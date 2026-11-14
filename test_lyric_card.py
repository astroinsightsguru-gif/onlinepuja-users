#!/usr/bin/env python3
"""
Test script to render a 100% pixel-perfect Devanagari Lyric Card with RAQM HarfBuzz
"""
import os
from PIL import Image, ImageDraw, ImageFont

FONT_DEV = "/usr/share/fonts/truetype/noto/NotoSansDevanagari-Bold.ttf"
FONT_SANS = "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"

def render_lyric_slide(hindi_text, english_text, width=1920, height=1080):
    img = Image.new("RGBA", (width, height), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)

    # Frosted dark banner at bottom
    banner_h = 240
    banner_y = height - banner_h - 40
    banner_w = width - 160
    banner_x = 80

    # Draw rounded dark card
    draw.rounded_rectangle(
        [banner_x, banner_y, banner_x + banner_w, banner_y + banner_h],
        radius=24,
        fill=(11, 20, 26, 210),
        outline=(245, 158, 11, 140),
        width=2
    )

    # Fonts with RAQM HarfBuzz
    f_dev = ImageFont.truetype(FONT_DEV, size=52, layout_engine=ImageFont.Layout.RAQM)
    f_en = ImageFont.truetype(FONT_SANS, size=28)

    # 1. Hindi line (Gold with soft drop shadow)
    bbox_hi = draw.textbbox((0, 0), hindi_text, font=f_dev)
    hi_w = bbox_hi[2] - bbox_hi[0]
    hi_x = (width - hi_w) // 2
    hi_y = banner_y + 40

    # Shadow
    draw.text((hi_x + 2, hi_y + 2), hindi_text, font=f_dev, fill=(0, 0, 0, 230))
    # Golden text
    draw.text((hi_x, hi_y), hindi_text, font=f_dev, fill=(251, 191, 36, 255))

    # 2. English transliteration line
    bbox_en = draw.textbbox((0, 0), english_text, font=f_en)
    en_w = bbox_en[2] - bbox_en[0]
    en_x = (width - en_w) // 2
    en_y = hi_y + 76

    # English text
    draw.text((en_x + 1, en_y + 1), english_text, font=f_en, fill=(0, 0, 0, 200))
    draw.text((en_x, en_y), english_text, font=f_en, fill=(255, 255, 255, 230))

    # Save
    out_path = "/home/onlinepujalive/htdocs/onlinepuja.live/public/storage/youtube_songs/sample_lyric_slide.png"
    img.save(out_path, "PNG")
    print(f"✓ Saved sample lyric slide to {out_path}")
    return out_path

if __name__ == "__main__":
    render_lyric_slide(
        "ॐ जय शिव ओंकारा, स्वामी जय शिव ओंकारा।",
        "Om Jai Shiv Omkara, Swami Jai Shiv Omkara"
    )
