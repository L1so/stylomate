"""Private HTTP service used by the StyloMate FastAPI backend."""

from contextlib import asynccontextmanager
from io import BytesIO
import logging
import os
from secrets import compare_digest
from threading import Lock
from typing import Annotated, Callable, Literal

from fastapi import Depends, FastAPI, File, Form, HTTPException, Request, Response, UploadFile
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from PIL import Image, ImageOps, UnidentifiedImageError

from inference import ImageGenerator, load_model


MAX_BYTES = 10 * 1024 * 1024
MAX_PIXELS = 20_000_000
logger = logging.getLogger("uvicorn.error")


def decode_image_data(data: bytes) -> Image.Image:
    if len(data) > MAX_BYTES:
        raise HTTPException(413, "Image exceeds 10 MiB.")

    try:
        with Image.open(BytesIO(data), formats=("JPEG", "PNG")) as image:
            if image.width * image.height > MAX_PIXELS:
                raise ValueError
            return ImageOps.exif_transpose(image).convert("RGB")
    except (UnidentifiedImageError, Image.DecompressionBombError, OSError, ValueError):
        raise HTTPException(422, "Upload a valid JPEG or PNG image up to 20 megapixels.") from None


def decode_image(upload: UploadFile) -> Image.Image:
    return decode_image_data(upload.file.read(MAX_BYTES + 1))


def create_app(
    model_loader: Callable[[], ImageGenerator] = load_model,
    *,
    token: str | None = None,
) -> FastAPI:
    token = os.environ.get("AI_SERVICE_TOKEN", "") if token is None else token
    generation_slot = Lock()

    @asynccontextmanager
    async def lifespan(app: FastAPI):
        app.state.generate = model_loader()
        try:
            yield
        finally:
            app.state.generate = None

    def authorize(request: Request) -> None:
        if token:
            credential = request.headers.get("X-StyloMate-Token")
            if credential is None:
                scheme, _, value = request.headers.get("Authorization", "").partition(" ")
                credential = value if scheme.lower() == "bearer" else ""
            if not compare_digest(credential.encode(), token.encode()):
                raise HTTPException(401, "Invalid service credentials.", {"WWW-Authenticate": "Bearer"})

    app = FastAPI(title="StyloMate AI", lifespan=lifespan, docs_url=None, redoc_url=None, openapi_url=None)

    @app.exception_handler(RequestValidationError)
    async def invalid_form(request: Request, error: RequestValidationError) -> JSONResponse:
        return JSONResponse(
            status_code=422,
            content={"detail": "Provide person_image, garment_image, and category=upper-body or lower-body."},
        )

    @app.get("/")
    @app.get("/health")
    def health(request: Request) -> dict[str, str]:
        if not callable(getattr(request.app.state, "generate", None)):
            raise HTTPException(503, "Model not ready.")
        return {"status": "ok"}

    @app.post("/generate", dependencies=[Depends(authorize)])
    def generate(
        request: Request,
        person_image: Annotated[UploadFile, File()],
        garment_image: Annotated[UploadFile, File()],
        category: Annotated[Literal["upper-body", "lower-body"], Form()],
    ) -> Response:
        model = getattr(request.app.state, "generate", None)
        if model is None or not generation_slot.acquire(blocking=False):
            raise HTTPException(503, "AI service is busy or unavailable.")

        try:
            with decode_image(person_image) as person, decode_image(garment_image) as garment:
                try:
                    with model(person, garment, category) as result, BytesIO() as output:
                        result.save(output, format="JPEG", quality=90)
                        data = output.getvalue()
                except Exception as error:
                    logger.error("Try-on inference failed (%s)", type(error).__name__)
                    raise HTTPException(503, "Could not generate a try-on preview.") from None

            if len(data) > MAX_BYTES:
                raise HTTPException(503, "Generated image exceeds the response limit.")
            return Response(data, media_type="image/jpeg", headers={"Cache-Control": "no-store"})
        finally:
            generation_slot.release()

    return app


app = create_app()
