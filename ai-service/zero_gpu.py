"""Gradio queue entry point for the private StyloMate ZeroGPU Space."""

from base64 import b64decode, b64encode
from binascii import Error as Base64Error
from io import BytesIO
import os
from pathlib import Path
from secrets import compare_digest
from threading import Lock

import spaces
from gradio import Error, Server
from fastapi import HTTPException, Request
from fastapi.responses import JSONResponse
from PIL import Image

from app import MAX_BYTES, decode_image_data
from inference import DEFAULT_MODEL_DIR, load_model
from prepare_assets import prepare_assets


model_dir = Path(os.environ.get("AI_MODEL_DIR", DEFAULT_MODEL_DIR)).expanduser()
prepare_assets(model_dir)
model = load_model(zerogpu=True)
token = os.environ.get("AI_SERVICE_TOKEN", "")
server = Server()
generation_lock = Lock()


@server.middleware("http")
async def authenticate(request: Request, call_next):
    public_paths = ("/", "/health", "/gradio_api/startup-events")
    if token and request.url.path not in public_paths:
        supplied = request.headers.get("X-StyloMate-Token", "")
        if not compare_digest(supplied.encode(), token.encode()):
            return JSONResponse({"detail": "Invalid service credentials."}, status_code=401)
    return await call_next(request)


@server.get("/")
@server.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


@spaces.GPU(duration=60)
def render(person: Image.Image, garment: Image.Image, category: str) -> str:
    if category not in {"upper-body", "lower-body"}:
        raise Error("Category must be upper-body or lower-body.")
    if not generation_lock.acquire(blocking=False):
        raise Error("AI service is busy. Try again shortly.")
    try:
        with model(person, garment, category) as result, BytesIO() as output:
            result.save(output, format="JPEG", quality=90)
            return b64encode(output.getvalue()).decode("ascii")
    finally:
        generation_lock.release()


def decode(encoded: str) -> Image.Image:
    if len(encoded) > ((MAX_BYTES + 2) // 3) * 4:
        raise Error("Image exceeds 10 MiB.")
    try:
        return decode_image_data(b64decode(encoded, validate=True))
    except (Base64Error, HTTPException, ValueError):
        raise Error("Upload a valid JPEG or PNG image up to 10 MiB and 20 megapixels.") from None


@server.api(name="generate")
def generate(person_image: str, garment_image: str, category: str) -> str:
    if category not in {"upper-body", "lower-body"}:
        raise Error("Only upper-body and lower-body try-on are supported.")
    with decode(person_image) as person, decode(garment_image) as garment:
        return render(person, garment, category)


if __name__ == "__main__":
    server.launch(server_name="0.0.0.0", server_port=int(os.environ.get("GRADIO_SERVER_PORT", "7860")))
