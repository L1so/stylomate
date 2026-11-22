"""Call the inference worker and handle cold starts on sleeping hosts."""

from base64 import b64decode, b64encode
from binascii import Error as Base64Error
import json
import re
from time import monotonic, sleep

import httpx
from fastapi import HTTPException

from app.config import settings


def ai_headers() -> dict[str, str]:
    if settings.ai_hf_space_token:
        headers = {"Authorization": f"Bearer {settings.ai_hf_space_token}"}
        if settings.ai_service_token:
            headers["X-StyloMate-Token"] = settings.ai_service_token
        return headers
    return {"Authorization": f"Bearer {settings.ai_service_token}"} if settings.ai_service_token else {}


def probe_ai(client: httpx.Client, timeout: float) -> bool:
    try:
        response = client.get(
            settings.ai_service_url.rstrip("/") + "/health",
            headers=ai_headers(),
            timeout=timeout,
        )
    except httpx.TransportError:
        return False

    if response.status_code in (401, 403, 404):
        response.raise_for_status()
    if response.status_code != 200:
        return False

    try:
        return response.json() == {"status": "ok"}
    except ValueError:
        # A provider may serve a startup page before the container is ready.
        return False


def wait_for_ai(client: httpx.Client) -> None:
    deadline = monotonic() + settings.ai_cold_start_timeout_seconds
    while (remaining := deadline - monotonic()) > 0:
        if probe_ai(client, min(remaining, 3)):
            return
        sleep(max(0, min(2, deadline - monotonic())))

    raise HTTPException(503, "AI service is still starting; please retry shortly")


def generate_zerogpu(client: httpx.Client, person: bytes, garment: bytes, max_bytes: int) -> bytes:
    endpoint = settings.ai_service_url.rstrip("/") + "/gradio_api/call/generate"
    headers = ai_headers()
    deadline = monotonic() + settings.ai_timeout_seconds
    response = client.post(endpoint, headers=headers, json={"data": [
        b64encode(person).decode("ascii"),
        b64encode(garment).decode("ascii"),
        "upper-body",
    ]})
    response.raise_for_status()
    try:
        event_id = response.json()["event_id"]
        if not isinstance(event_id, str) or not re.fullmatch(r"[A-Za-z0-9_-]{1,128}", event_id):
            raise ValueError
    except (KeyError, TypeError, ValueError):
        raise HTTPException(502, "AI returned an invalid queue response") from None

    event = None
    with client.stream("GET", f"{endpoint}/{event_id}", headers=headers) as stream:
        stream.raise_for_status()
        for line in stream.iter_lines():
            if monotonic() >= deadline:
                raise HTTPException(504, "AI service timed out; please retry")
            if line.startswith("event:"):
                event = line.removeprefix("event:").strip()
            elif line.startswith("data:") and event == "complete":
                return decode_zerogpu_image(line.removeprefix("data:").strip(), max_bytes)
            elif event == "error":
                break

    raise HTTPException(503, "AI could not finish the queued preview; please retry")


def decode_zerogpu_image(data: str, max_bytes: int) -> bytes:
    try:
        output, = json.loads(data)
        if not isinstance(output, str) or len(output) > ((max_bytes + 2) // 3) * 4:
            raise ValueError
        image = b64decode(output, validate=True)
        if len(image) > max_bytes:
            raise ValueError
        return image
    except (Base64Error, TypeError, ValueError):
        raise HTTPException(502, "AI returned an invalid image") from None
