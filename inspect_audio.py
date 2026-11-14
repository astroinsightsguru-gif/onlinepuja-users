#!/usr/bin/env python3
import subprocess

cmd = [
    "ffmpeg", "-i", "/home/onlinepujalive/htdocs/onlinepuja.live/public/storage/audio/shiv_aarti_kashi.mp3",
    "-t", "60",
    "-af", "silencedetect=noise=-30dB:d=0.5",
    "-f", "null", "-"
]
res = subprocess.run(cmd, capture_output=True, text=True)
for line in res.stderr.split("\n"):
    if "silence" in line or "Duration" in line:
        print(line)
