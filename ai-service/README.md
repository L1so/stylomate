# StyloMate AI Service

Upper- and lower-body virtual try-on for the StyloMate FastAPI backend. Previews are 384×512. Tops use CatVTON MaskFree VITON-HD. For bottoms, SegFormer locates pants, skirts and legs in the person photo so CatVTON can inpaint that region with the selected garment. The service blends edits into the source photo using the segmented clothing region. During outfit try-on, FastAPI sends the result of the bottom pass into the top pass so the bottom is generated on the original pose and preserved by the upper-body blend. The two pipelines share a VAE.

## Setup

Use Python 3.12 and an NVIDIA driver compatible with CUDA 12.8. The two pipelines were tested together on a 6 GB RTX 3060 Laptop GPU. Run these commands from `ai-service/`.

```bash
python3.12 -m venv .venv
source .venv/bin/activate
python -m pip install torch==2.9.1 torchvision==0.24.1 --index-url https://download.pytorch.org/whl/cu128
python -m pip install -r requirements.txt
python prepare_assets.py
```

The setup downloads roughly 4.3 GB of model files and the pinned upstream inference code into `.cache/catvton/`. For an existing install, run `python prepare_assets.py` again and restart the worker. The [asset manifest](prepare_assets.py) records each upstream revision. Use `HF_HUB_DISABLE_XET=1` if the Xet connection fails.

## Start and verify

```bash
python -m uvicorn app:app --host 127.0.0.1 --port 8001 --workers 1
```

- `GET /health` returns `200 {"status":"ok"}` once the model is loaded.
- `POST /generate` accepts multipart JPEG/PNG `person_image` and `garment_image` files and `category=upper-body` or `category=lower-body`. Each file can be up to 10 MiB and 20 megapixels. Success is raw `image/jpeg` bytes under the backend's 10 MiB response limit.
- Use a photo with the waist and legs in frame for bottoms and outfits. If the parser cannot find a usable lower-body region, the request returns a photo validation error.
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

Use `category=lower-body` with a pants or skirt garment. FastAPI makes two requests for a full outfit. It generates the bottom first, then uses that preview as the person image for the upper-body request.

When token auth is enabled, add the `Authorization: Bearer` header with the configured token to the generation request.

## Team integration

Set `AI_SERVICE_URL=http://127.0.0.1:8001` in the existing backend environment when both services run on the same host. Set `FASTAPI_USE_MOCK=false` in Laravel. The default backend and Laravel AI timeouts are 300 s and 330 s respectively. Keep the deployed request timeout above the measured inference time.

Register or log in, upload a real top and bottom to Wardrobe, create an outfit in Mix & Match, then choose Try On from the saved outfit. Upload a front-facing full-body photo; FastAPI generates the top stage followed by the bottom stage. Use small JPEGs for the demo if the PHP host retains its default 2 MB upload limit. The catalog seed uses illustrations.

Use approved photos for demos and keep photos, generated previews, weights, and tokens out of Git. The service uses in-memory image processing and closes the multipart uploads at the end of the request.

## Shared GPU on Hugging Face

The shared deployment uses a public [Gradio ZeroGPU Space](https://huggingface.co/spaces/RedEye1611/stylomate-ai). The Space loads the pinned public checkpoints at startup and allocates a GPU only while a queued generation runs. Requests without a Hugging Face token use the shared anonymous quota. [ZeroGPU usage tiers](https://huggingface.co/docs/hub/spaces-zerogpu#usage-tiers)

To publish the inference worker, upload only the files below to the Space repository root.

- Copy `zerogpu/README.md` and `zerogpu/requirements.txt` as `README.md` and `requirements.txt`.
- Copy `app.py`, `inference.py`, `prepare_assets.py`, and `zero_gpu.py` under their existing names.

Configure FastAPI with the direct `hf.space` app URL. Leave `AI_HF_SPACE_TOKEN` and `AI_SERVICE_TOKEN` empty for tokenless access to the public deployment. For a private deployment, set `AI_HF_SPACE_TOKEN` to a read token with access to that Space. If you enable the optional StyloMate shared secret, set `AI_SERVICE_TOKEN` in both services.

```dotenv
AI_SERVICE_URL=https://redeye1611-stylomate-ai.hf.space
AI_HF_SPACE_TOKEN=
AI_SERVICE_TOKEN=
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

- [CatVTON](https://huggingface.co/zhengchong/CatVTON) and [CatVTON MaskFree](https://huggingface.co/zhengchong/CatVTON-MaskFree) by Zheng Chong and coauthors. The CatVTON model card lists CC BY-NC-SA 4.0. See the [upstream inference implementation](https://huggingface.co/spaces/zhengchong/CatVTON/tree/24a6952918a8c6168b89ae32ad61e7e447e3987b).
- [SegFormer B2 clothes parser](https://huggingface.co/mattmdjaga/segformer_b2_clothes) locates the editable garment region. [Stable Diffusion Inpainting](https://huggingface.co/stable-diffusion-v1-5/stable-diffusion-inpainting) supplies the lower-body UNet and scheduler.
- [InstructPix2Pix](https://huggingface.co/timbrooks/instruct-pix2pix) supplies the base UNet and scheduler. [SD VAE FT-MSE](https://huggingface.co/stabilityai/sd-vae-ft-mse) supplies the VAE. See each model card for its terms.
