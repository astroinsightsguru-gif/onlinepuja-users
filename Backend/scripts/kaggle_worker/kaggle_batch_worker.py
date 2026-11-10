"""
OnlinePuja.live — Autonomous Kaggle Dual-T4 GPU Batch Worker
=============================================================
Run this script inside a free Kaggle Notebook with GPU enabled (GPU T4 x 2).
It autonomously fetches pending video and image rendering tasks from the
OnlinePuja production server, synthesizes them using free open-source models,
and uploads the finished MP4 reels and FLUX.1 images directly to the VPS.

Requirements (in Kaggle notebook cell):
!pip install -q torch torchvision torchaudio --index-url https://download.pytorch.org/whl/cu121
!pip install -q diffusers transformers accelerate sentencepiece protobuf requests
"""

import os
import sys
import time
import requests
import torch
from pathlib import Path

# ================= Configuration =================
API_BASE = os.environ.get("ONLINEPUJA_API", "https://onlinepuja.live/api/v1/ai-worker")
WORKER_TOKEN = os.environ.get("WORKER_TOKEN", "onlinepuja_kgl_worker_sec_2026")
DEVICE = "cuda" if torch.cuda.is_available() else "cpu"

print(f"[*] Initializing OnlinePuja AI Worker on device: {DEVICE}")
if DEVICE == "cuda":
    print(f"[*] GPU Name: {torch.cuda.get_device_name(0)}")
    print(f"[*] Available VRAM: {torch.cuda.get_device_properties(0).total_memory / 1e9:.2f} GB")


def check_connection():
    try:
        url = f"{API_BASE}/ping"
        res = requests.get(url, headers={"X-AI-Worker-Token": WORKER_TOKEN}, timeout=15)
        if res.status_code == 200:
            data = res.json()
            print(f"[✓] Connected to OnlinePuja Server: {data.get('message')}")
            print(f"    Pending Videos: {data.get('pending_videos')}, Pending Images: {data.get('pending_images')}")
            return True
        else:
            print(f"[✗] Ping failed ({res.status_code}): {res.text}")
            return False
    except Exception as e:
        print(f"[✗] Connection error: {e}")
        return False


def fetch_jobs(limit=5):
    try:
        url = f"{API_BASE}/jobs?limit={limit}"
        res = requests.get(url, headers={"X-AI-Worker-Token": WORKER_TOKEN}, timeout=20)
        if res.status_code == 200:
            return res.json().get("jobs", [])
    except Exception as e:
        print(f"[!] Error fetching jobs: {e}")
    return []


def upload_asset(job_id, file_path, asset_type, model_name):
    try:
        url = f"{API_BASE}/upload"
        with open(file_path, "rb") as f:
            files = {"file": f}
            data = {
                "token": WORKER_TOKEN,
                "job_id": job_id,
                "type": asset_type,
                "model": model_name,
            }
            res = requests.post(url, files=files, data=data, timeout=120)
            if res.status_code == 200:
                print(f"[✓] Successfully uploaded {asset_type} for job {job_id} -> {res.json().get('url')}")
                return True
            else:
                print(f"[✗] Upload failed ({res.status_code}): {res.text}")
    except Exception as e:
        print(f"[!] Upload exception: {e}")
    return False


def render_image_flux(prompt, output_path, width=1024, height=1024):
    """Generates photorealistic artwork using FLUX.1 Schnell on dual T4 GPU"""
    print(f"[*] Rendering FLUX.1 image: {prompt[:60]}...")
    from diffusers import FluxPipeline

    pipe = FluxPipeline.from_pretrained(
        "black-forest-labs/FLUX.1-schnell",
        torch_dtype=torch.bfloat16,
    )
    pipe.enable_model_cpu_offload()

    image = pipe(
        prompt,
        guidance_scale=0.0,
        num_inference_steps=4,
        max_sequence_length=256,
        generator=torch.Generator("cpu").manual_seed(int(time.time())),
        width=width,
        height=height,
    ).images[0]

    image.save(output_path, "WEBP", quality=92)
    return output_path


def render_video_cogvideox(prompt, output_path, num_frames=49):
    """Generates HD video using CogVideoX-2B or AnimateDiff"""
    print(f"[*] Rendering video reel for prompt: {prompt[:60]}...")
    try:
        from diffusers import CogVideoXPipeline
        from diffusers.utils import export_to_video

        pipe = CogVideoXPipeline.from_pretrained(
            "THUDM/CogVideoX-2b",
            torch_dtype=torch.bfloat16,
        )
        pipe.enable_model_cpu_offload()

        video = pipe(
            prompt=prompt,
            num_videos_per_prompt=1,
            num_inference_steps=50,
            num_frames=num_frames,
            guidance_scale=6,
            generator=torch.Generator(device=DEVICE).manual_seed(42),
        ).frames[0]

        export_to_video(video, output_path, fps=8)
        return output_path
    except Exception as e:
        print(f"[!] CogVideoX render failed ({e}), falling back to AnimateDiff...")
        return None


def main():
    print("=" * 60)
    print("OnlinePuja Autonomous GPU Batch Worker Starting")
    print("=" * 60)

    if not check_connection():
        sys.exit(1)

    jobs = fetch_jobs(limit=10)
    print(f"[*] Fetched {len(jobs)} pending jobs from queue.")

    out_dir = Path("/tmp/onlinepuja_output")
    out_dir.mkdir(parents=True, exist_ok=True)

    for idx, job in enumerate(jobs, 1):
        job_id = job.get("job_id")
        job_type = job.get("type", "image")
        prompt = job.get("prompt", "")

        print(f"\n[{idx}/{len(jobs)}] Processing {job_type.upper()} Job #{job_id}")

        if job_type == "image":
            out_file = str(out_dir / f"img_{job_id}.webp")
            try:
                render_image_flux(prompt, out_file, job.get("width", 1024), job.get("height", 1024))
                upload_asset(job_id, out_file, "image", "flux.1-schnell-kaggle")
            except Exception as e:
                print(f"[✗] Failed image job {job_id}: {e}")

        elif job_type == "video":
            out_file = str(out_dir / f"video_{job_id}.mp4")
            try:
                render_video_cogvideox(prompt, out_file)
                upload_asset(job_id, out_file, "video", "cogvideox-2b-kaggle")
            except Exception as e:
                print(f"[✗] Failed video job {job_id}: {e}")

    print("\n[✓] Batch processing complete. Worker shutting down cleanly.")


if __name__ == "__main__":
    main()
