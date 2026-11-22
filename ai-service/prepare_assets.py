"""Download the specific upstream files used by the local CatVTON service."""

import argparse
from pathlib import Path

from huggingface_hub import snapshot_download

from inference import DEFAULT_MODEL_DIR, SOURCE_FILES


ASSETS = (
    (
        "source",
        "zhengchong/CatVTON",
        "space",
        "24a6952918a8c6168b89ae32ad61e7e447e3987b",
        SOURCE_FILES,
    ),
    (
        "base",
        "timbrooks/instruct-pix2pix",
        "model",
        "31519b5cb02a7fd89b906d88731cd4d6a7bbf88d",
        (
            "scheduler/scheduler_config.json",
            "unet/config.json",
            "unet/diffusion_pytorch_model.fp16.safetensors",
        ),
    ),
    (
        "vae",
        "stabilityai/sd-vae-ft-mse",
        "model",
        "31f26fdeee1355a5c34592e401dd41e45d25a493",
        ("config.json", "diffusion_pytorch_model.safetensors"),
    ),
    (
        "attention",
        "zhengchong/CatVTON-MaskFree",
        "model",
        "b034d7700d6bf0e8ae1c01f9398f2eb54df5f52a",
        ("vitonhd-16k-512/attention/model.safetensors",),
    ),
)


def prepare_assets(model_dir: Path) -> None:
    for folder, repo, kind, revision, files in ASSETS:
        target = model_dir / folder
        if all((target / name).is_file() for name in files):
            continue
        print(f"Preparing {folder} from {repo}@{revision}", flush=True)
        snapshot_download(
            repo_id=repo,
            repo_type=kind,
            revision=revision,
            allow_patterns=list(files),
            local_dir=target,
            token=False,
        )
        missing = [name for name in files if not (target / name).is_file()]
        if missing:
            raise FileNotFoundError(f"Incomplete {folder} download: {', '.join(missing)}")


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--model-dir", type=Path, default=DEFAULT_MODEL_DIR)
    prepare_assets(parser.parse_args().model_dir)


if __name__ == "__main__":
    main()
