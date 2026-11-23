---
title: StyloMate AI
sdk: gradio
sdk_version: 6.17.3
python_version: "3.12.12"
app_file: zero_gpu.py
startup_duration_timeout: 30m
preload_from_hub:
  - "timbrooks/instruct-pix2pix scheduler/scheduler_config.json,unet/config.json,unet/diffusion_pytorch_model.fp16.safetensors 31519b5cb02a7fd89b906d88731cd4d6a7bbf88d"
  - "stabilityai/sd-vae-ft-mse config.json,diffusion_pytorch_model.safetensors 31f26fdeee1355a5c34592e401dd41e45d25a493"
  - "zhengchong/CatVTON-MaskFree vitonhd-16k-512/attention/model.safetensors,dresscode-16k-512/attention/model.safetensors b034d7700d6bf0e8ae1c01f9398f2eb54df5f52a"
---

# StyloMate AI

Upper- and lower-body try-on for the StyloMate backend. The health endpoint reports server readiness. A generation request selects the VITON-HD checkpoint for tops or DressCode for bottoms, acquires a GPU through the Gradio queue, and releases it after returning the result. Anonymous requests to a public deployment share the ZeroGPU quota.

The service uses [CatVTON MaskFree](https://huggingface.co/zhengchong/CatVTON-MaskFree) by Zheng Chong and coauthors. The model card lists CC BY-NC-SA 4.0.
