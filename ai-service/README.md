# StyloMate AI Service

Private upper-body virtual try-on for the StyloMate FastAPI backend. The service loads the pretrained [CatVTON MaskFree VITON-HD 512 checkpoint](https://huggingface.co/zhengchong/CatVTON-MaskFree/tree/main/vitonhd-16k-512) once and generates a 384×512 preview. The backend owns preview storage and saved history.

## Setup

Use Python 3.12 and an NVIDIA driver compatible with CUDA 12.8. Run these commands from `ai-service/`.

```bash
python3.12 -m venv .venv
source .venv/bin/activate
python -m pip install torch==2.9.1 torchvision==0.24.1 --index-url https://download.pytorch.org/whl/cu128
python -m pip install -r requirements.txt
python prepare_assets.py
```

The setup downloads roughly 2.3 GB of model files and the pinned upstream inference code into `.cache/catvton/`. The service reads these local assets during startup. The [asset manifest](prepare_assets.py) records each upstream revision. Use `HF_HUB_DISABLE_XET=1` for the download if the Xet connection fails. If Hugging Face requires authentication, set `HF_TOKEN` in the download process environment.

## Start and verify

```bash
python -m uvicorn app:app --host 127.0.0.1 --port 8001 --workers 1
```

- `GET /health` returns `200 {"status":"ok"}` once the model is loaded.
- `POST /generate` accepts multipart JPEG/PNG `person_image` and `garment_image` files and the form field `category=upper-body`. Each file can be up to 10 MiB and 20 megapixels. Success is raw `image/jpeg` bytes under the backend's 10 MiB response limit.
- Set the same `AI_SERVICE_TOKEN` in the AI and backend environments to require a bearer token for generation. For a separate host, bind or forward the service over a private network the backend can reach.
- `AI_MODEL_DIR` overrides the local asset path. `AI_INFERENCE_STEPS` defaults to 25 and accepts values from 1 to 50. Run one worker per GPU with one generation in flight. Startup and inference timings are logged without photo contents or filenames.

```bash
curl --fail-with-body http://127.0.0.1:8001/health
curl --fail-with-body http://127.0.0.1:8001/generate \
  -F 'category=upper-body' \
  -F 'person_image=@person.jpg' \
  -F 'garment_image=@shirt.jpg' \
  --output preview.jpg
```

When token auth is enabled, add the `Authorization: Bearer` header with the configured token to the generation request.

## Team integration

Set `AI_SERVICE_URL=http://127.0.0.1:8001` in the existing backend environment when both services run on the same host. Set `FASTAPI_USE_MOCK=false` in Laravel. The default backend and Laravel AI timeouts are 300 s and 330 s respectively. Keep the deployed request timeout above the measured inference time.

Register or log in, upload a real photo of a top to Wardrobe, select that top in Try-On, and upload a front-facing person photo. Generate and save the preview, then sign in again and open Profile → Try On. Use small JPEGs for the demo if the PHP host retains its default 2 MB upload limit. The catalog seed uses illustrations.

Use approved photos for demos and keep photos, generated previews, weights, and tokens out of Git. The service uses in-memory image processing and closes the multipart uploads at the end of the request.

## Shared GPU on Hugging Face

The shared deployment uses a private [Gradio ZeroGPU Space](https://huggingface.co/docs/hub/spaces-zerogpu). The Space loads the pinned public checkpoints at startup and allocates a GPU only while a queued generation runs. ZeroGPU quotas and startup time still apply.

To publish the inference worker, upload only the files below to the Space repository root.

- Copy `zerogpu/README.md` and `zerogpu/requirements.txt` as `README.md` and `requirements.txt`.
- Copy `app.py`, `inference.py`, `prepare_assets.py`, and `zero_gpu.py` under their existing names.

Give the FastAPI backend a Hugging Face read token with access to the private Space. If using the optional middleware secret, set `AI_SERVICE_TOKEN` as a Space secret and use the same value in FastAPI. Configure FastAPI with the direct `hf.space` app URL.

```dotenv
AI_SERVICE_URL=https://redeye1611-stylomate-ai.hf.space
AI_HF_SPACE_TOKEN=<hf-space-read-token>
AI_HF_ZEROGPU=true
AI_COLD_START_TIMEOUT_SECONDS=90
```

In Laravel, enable visit-triggered warmup and allow enough time for the backend cold-start wait plus inference.

```dotenv
FASTAPI_USE_MOCK=false
FASTAPI_AI_WARMUP=true
FASTAPI_AI_TIMEOUT=420
```

A visible, signed-in page triggers one background wake-up request. New page visits or returning to the tab can request warmup again after the cooldown. There is no periodic polling while the user is idle. If someone submits a photo during cold start, FastAPI checks readiness before it sends the image pair to the worker. This retries the health check only, so each submission sends at most one inference request.

When the host goes to sleep, opening a signed-in page asks it to wake. Once ready, use Try-On and save the preview to Profile. The Gradio queue acquires the GPU for that job and releases it on completion. The application's idle sleep is managed by Hugging Face.

## API tests

```bash
python -m pip install -r requirements-api.txt httpx==0.28.1 pytest==9.2.0
python -m pytest -q
```

The API tests substitute an in-memory generator and run on CPU. A real GPU run is required to validate model output and runtime.

## Verified demo

On the 6 GB RTX 3060 Laptop GPU with PyTorch 2.9.1 and CUDA 12.8, the 25-step model generated two previews at 384×512 in 4.8 to 5.2 seconds each. Peak CUDA memory was approximately 2.23 GiB allocated and 2.69 GiB reserved. The second preview passed through Laravel and FastAPI, was saved to PostgreSQL-backed history, and remained visible in Profile after a fresh login.

The upstream [demo examples](https://huggingface.co/spaces/zhengchong/CatVTON/tree/24a6952918a8c6168b89ae32ad61e7e447e3987b/resource/demo/example) used for verification were `person/women/049713_0.jpg` with `condition/upper/21514384_52353349_1000.jpg` and then `condition/upper/23255574_53383833_1000.jpg`. Upload the second top to Wardrobe to repeat the tested app flow.

## Upstream models

- [CatVTON MaskFree](https://huggingface.co/zhengchong/CatVTON-MaskFree) and its [upstream inference implementation](https://huggingface.co/spaces/zhengchong/CatVTON/tree/24a6952918a8c6168b89ae32ad61e7e447e3987b) by Zheng Chong and coauthors. The CatVTON model card lists CC BY-NC-SA 4.0.
- [InstructPix2Pix](https://huggingface.co/timbrooks/instruct-pix2pix) supplies the base UNet and scheduler. [SD VAE FT-MSE](https://huggingface.co/stabilityai/sd-vae-ft-mse) supplies the VAE. See each model card for its terms.
