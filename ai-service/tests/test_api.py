from concurrent.futures import ThreadPoolExecutor
from io import BytesIO
from threading import Event

from fastapi.testclient import TestClient
import numpy as np
from PIL import Image
import pytest

import app
from inference import LowerBodyNotVisible, garment_masks


def make_client(application) -> TestClient:
    # FastAPI's current TestClient backend otherwise stalls under the project test runtime.
    return TestClient(application, backend_options={"use_uvloop": True})


def image_bytes(color: str = "white", image_format: str = "PNG") -> bytes:
    with BytesIO() as buffer:
        Image.new("RGB", (12, 20), color).save(buffer, format=image_format)
        return buffer.getvalue()


def files(person: bytes | None = None, garment: bytes | None = None) -> dict:
    return {
        "person_image": ("person.png", person if person is not None else image_bytes("red"), "image/png"),
        "garment_image": ("garment.png", garment if garment is not None else image_bytes("blue"), "image/png"),
    }


def try_on(client: TestClient, **kwargs):
    kwargs.setdefault("files", files())
    kwargs.setdefault("data", {"category": "upper-body"})
    return client.post("/generate", **kwargs)


def test_model_is_loaded_once_and_output_is_jpeg():
    loads = []
    colors = []

    def generate(person, garment, category):
        colors.append((person.getpixel((0, 0)), garment.getpixel((0, 0)), category))
        return garment.copy()

    def load():
        loads.append(True)
        return generate

    with make_client(app.create_app(load, token="")) as client:
        assert client.get("/health").json() == {"status": "ok"}
        for category in ("upper-body", "lower-body"):
            response = try_on(client, data={"category": category})
            assert response.status_code == 200
            assert response.headers["content-type"] == "image/jpeg"
            assert response.headers["cache-control"] == "no-store"
            with Image.open(BytesIO(response.content)) as result:
                assert result.format == "JPEG"
                assert result.size == (12, 20)

    assert len(loads) == 1
    assert colors == [
        ((255, 0, 0), (0, 0, 255), "upper-body"),
        ((255, 0, 0), (0, 0, 255), "lower-body"),
    ]


def test_readiness_requires_startup():
    application = app.create_app(lambda: lambda person, garment, category: garment.copy(), token="")
    with make_client(application) as client:
        assert client.get("/health").status_code == 200
    assert make_client(application).get("/health").status_code == 503


def test_lower_body_mask_protects_shirt_shoes_and_distant_background():
    labels = np.zeros((96, 96), dtype=np.uint8)
    labels[15:40, 30:65] = 4  # shirt
    labels[40:79, 35:60] = 6  # pants
    labels[79:85, 35:60] = 9  # shoes

    mask, alpha = garment_masks(labels, {6}, 0)
    with mask, alpha:
        for region in (mask, alpha):
            assert region.getpixel((48, 55)) == 255
            assert region.getpixel((48, 39)) == 0
            assert region.getpixel((48, 80)) == 0
            assert region.getpixel((3, 50)) == 0
        assert mask.getpixel((32, 55)) == 255
    mask, alpha = garment_masks(labels, {4}, 0)
    with mask, alpha:
        assert alpha.getpixel((48, 22)) == 255
        assert alpha.getpixel((48, 40)) == 0


def test_garment_mask_requires_a_visible_region():
    labels = np.full((96, 96), 4, dtype=np.uint8)
    assert garment_masks(labels, {6}, 0) is None


def test_missing_lower_body_returns_validation_error_and_releases_slot():
    def generate(person, garment, category):
        if category == "lower-body":
            raise LowerBodyNotVisible()
        return garment.copy()

    with make_client(app.create_app(lambda: generate, token="")) as client:
        response = try_on(client, data={"category": "lower-body"})
        assert response.status_code == 422
        assert response.headers["X-StyloMate-Error"] == "lower_body_not_visible"
        assert try_on(client).status_code == 200


def test_service_auth_when_configured():
    with make_client(app.create_app(lambda: lambda person, garment, category: garment.copy(), token="shared-test")) as client:
        assert client.get("/").json() == {"status": "ok"}
        assert try_on(client).status_code == 401
        assert try_on(client, headers={"Authorization": "Bearer incorrect"}).status_code == 401
        assert try_on(client, headers={"Authorization": "Bearer shared-test"}).status_code == 200
        assert try_on(client, headers={
            "Authorization": "Bearer gateway-test",
            "X-StyloMate-Token": "shared-test",
        }).status_code == 200


@pytest.mark.parametrize("input_files,data,expected", [
    ({}, {"category": "upper-body"}, 422),
    (files(), {"category": "dress"}, 422),
    (files(person=b"not an image"), {"category": "upper-body"}, 422),
    (files(garment=image_bytes(image_format="GIF")), {"category": "upper-body"}, 422),
    (files(person=b"x" * (app.MAX_BYTES + 1)), {"category": "upper-body"}, 413),
])
def test_invalid_requests_do_not_reach_inference(input_files, data, expected):
    calls = []

    def generate(person, garment, category):
        calls.append(True)
        return garment.copy()

    with make_client(app.create_app(lambda: generate, token="")) as client:
        assert try_on(client, files=input_files, data=data).status_code == expected
    assert calls == []


def test_image_pixel_limit_is_enforced(monkeypatch):
    monkeypatch.setattr(app, "MAX_PIXELS", 100)
    with make_client(app.create_app(lambda: lambda person, garment, category: garment.copy(), token="")) as client:
        assert try_on(client).status_code == 422


def test_failed_inference_releases_the_generation_slot():
    attempts = 0

    def generate(person, garment, category):
        nonlocal attempts
        attempts += 1
        if attempts == 1:
            raise RuntimeError("internal details")
        return garment.copy()

    with make_client(app.create_app(lambda: generate, token="")) as client:
        failed = try_on(client)
        assert failed.status_code == 503
        assert "internal details" not in failed.text
        assert try_on(client).status_code == 200


def test_concurrent_request_returns_busy_without_blocking_health():
    started = Event()
    release = Event()

    def generate(person, garment, category):
        started.set()
        assert release.wait(5)
        return garment.copy()

    with make_client(app.create_app(lambda: generate, token="")) as client, ThreadPoolExecutor() as pool:
        first = pool.submit(try_on, client)
        try:
            assert started.wait(5)
            assert client.get("/health").status_code == 200
            assert try_on(client).status_code == 503
        finally:
            release.set()
        assert first.result(timeout=5).status_code == 200
