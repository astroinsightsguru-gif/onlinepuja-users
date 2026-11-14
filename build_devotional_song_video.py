#!/usr/bin/env python3
"""
Sacred Devotional Song & Lyric Video Generator for YouTube
OnlinePuja.live - Production Engine

Renders 1080p Broadcast YouTube Devotional Video with:
- Studio-quality audio (Shiv Aarti Kashi Vishwanath)
- Flawless HarfBuzz RAQM Devanagari typography
- Dual-line lyrics (Sanskrit/Hindi + English Transliteration)
- Ken Burns cinematic sanctum camera zoom
- Transparent OnlinePuja emblem watermark
"""

import os
import subprocess
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

BASE_DIR = Path("/home/onlinepujalive/htdocs/onlinepuja.live")
OUTPUT_DIR = BASE_DIR / "public/storage/youtube_songs"
OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

SLIDES_DIR = OUTPUT_DIR / "slides_temp"
SLIDES_DIR.mkdir(parents=True, exist_ok=True)

LOGO_PATH = BASE_DIR / "public/storage/images/onlinepuja-logo-web.png"
FONT_DEV = "/usr/share/fonts/truetype/noto/NotoSansDevanagari-Bold.ttf"
FONT_SANS = "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"

def create_circular_logo(logo_path, size=115):
    """Creates a transparent circular branded badge for the video header"""
    try:
        logo = Image.open(logo_path).convert("RGBA")
        logo = logo.resize((size, size), Image.Resampling.LANCZOS)
        
        mask = Image.new("L", (size, size), 0)
        draw_mask = ImageDraw.Draw(mask)
        draw_mask.ellipse((0, 0, size, size), fill=255)
        
        output = Image.new("RGBA", (size + 8, size + 8), (0, 0, 0, 0))
        draw_out = ImageDraw.Draw(output)
        draw_out.ellipse((0, 0, size + 7, size + 7), fill=(11, 20, 26, 235), outline=(245, 158, 11, 220), width=3)
        
        output.paste(logo, (4, 4), mask)
        badge_path = OUTPUT_DIR / "brand_badge.png"
        output.save(badge_path, "PNG")
        return str(badge_path)
    except Exception as e:
        print("Logo badge error:", e)
        return str(logo_path)

def create_header_banner(width=520, height=48):
    """Creates a sleek sacred header banner in top right with flawless fonts"""
    img = Image.new("RGBA", (width, height), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    
    draw.rounded_rectangle([0, 0, width, height], radius=24, fill=(11, 20, 26, 220), outline=(245, 158, 11, 150), width=2)
    
    f_dev = ImageFont.truetype(FONT_DEV, size=20, layout_engine=ImageFont.Layout.RAQM)
    f_sans = ImageFont.truetype(FONT_SANS, size=18)
    
    # Pure Devanagari sacred branding pill (zero character loss)
    draw.text((28, 11), "ॐ ऑनलाइन पूजा • श्री काशी विश्वनाथ सेवा", font=f_dev, fill=(255, 255, 255, 245))
    
    header_path = OUTPUT_DIR / "header_banner.png"
    img.save(header_path, "PNG")
    return str(header_path)

def render_lyric_slide(idx, hindi_text, english_text, width=1920, height=1080):
    img = Image.new("RGBA", (width, height), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)

    banner_h = 230
    banner_y = height - banner_h - 45
    banner_w = width - 200
    banner_x = 100

    # Frosted dark obsidian pill with gold border
    draw.rounded_rectangle(
        [banner_x, banner_y, banner_x + banner_w, banner_y + banner_h],
        radius=26,
        fill=(11, 20, 26, 215),
        outline=(245, 158, 11, 180),
        width=2
    )

    f_dev = ImageFont.truetype(FONT_DEV, size=50, layout_engine=ImageFont.Layout.RAQM)
    f_en = ImageFont.truetype(FONT_SANS, size=26)

    # Hindi line
    bbox_hi = draw.textbbox((0, 0), hindi_text, font=f_dev)
    hi_w = bbox_hi[2] - bbox_hi[0]
    hi_x = (width - hi_w) // 2
    hi_y = banner_y + 38

    draw.text((hi_x + 2, hi_y + 2), hindi_text, font=f_dev, fill=(0, 0, 0, 240))
    draw.text((hi_x, hi_y), hindi_text, font=f_dev, fill=(251, 191, 36, 255))

    # English line
    if english_text:
        bbox_en = draw.textbbox((0, 0), english_text, font=f_en)
        en_w = bbox_en[2] - bbox_en[0]
        en_x = (width - en_w) // 2
        en_y = hi_y + 74

        draw.text((en_x + 1, en_y + 1), english_text, font=f_en, fill=(0, 0, 0, 200))
        draw.text((en_x, en_y), english_text, font=f_en, fill=(255, 255, 255, 235))

    slide_path = SLIDES_DIR / f"slide_{idx:03d}.png"
    img.save(slide_path, "PNG")
    return str(slide_path)

def generate_devotional_master_video():
    audio_path = str(BASE_DIR / "public/storage/audio/shiv_aarti_kashi.mp3")
    bg_image = str(BASE_DIR / "public/storage/brain_media/brain_9ooQBPAHAjgPpXMdCo_1024x1024.png")
    out_video = str(OUTPUT_DIR / "om_jai_shiv_omkara_kashi_1080p.mp4")

    # High-impact 60-second devotional segment
    lyrics = [
        {"start": 0.0,  "end": 4.5,  "hi": "॥ श्री शिव आरती - काशी विश्वनाथ धाम ॥", "en": "Shri Shiv Aarti - Kashi Vishwanath Dham"},
        {"start": 4.5,  "end": 11.5, "hi": "ॐ जय शिव ओंकारा, स्वामी जय शिव ओंकारा।", "en": "Om Jai Shiv Omkara, Swami Jai Shiv Omkara"},
        {"start": 11.5, "end": 18.5, "hi": "ब्रह्मा विष्णु सदा शिव, अर्द्धांगी धारा॥", "en": "Brahma Vishnu Sada Shiva, Ardhangi Dhara"},
        {"start": 18.5, "end": 24.5, "hi": "ॐ जय शिव ओंकारा, स्वामी जय शिव ओंकारा॥", "en": "Om Jai Shiv Omkara, Swami Jai Shiv Omkara"},
        {"start": 24.5, "end": 31.5, "hi": "एकानन चतुरानन, पंचानन राजे।", "en": "Ekanana Chaturana, Panchanana Raje"},
        {"start": 31.5, "end": 38.5, "hi": "हंसानन गरुड़ासन, वृषवाहन साजे॥", "en": "Hansanana Garudasana, Vrishavahana Saje"},
        {"start": 38.5, "end": 45.5, "hi": "ॐ जय शिव ओंकारा, स्वामी जय शिव ओंकारा॥", "en": "Om Jai Shiv Omkara, Swami Jai Shiv Omkara"},
        {"start": 45.5, "end": 52.5, "hi": "दो भुज चार चतुर्भुज, दस भुज अति सोहे।", "en": "Do Bhuja Chaar Chaturbhuja, Dasa Bhuja Ati Sohe"},
        {"start": 52.5, "end": 60.0, "hi": "त्रिगुण रूपनिरखता, त्रिभुवन जन मोहे॥", "en": "Triguna Rupanirakhata, Tribhuvana Jana Mohe"},
    ]

    total_duration = 60.0
    width = 1920
    height = 1080
    fps = 30
    total_frames = int(total_duration * fps)

    # 1. Render all slides
    slide_files = []
    for i, item in enumerate(lyrics):
        s_path = render_lyric_slide(i, item["hi"], item["en"], width, height)
        slide_files.append((s_path, item["start"], item["end"]))

    # 2. Build circular logo badge & header banner
    badge_path = create_circular_logo(LOGO_PATH, size=115)
    header_path = create_header_banner(width=520, height=48)

    # 3. Build FFmpeg command with layered inputs and filters
    input_args = [
        "-loop", "1", "-i", bg_image,
        "-i", badge_path,
        "-i", header_path,
        "-ss", "0", "-t", str(total_duration), "-i", audio_path,
    ]

    for s_path, _, _ in slide_files:
        input_args.extend(["-i", s_path])

    # Filter graph
    filter_parts = [
        f"[0:v]scale={width*2}:{height*2},"
        f"zoompan=z='min(zoom+0.0004,1.12)':d={total_frames}:x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':s={width}x{height}:fps={fps}[bg]",
        # Top-left logo badge overlay
        f"[bg][1:v]overlay=50:40[with_badge]",
        # Top-right header banner overlay
        f"[with_badge][2:v]overlay={width-570}:40[with_header]"
    ]

    current_layer = "[with_header]"
    for idx, (_, start, end) in enumerate(slide_files):
        slide_input_idx = 4 + idx
        next_layer = f"[v{idx}]"
        filter_parts.append(
            f"{current_layer}[{slide_input_idx}:v]overlay=0:0:enable='between(t,{start},{end})'{next_layer}"
        )
        current_layer = next_layer

    final_filter = ";".join(filter_parts)

    cmd = [
        "ffmpeg", "-y",
        *input_args,
        "-filter_complex", final_filter,
        "-map", current_layer,
        "-map", "3:a",
        "-c:v", "libx264",
        "-preset", "medium",
        "-crf", "18",
        "-pix_fmt", "yuv420p",
        "-c:a", "aac",
        "-b:a", "320k",
        "-t", str(total_duration),
        out_video
    ]

    print(f"🎬 Compiling 1080p Devotional Song Video with synchronized lyrics ({total_duration}s)...")
    res = subprocess.run(cmd, capture_output=True, text=True)
    if res.returncode != 0:
        print("FFmpeg Error:\n", res.stderr[-1500:])
        return False, res.stderr
    
    print(f"✓ Devotional Master Video generated successfully:\n{out_video}")
    return True, out_video

if __name__ == "__main__":
    generate_devotional_master_video()
