#!/usr/bin/env python3
"""
Sacred Devotional Song & Lyric Video Synthesizer for YouTube
OnlinePuja.live - Production Engine

Features:
- 16:9 (1920x1080) YouTube Landscape & 9:16 (1080x1920) YouTube Shorts modes
- Synchronized dual-line lyrics (Devanagari Sanskrit/Hindi + English Transliteration)
- ASS styling with golden glow, dark contrast backdrop, and HarfBuzz Devanagari font
- Cinematic Ken Burns camera zoom/pan on sacred sanctum deity visuals
- Embedded OnlinePuja sacred emblem & watermark
- Clean 1080p MP4 encoding with 320kbps pristine devotional audio
"""

import os
import sys
import json
import subprocess
from pathlib import Path

BASE_DIR = Path("/home/onlinepujalive/htdocs/onlinepuja.live")
OUTPUT_DIR = BASE_DIR / "public/storage/youtube_songs"
OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

LOGO_PATH = BASE_DIR / "public/storage/images/onlinepuja-logo-web.png"
FONT_PATH = "/usr/share/fonts/truetype/noto/NotoSansDevanagari-Bold.ttf"

def generate_ass_subtitles(lyrics_data, ass_path, is_landscape=True):
    """
    Generates an Advanced SubStation Alpha (.ass) subtitle file with
    rich styling, golden glow, and perfect Devanagari typography.
    """
    width = 1920 if is_landscape else 1080
    height = 1080 if is_landscape else 1920
    font_size_hi = 44 if is_landscape else 40
    font_size_en = 26 if is_landscape else 24
    margin_v = 110 if is_landscape else 380

    ass_content = f"""[Script Info]
Title: OnlinePuja Devotional Lyrics
ScriptType: v4.00+
PlayResX: {width}
PlayResY: {height}
WrapStyle: 0
ScaledBorderAndShadow: yes

[V4+ Styles]
Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding
Style: HindiLyrics,Noto Sans Devanagari,{font_size_hi},&H0024BFFB,&H0000FFFF,&H000B141A,&H80000000,-1,0,0,0,100,100,1,0,1,4,2,2,40,40,{margin_v + 45},1
Style: EnglishSub,Cinzel,{font_size_en},&H00FFFFFF,&H0000FFFF,&H000B141A,&H80000000,1,0,0,0,100,100,1,0,1,3,1,2,40,40,{margin_v},1

[Events]
Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text
"""
    for item in lyrics_data:
        start = item["start"]
        end = item["end"]
        hi_text = item.get("hindi", "")
        en_text = item.get("english", "")

        # Hindi lyric line
        ass_content += f"Dialogue: 0,{start},{end},HindiLyrics,,0,0,0,,{hi_text}\n"
        # English translation/transliteration line
        if en_text:
            ass_content += f"Dialogue: 0,{start},{end},EnglishSub,,0,0,0,,{en_text}\n"

    with open(ass_path, "w", encoding="utf-8") as f:
        f.write(ass_content)

def build_devotional_video(
    audio_file: str,
    bg_image: str,
    lyrics: list,
    output_filename: str,
    title: str = "Shri Shiv Mahamrityunjaya Mantra",
    is_landscape: bool = True,
    duration: float = None
):
    """
    Renders high-definition devotional lyric video
    """
    out_video = OUTPUT_DIR / output_filename
    ass_file = OUTPUT_DIR / f"{Path(output_filename).stem}.ass"

    generate_ass_subtitles(lyrics, ass_file, is_landscape)

    width = 1920 if is_landscape else 1080
    height = 1080 if is_landscape else 1920

    # FFmpeg Filter Complex:
    # 1. Image zoompan (Ken Burns slow breathing zoom)
    # 2. Dark vignette gradient at bottom so lyrics pop brightly
    # 3. Logo overlay at top corner
    # 4. ASS subtitle burn with libass (Devanagari HarfBuzz shaped)
    # 5. Audio track integration
    
    fps = 30
    dur_frames = int((duration or 30) * fps)

    filter_complex = (
        f"[0:v]scale={width*2}:{height*2},"
        f"zoompan=z='min(zoom+0.0006,1.15)':d={dur_frames}:x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':s={width}x{height}:fps={fps}[bg];"
        # Sacred bottom lyric shadow gradient
        f"[bg]drawbox=x=0:y={height-260 if is_landscape else height-550}:w={width}:h=260:color=black@0.65:t=fill[vignette];"
        # Top title & logo overlay
        f"[vignette][1:v]overlay=40:40[with_logo];"
        # Burn ASS subtitles
        f"[with_logo]ass={ass_file}[final_v]"
    )

    cmd = [
        "ffmpeg", "-y",
        "-loop", "1", "-i", bg_image,
        "-i", str(LOGO_PATH),
        "-i", audio_file,
        "-filter_complex", filter_complex,
        "-map", "[final_v]",
        "-map", "2:a",
        "-c:v", "libx264",
        "-preset", "medium",
        "-crf", "18",
        "-pix_fmt", "yuv420p",
        "-c:a", "aac",
        "-b:a", "320k",
        "-shortest",
        str(out_video)
    ]

    print(f"🎬 Rendering Devotional Lyric Video ({width}x{height})...")
    res = subprocess.run(cmd, capture_output=True, text=True)
    if res.returncode != 0:
        print("FFmpeg Error:", res.stderr[-1000:])
        return False, res.stderr
    
    print(f"✓ Video successfully generated: {out_video}")
    return True, str(out_video)

if __name__ == "__main__":
    # Test sample: Mahamrityunjaya Mantra
    sample_lyrics = [
        {"start": "0:00:00.00", "end": "0:00:04.50", "hindi": "ॐ नमः शिवाय", "english": "Om Namah Shivaya"},
        {"start": "0:00:04.50", "end": "0:00:11.00", "hindi": "ॐ त्र्यम्बकं यजामहे सुगन्धिं पुष्टिवर्धनम्", "english": "Om Tryambakam Yajamahe Sugandhim Pushti-Vardhanam"},
        {"start": "0:00:11.00", "end": "0:00:18.50", "hindi": "उर्वारुकमिव बन्धनान् मृत्योर्मुक्षीय मामृतात्॥", "english": "Urvarukamiva Bandhanan Mrityormukshiya Maamritat"},
        {"start": "0:00:18.50", "end": "0:00:25.00", "hindi": "काशी विश्वनाथ ज्योतिर्लिंगाय नमः", "english": "Salutations to Shri Kashi Vishwanath Jyotirlinga"},
    ]

    audio_path = "/home/onlinepujalive/htdocs/onlinepuja.live/public/storage/audio/master_sacred_atmosphere.mp3"
    bg_path = "/home/onlinepujalive/htdocs/onlinepuja.live/public/storage/brain_media/brain_9ooQBPAHAjgPpXMdCo_1024x1024.png"

    ok, path = build_devotional_video(
        audio_file=audio_path,
        bg_image=bg_path,
        lyrics=sample_lyrics,
        output_filename="shiva_mahamrityunjaya_lyrics_1080p.mp4",
        is_landscape=True,
        duration=25.0
    )
