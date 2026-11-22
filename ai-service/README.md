# StyloMate AI Service

Owner: Member 1 (AI Engineer). Model, Python version, GPU/CUDA, licenses and dependencies are to be verified by the AI Engineer. Do not commit model weights or user photos.

Internal API (proposed): port 8001.
- GET /health: 200 JSON {"status":"ok"} only when model is ready.
- POST /generate: multipart/form-data fields `person_image` (file), `garment_image` (file), `category` (string; start with upper-body).
- Success: 200 with PNG/JPEG image bytes, correct Content-Type.
- Failure: non-2xx HTTP response with safe error details.

Only the main backend should call this service. Use private network/access controls in deployment. Store temporary inputs only as long as required and clean them up.

Backend integration now calls this contract. If AI_SERVICE_TOKEN is configured in the backend, enforce the same bearer token here. Real inference is still owned by member 1. The backend supports only category `upper-body` and caps returned JPEG/PNG data at 10 MiB. Do not return JSON or URLs on success. Backend does not retry generation automatically.
