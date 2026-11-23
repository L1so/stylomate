"""Generate garment previews and blend edits into their segmented regions."""

import logging
import os
from pathlib import Path
import sys
from time import perf_counter
from typing import Callable, Literal

import numpy as np
from PIL import Image, ImageChops, ImageFilter, ImageOps


DEFAULT_MODEL_DIR = Path(__file__).resolve().parent / ".cache" / "catvton"
SOURCE_FILES = ("model/pipeline.py", "model/attn_processor.py", "model/utils.py", "utils.py")
WIDTH, HEIGHT = 384, 512
GarmentCategory = Literal["upper-body", "lower-body"]
UPPER_CHECKPOINT = "vitonhd-16k-512"
LOWER_CHECKPOINT = "mix-48k-1024"
UPPER_BODY_LABELS = {"Upper-clothes", "Left-arm", "Right-arm"}
LOWER_BODY_LABELS = {"Pants", "Skirt", "Left-leg", "Right-leg"}
ImageGenerator = Callable[[Image.Image, Image.Image, GarmentCategory], Image.Image]
logger = logging.getLogger("uvicorn.error")


class LowerBodyNotVisible(ValueError):
    """The person photo does not contain a usable lower-body region."""


def garment_masks(
    labels: np.ndarray, garment_ids: set[int], background_id: int
) -> tuple[Image.Image, Image.Image] | None:
    region = np.isin(labels, list(garment_ids))
    if region.sum() < region.size * 0.01:
        return None

    # Give loose garments room around the silhouette while protecting other body parts.
    with (
        Image.fromarray(region.astype(np.uint8) * 255) as silhouette,
        Image.fromarray((region | (labels == background_id)).astype(np.uint8) * 255) as allowed,
    ):
        mask = ImageChops.multiply(silhouette.filter(ImageFilter.MaxFilter(21)), allowed)
        alpha = ImageChops.multiply(mask.filter(ImageFilter.GaussianBlur(3)), allowed)
    return mask, alpha


def load_model(*, zerogpu: bool = False) -> ImageGenerator:
    import torch

    if not zerogpu and not torch.cuda.is_available():
        raise RuntimeError("CatVTON requires a CUDA GPU and a CUDA-enabled PyTorch build.")

    weights = Path(os.environ.get("AI_MODEL_DIR", DEFAULT_MODEL_DIR)).expanduser().resolve()
    steps = int(os.environ.get("AI_INFERENCE_STEPS", "25"))
    if not 1 <= steps <= 50:
        raise ValueError("AI_INFERENCE_STEPS must be between 1 and 50.")

    def pipeline_files(folder: str) -> list[Path]:
        return [
            weights / folder / "scheduler" / "scheduler_config.json",
            weights / folder / "unet" / "config.json",
            weights / folder / "unet" / "diffusion_pytorch_model.fp16.safetensors",
        ]

    required = [
        *(weights / "source" / name for name in SOURCE_FILES),
        *pipeline_files("base"),
        *pipeline_files("inpaint"),
        weights / "vae" / "config.json",
        weights / "vae" / "diffusion_pytorch_model.safetensors",
        weights / "attention" / UPPER_CHECKPOINT / "attention" / "model.safetensors",
        weights / "masked-attention" / LOWER_CHECKPOINT / "attention" / "model.safetensors",
        *(weights / "parser" / name for name in ("config.json", "preprocessor_config.json", "model.safetensors")),
    ]
    for path in required:
        if not path.is_file():
            raise RuntimeError(f"Missing CatVTON asset {path}. Run python prepare_assets.py first.")

    from diffusers import AutoencoderKL, DDIMScheduler, UNet2DConditionModel
    from transformers import SegformerForSemanticSegmentation, SegformerImageProcessor

    # The pinned upstream code uses absolute imports such as model.utils.
    source = str(weights / "source")
    sys.path.insert(0, source)
    try:
        from model.attn_processor import SkipAttnProcessor
        from model.pipeline import CatVTONPipeline, CatVTONPix2PixPipeline
        from model.utils import get_trainable_module, init_adapter
    finally:
        sys.path.remove(source)

    device = torch.device("cuda:0")
    dtype = torch.float16

    class LocalWeights:
        def __init__(self, base: str, attention: str, version: str) -> None:
            self.device = device
            self.weight_dtype = dtype
            self.skip_safety_check = True
            self.noise_scheduler = DDIMScheduler.from_pretrained(weights / base / "scheduler")
            self.vae = vae
            self.unet = UNet2DConditionModel.from_pretrained(
                weights / base / "unet", variant="fp16", use_safetensors=True, torch_dtype=dtype
            ).to(device).eval()
            init_adapter(self.unet, cross_attn_cls=SkipAttnProcessor)
            self.attn_modules = get_trainable_module(self.unet, "attention")
            self.auto_attn_ckpt_load(str(weights / attention), version)

    class UpperPipeline(LocalWeights, CatVTONPix2PixPipeline):
        pass

    class LowerPipeline(LocalWeights, CatVTONPipeline):
        pass

    started = perf_counter()
    vae = AutoencoderKL.from_pretrained(
        weights / "vae", use_safetensors=True, torch_dtype=dtype
    ).to(device).eval()
    upper = UpperPipeline("base", "attention", UPPER_CHECKPOINT)
    lower = LowerPipeline("inpaint", "masked-attention", "mix")
    parser = SegformerForSemanticSegmentation.from_pretrained(
        weights / "parser", use_safetensors=True
    ).to(device).eval()
    processor = SegformerImageProcessor.from_pretrained(weights / "parser")
    label_ids = {name: index for index, name in parser.config.id2label.items()}
    garment_ids = {
        "upper-body": {label_ids[name] for name in UPPER_BODY_LABELS},
        "lower-body": {label_ids[name] for name in LOWER_BODY_LABELS},
    }
    background_id = label_ids["Background"]
    if not zerogpu:
        torch.cuda.synchronize(device)
    logger.info("Loaded CatVTON %s and %s in %.2f s", UPPER_CHECKPOINT, LOWER_CHECKPOINT, perf_counter() - started)

    def find_masks(person, category):
        inputs = processor(images=person, return_tensors="pt").to(device)
        logits = parser(**inputs).logits
        labels = torch.nn.functional.interpolate(
            logits, size=(HEIGHT, WIDTH), mode="bilinear", align_corners=False
        ).argmax(dim=1)[0].cpu().numpy()
        return garment_masks(labels, garment_ids[category], background_id)

    def generate(
        person: Image.Image,
        garment: Image.Image,
        category: GarmentCategory,
    ) -> Image.Image:
        torch.cuda.reset_peak_memory_stats(device)
        started = perf_counter()
        options = dict(
            width=WIDTH,
            height=HEIGHT,
            num_inference_steps=steps,
            generator=torch.Generator(device=device).manual_seed(42),
        )
        # Keep feet and legs in frame for full-body photos and subsequent outfit stages.
        with torch.inference_mode(), ImageOps.pad(
            person, (WIDTH, HEIGHT), method=Image.Resampling.LANCZOS, color="white"
        ) as framed:
            masks = find_masks(framed, category)
            if masks is None:
                if category == "lower-body":
                    raise LowerBodyNotVisible()
                result = upper(image=framed, condition_image=garment, **options)[0]
            else:
                mask, alpha = masks
                with mask, alpha:
                    if category == "lower-body":
                        rendered = lower(image=framed, condition_image=garment, mask=mask, **options)[0]
                    else:
                        rendered = upper(image=framed, condition_image=garment, **options)[0]
                    with rendered:
                        result = Image.composite(rendered, framed, alpha)
        torch.cuda.synchronize(device)
        logger.info(
            "CatVTON %s generated %dx%d in %.2f s (peak CUDA %.0f MiB allocated, %.0f MiB reserved)",
            category,
            WIDTH,
            HEIGHT,
            perf_counter() - started,
            torch.cuda.max_memory_allocated(device) / 1024**2,
            torch.cuda.max_memory_reserved(device) / 1024**2,
        )
        return result

    return generate
