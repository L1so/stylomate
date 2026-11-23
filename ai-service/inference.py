"""Load pinned CatVTON MaskFree checkpoints for upper- and lower-body try-on."""

import logging
import os
from pathlib import Path
import sys
from time import perf_counter
from typing import Callable, Literal

from PIL import Image


DEFAULT_MODEL_DIR = Path(__file__).resolve().parent / ".cache" / "catvton"
SOURCE_FILES = ("model/pipeline.py", "model/attn_processor.py", "model/utils.py", "utils.py")
WIDTH, HEIGHT = 384, 512
GarmentCategory = Literal["upper-body", "lower-body"]
CHECKPOINTS: dict[GarmentCategory, str] = {
    "upper-body": "vitonhd-16k-512",
    "lower-body": "dresscode-16k-512",
}
ImageGenerator = Callable[[Image.Image, Image.Image, GarmentCategory], Image.Image]
logger = logging.getLogger("uvicorn.error")


def load_model(*, zerogpu: bool = False) -> ImageGenerator:
    import torch

    if not zerogpu and not torch.cuda.is_available():
        raise RuntimeError("CatVTON requires a CUDA GPU and a CUDA-enabled PyTorch build.")

    weights = Path(os.environ.get("AI_MODEL_DIR", DEFAULT_MODEL_DIR)).expanduser().resolve()
    steps = int(os.environ.get("AI_INFERENCE_STEPS", "25"))
    if not 1 <= steps <= 50:
        raise ValueError("AI_INFERENCE_STEPS must be between 1 and 50.")

    required = [
        *(weights / "source" / name for name in SOURCE_FILES),
        weights / "base" / "scheduler" / "scheduler_config.json",
        weights / "base" / "unet" / "config.json",
        weights / "base" / "unet" / "diffusion_pytorch_model.fp16.safetensors",
        weights / "vae" / "config.json",
        weights / "vae" / "diffusion_pytorch_model.safetensors",
        *(
            weights / "attention" / checkpoint / "attention" / "model.safetensors"
            for checkpoint in CHECKPOINTS.values()
        ),
    ]
    for path in required:
        if not path.is_file():
            raise RuntimeError(f"Missing CatVTON asset {path}. Run python prepare_assets.py first.")

    from diffusers import AutoencoderKL, DDIMScheduler, UNet2DConditionModel

    # The pinned upstream code uses absolute imports such as model.utils.
    source = str(weights / "source")
    sys.path.insert(0, source)
    try:
        from model.attn_processor import SkipAttnProcessor
        from model.pipeline import CatVTONPix2PixPipeline
        from model.utils import get_trainable_module, init_adapter
    finally:
        sys.path.remove(source)

    device = torch.device("cuda:0")
    dtype = torch.float16

    class LocalCatVTON(CatVTONPix2PixPipeline):
        """Use the upstream inference loop with explicit local checkpoint paths."""

        def __init__(self) -> None:
            self.device = device
            self.weight_dtype = dtype
            self.skip_safety_check = True
            self.noise_scheduler = DDIMScheduler.from_pretrained(weights / "base" / "scheduler")
            self.vae = AutoencoderKL.from_pretrained(
                weights / "vae", use_safetensors=True, torch_dtype=dtype
            ).to(device).eval()
            self.unet = UNet2DConditionModel.from_pretrained(
                weights / "base" / "unet", variant="fp16", use_safetensors=True, torch_dtype=dtype
            ).to(device).eval()
            init_adapter(self.unet, cross_attn_cls=SkipAttnProcessor)
            self.attn_modules = get_trainable_module(self.unet, "attention")
            self.auto_attn_ckpt_load(str(weights / "attention"), CHECKPOINTS["upper-body"])

    started = perf_counter()
    pipeline = LocalCatVTON()
    if not zerogpu:
        torch.cuda.synchronize(device)
    current_checkpoint = CHECKPOINTS["upper-body"]
    logger.info("Loaded CatVTON %s in %.2f s", current_checkpoint, perf_counter() - started)

    def generate(
        person: Image.Image,
        garment: Image.Image,
        category: GarmentCategory,
    ) -> Image.Image:
        nonlocal current_checkpoint
        checkpoint = CHECKPOINTS[category]
        if checkpoint != current_checkpoint:
            switch_started = perf_counter()
            pipeline.auto_attn_ckpt_load(str(weights / "attention"), checkpoint)
            current_checkpoint = checkpoint
            logger.info("Switched CatVTON checkpoint to %s in %.2f s", checkpoint, perf_counter() - switch_started)

        torch.cuda.reset_peak_memory_stats(device)
        started = perf_counter()
        with torch.inference_mode():
            result = pipeline(
                image=person,
                condition_image=garment,
                width=WIDTH,
                height=HEIGHT,
                num_inference_steps=steps,
                generator=torch.Generator(device=device).manual_seed(42),
            )[0]
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
