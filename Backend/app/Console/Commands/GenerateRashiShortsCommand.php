<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateRashiShortsCommand extends Command
{
    protected $signature = 'video:generate-rashi-shorts {--sign=Aries} {--output=}';
    protected $description = 'Generate 9:16 vertical video shorts using FFmpeg for YouTube Shorts & Instagram Reels';

    public function handle()
    {
        $sign = ucfirst($this->option('sign') ?: 'Aries');
        $date = Carbon::now()->format('d M Y');
        $this->info("🎬 Generating 9:16 vertical video reel for [{$sign}] on {$date}...");

        $outputDir = public_path('shorts');
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $filename = 'rashi_' . strtolower($sign) . '_' . date('Ymd') . '.mp4';
        $outputPath = $outputDir . '/' . $filename;

        // Fetch or create horoscope quote
        $rashiQuotes = [
            'Aries' => ['Lord Hanuman blessings guide your career today.', 'Lucky Color: Saffron', 'Lucky No: 9', 'Mantra: Om Hanumate Namah'],
            'Taurus' => ['Goddess Lakshmi brings peace and financial abundance.', 'Lucky Color: White', 'Lucky No: 6', 'Mantra: Om Shreem Mahalakshmiyei Namah'],
            'Gemini' => ['Lord Ganesha removes obstacles in business negotiations.', 'Lucky Color: Green', 'Lucky No: 5', 'Mantra: Om Gam Ganapataye Namah'],
            'Cancer' => ['Lord Shiva bestows inner calm and emotional strength.', 'Lucky Color: Silver', 'Lucky No: 2', 'Mantra: Om Namah Shivaya'],
            'Leo' => ['Surya Dev empowers leadership and vitality today.', 'Lucky Color: Gold', 'Lucky No: 1', 'Mantra: Om Suryaya Namah'],
            'Virgo' => ['Lord Vishnu grants wisdom and flawless analytical decisions.', 'Lucky Color: Emerald', 'Lucky No: 5', 'Mantra: Om Namo Bhagavate Vasudevaya'],
            'Libra' => ['Maa Durga grants balance and harmonious partnerships.', 'Lucky Color: Rose', 'Lucky No: 7', 'Mantra: Om Dum Durgayei Namah'],
            'Scorpio' => ['Lord Kartikeya grants courage to conquer difficult hurdles.', 'Lucky Color: Crimson', 'Lucky No: 9', 'Mantra: Om Saravanabhavaya Namah'],
            'Sagittarius' => ['Brihaspati Dev blesses higher learning and good fortune.', 'Lucky Color: Yellow', 'Lucky No: 3', 'Mantra: Om Gurave Namah'],
            'Capricorn' => ['Lord Shani blesses disciplined effort with lasting rewards.', 'Lucky Color: Indigo', 'Lucky No: 8', 'Mantra: Om Sham Shanaicharaya Namah'],
            'Aquarius' => ['Lord Shiva illuminates unique innovations and community bonds.', 'Lucky Color: Cyan', 'Lucky No: 4', 'Mantra: Om Namah Shivaya'],
            'Pisces' => ['Lord Vishnu showers divine intuition and spiritual peace.', 'Lucky Color: Golden Yellow', 'Lucky No: 3', 'Mantra: Om Namo Narayanaya'],
        ];

        $info = $rashiQuotes[$sign] ?? $rashiQuotes['Aries'];
        $headline = "DAILY HOROSCOPE - {$sign}";
        $subtext = str_replace([':', "'"], [' - ', ''], $info[0]);
        $meta = str_replace([':', "'"], [' - ', ''], "{$info[1]}  -  {$info[2]}");
        $mantra = str_replace([':', "'"], [' - ', ''], $info[3]);
        $cta = "OnlinePuja.live - Book Vedic Darshan";

        $ffmpeg = '/usr/bin/ffmpeg';
        if (!file_exists($ffmpeg)) {
            $ffmpeg = 'ffmpeg';
        }

        $filters = "[0:v]drawtext=text='ONLINEPUJA.LIVE':fontcolor=0xf59e0b:fontsize=48:x=(w-text_w)/2:y=280," .
                   "drawtext=text='TODAY RASHI FAL':fontcolor=0xffffff:fontsize=36:x=(w-text_w)/2:y=360," .
                   "drawtext=text='{$headline}':fontcolor=0xfbbf24:fontsize=56:x=(w-text_w)/2:y=550," .
                   "drawtext=text='{$date}':fontcolor=0xe5e7eb:fontsize=32:x=(w-text_w)/2:y=630," .
                   "drawtext=text='{$subtext}':fontcolor=0xffffff:fontsize=36:x=(w-text_w)/2:y=850," .
                   "drawtext=text='{$meta}':fontcolor=0xfcd34d:fontsize=40:x=(w-text_w)/2:y=1050," .
                   "drawtext=text='SACRED MANTRA':fontcolor=0x9ca3af:fontsize=30:x=(w-text_w)/2:y=1220," .
                   "drawtext=text='{$mantra}':fontcolor=0xfef08a:fontsize=44:x=(w-text_w)/2:y=1280," .
                   "drawtext=text='{$cta}':fontcolor=0xf59e0b:fontsize=38:x=(w-text_w)/2:y=1650[out]";

        $cmd = "{$ffmpeg} -y -f lavfi -i color=c=0x7c1d1d:s=1080x1920:d=8 -f lavfi -i anullsrc=channel_layout=stereo:sample_rate=44100 -filter_complex \"{$filters}\" -map \"[out]\" -map 1:a -c:v libx264 -pix_fmt yuv420p -t 8 -r 30 \"{$outputPath}\" 2>&1";

        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($outputPath)) {
            $publicUrl = url('shorts/' . $filename);
            $this->info("✅ Video Short rendered successfully (1080x1920, 8s, 30fps):");
            $this->info("🔗 URL: {$publicUrl}");
            return 0;
        }

        $this->error("FFmpeg failed with exit code {$returnCode}: " . implode("\n", array_slice($output, -10)));
        return 1;
    }
}
