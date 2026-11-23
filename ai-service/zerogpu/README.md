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
  - "zhengchong/CatVTON-MaskFree vitonhd-16k-512/attention/model.safetensors b034d7700d6bf0e8ae1c01f9398f2eb54df5f52a"
  - "stable-diffusion-v1-5/stable-diffusion-inpainting scheduler/scheduler_config.json,unet/config.json,unet/diffusion_pytorch_model.fp16.safetensors 8a4288a76071f7280aedbdb3253bdb9e9d5d84bb"
  - "zhengchong/CatVTON mix-48k-1024/attention/model.safetensors 2969fcf85fe62f2036605716f0b56f0b81d01d79"
  - "mattmdjaga/segformer_b2_clothes config.json,preprocessor_config.json,model.safetensors 584abc1e1d260e23c0fc627c5217a09b2b461046"
---

# StyloMate AI

Upper- and lower-body try-on for the StyloMate backend. The health endpoint reports server readiness. Tops use VITON-HD and bottoms use segmented CatVTON inpainting. The Gradio queue acquires a GPU for each generation and releases it when the job finishes. Anonymous requests to a public deployment share the ZeroGPU quota.

The service uses [CatVTON MaskFree](https://huggingface.co/zhengchong/CatVTON-MaskFree) by Zheng Chong and coauthors. The model card lists CC BY-NC-SA 4.0.
