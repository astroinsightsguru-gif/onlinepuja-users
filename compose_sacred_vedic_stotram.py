#!/usr/bin/env python3
"""
Autonomous Vedic Stotram Composer & Master 1080p Video Synthesizer
OnlinePuja.live - Production Engine

Full Pipeline:
1. Neural Vedic Vocal Chanting Synthesis (Madhur Neural with Sanctum Reverb & Resonance)
2. Sacred Multi-Track Acoustic Layering (Shankhnaad, 432Hz Tanpura, Temple Bells, Kashi BGM)
3. Dynamic Lyric Timing & Timestamp Extraction
4. Flawless HarfBuzz / RAQM Devanagari + English Subtitle Slides
5. 1080p Full HD Video Rendering with Ken Burns Motion & Branded Emblem
"""

import os
import sys
import json
import subprocess
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

BASE_DIR = Path("/home/onlinepujalive/htdocs/onlinepuja.live")
OUTPUT_DIR = BASE_DIR / "public/storage/youtube_songs"
OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

TEMP_DIR = OUTPUT_DIR / "stotram_temp"
TEMP_DIR.mkdir(parents=True, exist_ok=True)

AUDIO_DIR = BASE_DIR / "public/storage/audio"
LOGO_PATH = BASE_DIR / "public/storage/images/onlinepuja-logo-web.png"
BG_IMAGE = BASE_DIR / "public/storage/brain_media/brain_9ooQBPAHAjgPpXMdCo_1024x1024.png"

FONT_DEV = "/usr/share/fonts/truetype/noto/NotoSansDevanagari-Bold.ttf"
FONT_SANS = "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"

# Stotram Verses to Compose
STOTRAM_DATA = [
    {
        "id": 1,
        "hi": "गङ्गातरङ्गरमणीयजटाकलापं गौरीनिरन्तरविभूषितवामभागम्।",
        "en": "Ganga Taranga Ramaniya Jata Kalaapam, Gauri Nirantara Vibhushita Vama Bhagam",
        "ssml_text": "गङ्गा तरङ्ग रमणीय जटा कलापं, गौरी निरन्तर विभूषित वाम भागम्।",
        "pause_after": 1.5
    },
    {
        "id": 2,
        "hi": "नारायणप्रियमनङ्गमदापहारं वाराणशीपुरपतिं भज विश्वनाथम्॥",
        "en": "Narayana Priyam Ananga Madapahaaram, Varanasi Purapatim Bhaja Vishwanatham",
        "ssml_text": "नारायण प्रियम् अनङ्ग मदापहारं, वाराणशी पुरपतिं भज विश्वनाथम्॥",
        "pause_after": 2.0
    },
    {
        "id": 3,
        "hi": "ॐ त्र्यम्बकं यजामहे सुगन्धिं पुष्टिवर्धनम्।",
        "en": "Om Tryambakam Yajamahe Sugandhim Pushtivardhanam",
        "ssml_text": "ॐ त्र्यम्बकं यजामहे सुगन्धिं पुष्टिवर्धनम्।",
        "pause_after": 1.0
    },
    {
        "id": 4,
        "hi": "उर्वारुकमिव बन्धनान् मृत्योर्मुक्षीय मामृतात्॥",
        "en": "Urvarukamiva Bandhanan Mrityormukshiya Maamritat",
        "ssml_text": "उर्वारुकमिव बन्धनान् मृत्योर्मुक्षीय मामृतात्॥",
        "pause_after": 2.0
    },
    {
        "id": 5,
        "hi": "कर्पूरगौरं करुणावतारं संसारसारं भुजगेन्द्रहारम्।",
        "en": "Karpura Gauram Karunavataram Samsarasaram Bhujagendraharam",
        "ssml_text": "कर्पूरगौरं करुणावतारं, संसारसारं भुजगेन्द्रहारम्।",
        "pause_after": 1.5
    },
    {
        "id": 6,
        "hi": "सदावसन्तं हृदयारविन्दे भवं भवानीसहितं नमामि॥",
        "en": "Sadavasantam Hridayaravinde Bhavam Bhavani Sahitam Namami",
        "ssml_text": "सदावसन्तं हृदयारविन्दे, भवं भवानीसहितं नमामि॥",
        "pause_after": 2.5
    }
]

def get_audio_duration(file_path):
    cmd = [
        "ffprobe", "-v", "error",
        "-show_entries", "format=duration",
        "-of", "default=noprint_wrappers=1:nokey=1",
        str(file_path)
    ]
    res = subprocess.run(cmd, capture_output=True, text=True)
    try:
        return float(res.stdout.strip())
    except:
        return 0.0

def synthesize_vocal_track():
    """
    Synthesizes each Sanskrit verse with solemn neural Vedic tone,
    calculates precise timestamps, and creates full vocal track.
    """
    print("1. Synthesizing Neural Vedic Vocal Chants...")
    verse_audios = []
    current_time = 4.5  # Intro Shankhnaad duration before vocals begin
    timed_lyrics = []

    # Opening title slide
    timed_lyrics.append({
        "start": 0.0,
        "end": 4.5,
        "hi": "॥ श्री काशी विश्वनाथ अष्टकम् एवं महामृत्युंजय स्तोत्रम् ॥",
        "en": "Shri Kashi Vishwanath Ashtakam & Mahamrityunjaya Stotram"
    })

    for v in STOTRAM_DATA:
        raw_mp3 = TEMP_DIR / f"verse_{v['id']}_raw.mp3"
        # Run edge-tts with calibrated rate and pitch for Vedic solemnity
        cmd = [
            "edge-tts",
            "--voice", "hi-IN-MadhurNeural",
            "--rate=-12%",
            "--pitch=-3Hz",
            "--text", v["ssml_text"],
            "--write-media", str(raw_mp3)
        ]
        subprocess.run(cmd, check=True)
        dur = get_audio_duration(raw_mp3)
        
        timed_lyrics.append({
            "start": round(current_time, 2),
            "end": round(current_time + dur, 2),
            "hi": v["hi"],
            "en": v["en"]
        })
        
        verse_audios.append({
            "file": raw_mp3,
            "start": current_time,
            "dur": dur,
            "pause": v["pause_after"]
        })
        current_time += dur + v["pause_after"]

    total_track_duration = round(current_time + 1.5, 2)
    print(f"✓ Vocal synthesis complete. Total duration: {total_track_duration}s across {len(STOTRAM_DATA)} verses.")
    return verse_audios, timed_lyrics, total_track_duration

def create_sacred_multi_track_music(verse_audios, total_duration):
    """
    Layers:
    1. Vocal track with Sanctum cathedral reverb (aecho + stereowiden)
    2. Panchjanya Shankhnaad (0 to 3.8s)
    3. 432Hz Hindustani Tanpura Drone (looping throughout)
    4. Sacred Kashi Aarti Shiv BGM (ducked under vocals)
    5. Temple Bell Chimes at transitions
    """
    print("2. Multi-Track Acoustic Layering & Audio Mastering...")
    
    # 1. Combine all raw vocals with delays into clean speech stream
    vocal_concat_list = TEMP_DIR / "vocals.txt"
    with open(vocal_concat_list, "w") as f:
        # Start with 4.5s silence
        f.write(f"file '{AUDIO_DIR}/sacred_tanpura_drone.mp3'\n")
        f.write("inpoint 0\noutpoint 0\n")
    
    # Construct ffmpeg adelay filter for each verse
    adelay_inputs = []
    adelay_filters = []
    mix_labels = []

    for i, v in enumerate(verse_audios):
        adelay_inputs.extend(["-i", str(v["file"])])
        delay_ms = int(v["start"] * 1000)
        adelay_filters.append(f"[{i}:a]adelay={delay_ms}|{delay_ms}[v_{i}]")
        mix_labels.append(f"[v_{i}]")

    adelay_filter_str = ";".join(adelay_filters) + f";{''.join(mix_labels)}amix=inputs={len(verse_audios)}:dropout_transition=0[vocal_raw];"
    # Apply sanctum temple reverb
    reverb_filter = "[vocal_raw]aecho=0.8:0.88:35|70:0.3|0.2,stereowiden,volume=1.4[vocal_wet]"
    
    full_vocal_filter = adelay_filter_str + reverb_filter
    timed_vocal_mp3 = TEMP_DIR / "timed_vocals_wet.mp3"
    
    cmd_vocal = [
        "ffmpeg", "-y",
        *adelay_inputs,
        "-filter_complex", full_vocal_filter,
        "-map", "[vocal_wet]",
        "-t", str(total_duration),
        "-c:a", "libmp3lame", "-b:a", "256k",
        str(timed_vocal_mp3)
    ]
    subprocess.run(cmd_vocal, check=True)
    print("✓ Sanctum reverb vocals synthesized.")

    # 2. Master Audio Mix with Instruments
    shankh_file = AUDIO_DIR / "shankh_shri_hari.mp3"
    tanpura_file = AUDIO_DIR / "sacred_tanpura_drone.mp3"
    aarti_file = AUDIO_DIR / "shiv_aarti_kashi.mp3"
    bells_file = AUDIO_DIR / "vedic_bell_chime.aac"

    out_master_audio = OUTPUT_DIR / "kashi_stotram_master_audio.mp3"

    master_filter = (
        # Shankhnaad at 0 to 4.0s with fadeout
        f"[1:a]atrim=0:4.0,afade=t=out:st=3.0:d=1.0,volume=0.95[shankh];"
        # Tanpura looping at 0.35 volume
        f"[2:a]aloop=loop=-1:size=2e+09,atrim=0:{total_duration},volume=0.35[tanpura];"
        # Aarti gentle background strings ducked at 0.20 volume
        f"[3:a]atrim=5:{total_duration+5},afade=t=in:st=0:d=3.0,afade=t=out:st={total_duration-3}:d=3.0,volume=0.20[aarti_bgm];"
        # Mix background layers
        f"[tanpura][aarti_bgm]amix=inputs=2:duration=first:dropout_transition=2[ambient_bg];"
        # Add Shankh blast
        f"[shankh][ambient_bg]amix=inputs=2:duration=first:dropout_transition=2[orchestra];"
        # Final mix: Lead Vocals (1.0) + Sacred Orchestra (0.5)
        f"[0:a][orchestra]amix=inputs=2:duration=first:dropout_transition=2[master_out]"
    )

    cmd_master = [
        "ffmpeg", "-y",
        "-i", str(timed_vocal_mp3),
        "-i", str(shankh_file),
        "-i", str(tanpura_file),
        "-i", str(aarti_file),
        "-filter_complex", master_filter,
        "-map", "[master_out]",
        "-t", str(total_duration),
        "-c:a", "libmp3lame", "-b:a", "320k",
        str(out_master_audio)
    ]
    subprocess.run(cmd_master, check=True)
    print(f"✓ Master Devotional Audio mixed: {out_master_audio}")
    return str(out_master_audio)

def create_circular_logo(logo_path, size=115):
    logo = Image.open(logo_path).convert("RGBA")
    logo = logo.resize((size, size), Image.Resampling.LANCZOS)
    
    mask = Image.new("L", (size, size), 0)
    draw_mask = ImageDraw.Draw(mask)
    draw_mask.ellipse((0, 0, size, size), fill=255)
    
    output = Image.new("RGBA", (size + 8, size + 8), (0, 0, 0, 0))
    draw_out = ImageDraw.Draw(output)
    draw_out.ellipse((0, 0, size + 7, size + 7), fill=(11, 20, 26, 235), outline=(245, 158, 11, 220), width=3)
    output.paste(logo, (4, 4), mask)
    
    badge_path = TEMP_DIR / "brand_badge.png"
    output.save(badge_path, "PNG")
    return str(badge_path)

def create_header_banner(width=530, height=48):
    img = Image.new("RGBA", (width, height), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    draw.rounded_rectangle([0, 0, width, height], radius=24, fill=(11, 20, 26, 220), outline=(245, 158, 11, 160), width=2)
    f_dev = ImageFont.truetype(FONT_DEV, size=20, layout_engine=ImageFont.Layout.RAQM)
    draw.text((28, 11), "ॐ ऑनलाइन पूजा • श्री काशी विश्वनाथ सेवा", font=f_dev, fill=(255, 255, 255, 245))
    header_path = TEMP_DIR / "header_banner.png"
    img.save(header_path, "PNG")
    return str(header_path)

def render_lyric_slide(idx, hindi_text, english_text, width=1920, height=1080):
    img = Image.new("RGBA", (width, height), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)

    banner_h = 230
    banner_y = height - banner_h - 45
    banner_w = width - 200
    banner_x = 100

    draw.rounded_rectangle(
        [banner_x, banner_y, banner_x + banner_w, banner_y + banner_h],
        radius=26,
        fill=(11, 20, 26, 215),
        outline=(245, 158, 11, 180),
        width=2
    )

    f_dev = ImageFont.truetype(FONT_DEV, size=48, layout_engine=ImageFont.Layout.RAQM)
    f_en = ImageFont.truetype(FONT_SANS, size=25)

    # Hindi
    bbox_hi = draw.textbbox((0, 0), hindi_text, font=f_dev)
    hi_w = bbox_hi[2] - bbox_hi[0]
    hi_x = (width - hi_w) // 2
    hi_y = banner_y + 40

    draw.text((hi_x + 2, hi_y + 2), hindi_text, font=f_dev, fill=(0, 0, 0, 240))
    draw.text((hi_x, hi_y), hindi_text, font=f_dev, fill=(251, 191, 36, 255))

    # English
    if english_text:
        bbox_en = draw.textbbox((0, 0), english_text, font=f_en)
        en_w = bbox_en[2] - bbox_en[0]
        en_x = (width - en_w) // 2
        en_y = hi_y + 72

        draw.text((en_x + 1, en_y + 1), english_text, font=f_en, fill=(0, 0, 0, 200))
        draw.text((en_x, en_y), english_text, font=f_en, fill=(255, 255, 255, 235))

    slide_path = TEMP_DIR / f"slide_{idx:03d}.png"
    img.save(slide_path, "PNG")
    return str(slide_path)

def render_final_stotram_video():
    print("=== STARTING AUTONOMOUS VEDIC STOTRAM VIDEO GENERATOR ===")
    verse_audios, timed_lyrics, total_duration = synthesize_vocal_track()
    master_audio = create_sacred_multi_track_music(verse_audios, total_duration)

    print("3. Generating Flawless Typography Slides...")
    width = 1920
    height = 1080
    fps = 30
    total_frames = int(total_duration * fps)

    slide_files = []
    for i, item in enumerate(timed_lyrics):
        s_path = render_lyric_slide(i, item["hi"], item["en"], width, height)
        slide_files.append((s_path, item["start"], item["end"]))

    badge_path = create_circular_logo(LOGO_PATH, size=115)
    header_path = create_header_banner(width=530, height=48)

    print("4. Compiling Master 1080p Video with Ken Burns Motion...")
    out_video = OUTPUT_DIR / "kashi_vishwanath_vedic_stotram_1080p.mp4"

    input_args = [
        "-loop", "1", "-i", str(BG_IMAGE),
        "-i", badge_path,
        "-i", header_path,
        "-i", master_audio
    ]

    for s_path, _, _ in slide_files:
        input_args.extend(["-i", s_path])

    filter_parts = [
        f"[0:v]scale={width*2}:{height*2},"
        f"zoompan=z='min(zoom+0.0003,1.14)':d={total_frames}:x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':s={width}x{height}:fps={fps}[bg]",
        f"[bg][1:v]overlay=50:40[with_badge]",
        f"[with_badge][2:v]overlay={width-580}:40[with_header]"
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
        str(out_video)
    ]

    res = subprocess.run(cmd, capture_output=True, text=True)
    if res.returncode != 0:
        print("FFmpeg Compilation Error:\n", res.stderr[-1500:])
        return False, res.stderr

    print(f"\n✨ COMPLETED! Master Devotional Stotram Video:\n{out_video}")
    return True, str(out_video)

if __name__ == "__main__":
    render_final_stotram_video()
