<?php

namespace App\Console\Commands;

use App\Services\EvolutionWaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsAppDailyPanchangCommand extends Command
{
    protected $signature = 'whatsapp:daily-panchang-broadcast
        {--dispatch : Actually dispatch messages via WhatsApp (default: preview only)}
        {--mobile= : Send a single test message to this phone number}
        {--limit=100 : Maximum number of devotees to broadcast to}';

    protected $description = 'Generate and broadcast daily Vedic Hindi Panchang & 12-Rashi Dainik Rashifal on WhatsApp';

    public function handle(EvolutionWaService $wa): int
    {
        $dispatch = (bool) $this->option('dispatch');
        $testMobile = $this->option('mobile');
        $limit = (int) $this->option('limit');

        $this->info("🕉️ === OnlinePuja.live Daily Panchang & Rashifal WhatsApp Engine ===");

        // 1. Synthesize Today's Sacred Vedic Panchang & Rashifal Payload
        $payload = $this->generateDailyDevotionalPayload();

        $this->line('');
        $this->info(">>> Sample Devotional Broadcast Copy:");
        $this->comment($payload);
        $this->line('');

        // 2. Handle Single Test Mobile Mode
        if ($testMobile) {
            $cleanMobile = $wa->cleanNumber($testMobile);
            $this->warn("Sending test broadcast to single number: {$cleanMobile}...");

            $res = $wa->sendText($cleanMobile, $payload);
            if ($res['ok'] ?? false) {
                $this->info("✓ Test message delivered successfully!");
            } else {
                $this->error("✗ Failed to deliver: " . ($res['error'] ?? 'Unknown error'));
            }
            return self::SUCCESS;
        }

        // 3. Aggregate Eligible Devotee Contacts
        $optedOutPhones = DB::table('whatsapp_optouts')->pluck('phone')->toArray();

        $candidates = collect();

        // From Users table
        $users = DB::table('users')
            ->whereNotNull('contactNo')
            ->where('contactNo', '!=', '')
            ->select('id', 'name', 'contactNo as phone')
            ->get();
        $candidates = $candidates->merge($users);

        // From Leads table
        $leads = DB::table('leads')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->select('id', 'name', 'phone')
            ->get();
        $candidates = $candidates->merge($leads);

        // From WhatsApp Threads (inbound devotee chats & Meta ad leads)
        $threads = DB::table('whatsapp_threads')
            ->where('remote_jid', 'not like', '%@g.us')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->select('id', 'name', 'phone')
            ->get();
        $candidates = $candidates->merge($threads);

        // Deduplicate and filter opt-outs & invalid numbers
        $uniqueDevotees = collect();
        $seenNumbers = [];

        foreach ($candidates as $c) {
            $cleaned = $wa->cleanNumber($c->phone);
            if (strlen($cleaned) < 10 || strlen($cleaned) > 13) {
                continue;
            }
            if (in_array($cleaned, $seenNumbers)) {
                continue;
            }
            if (in_array($cleaned, $optedOutPhones) || in_array(substr($cleaned, -10), $optedOutPhones)) {
                continue;
            }
            $seenNumbers[] = $cleaned;
            $uniqueDevotees->push((object)[
                'name'  => $c->name ?: 'भक्त',
                'phone' => $cleaned,
            ]);
        }

        $totalDevotees = $uniqueDevotees->count();
        $this->info("Identified {$totalDevotees} eligible active devotee(s) across database (excluding opt-outs).");

        if (!$dispatch) {
            $this->warn('');
            $this->comment("  [DRY-RUN MODE] Pass --dispatch to broadcast to {$totalDevotees} devotees.");
            return self::SUCCESS;
        }

        // 4. Check WhatsApp Instance Connection State
        $conn = $wa->getConnectionState();
        if (($conn['state'] ?? '') !== 'open') {
            $this->error("Cannot broadcast: Evolution WhatsApp instance '{$wa->getConnectionState()['state']}' is disconnected. Please re-pair your WhatsApp device first.");
            return self::FAILURE;
        }

        // 5. Anti-Ban Throttled Dispatch Loop
        $this->warn("🚀 Dispatching morning broadcast to {$totalDevotees} devotee(s)...");
        $sentCount = 0;
        $failedCount = 0;

        foreach ($uniqueDevotees->take($limit) as $idx => $devotee) {
            // Personalized greeting
            $personalized = "🕉️ *ॐ नमः शिवाय* 🙏\n\n" . $payload;

            $res = $wa->sendText($devotee->phone, $personalized);
            if ($res['ok'] ?? false) {
                $sentCount++;
                $this->line("  ✓ [{$sentCount}/{$totalDevotees}] Sent to {$devotee->phone} ({$devotee->name})");
            } else {
                $failedCount++;
                $this->warn("  ✗ Failed to send to {$devotee->phone}: " . ($res['error'] ?? 'timeout'));
            }

            // Anti-ban delay between 2.5 and 4.5 seconds
            usleep(rand(2500000, 4500000));

            // Batch cooldown every 50 messages
            if (($sentCount + $failedCount) % 50 === 0 && ($sentCount + $failedCount) < $totalDevotees) {
                $this->comment("  ⏸️ Batch cooldown pause (30 seconds) to ensure account safety...");
                sleep(30);
            }
        }

        // 6. Record Campaign Log
        DB::table('whatsapp_broadcast_logs')->insert([
            'campaign_type'    => 'daily_panchang_rashifal',
            'recipients_count' => $sentCount + $failedCount,
            'success_count'    => $sentCount,
            'failed_count'     => $failedCount,
            'sample_payload'   => mb_substr($payload, 0, 1000),
            'dispatched_at'    => now(),
        ]);

        $this->info("🎉 Morning Devotional Broadcast complete! Sent: {$sentCount} | Failed: {$failedCount}");
        return self::SUCCESS;
    }

    /**
     * Synthesize authentic Vedic Hindi Panchang & 12-Rashi predictions
     */

    protected function translateTithiToHindi(string $tithi): string
    {
        $map = [
            "Pratipada" => "प्रतिपदा", "Dvitiya" => "द्वितीया", "Tritiya" => "तृतीया",
            "Chaturthi" => "चतुर्थी", "Panchami" => "पंचमी", "Shashthi" => "षष्ठी",
            "Saptami" => "सप्तमी", "Ashtami" => "अष्टमी", "Navami" => "नवमी",
            "Dashami" => "दशमी", "Ekadashi" => "एकादशी", "Dvadashi" => "द्वादशी",
            "Trayodashi" => "त्रयोदशी", "Chaturdashi" => "चतुर्दशी", "Purnima" => "पूर्णिमा",
            "Amavasya" => "अमावस्या", "Shukla Paksha" => "शुक्ल पक्ष", "Krishna Paksha" => "कृष्ण पक्ष"
        ];
        return strtr($tithi, $map);
    }

    protected function translateNakshatraToHindi(string $nak): string
    {
        $map = [
            "Ashwini" => "अश्विनी", "Bharani" => "भरणी", "Krittika" => "कृत्तिका",
            "Rohini" => "रोहिणी", "Mrigashira" => "मृगशिरा", "Ardra" => "आर्द्रा",
            "Punarvasu" => "पुनर्वसु", "Pushya" => "पुष्य", "Ashlesha" => "आश्लेषा",
            "Magha" => "मघा", "Purva Phalguni" => "पूर्वा फाल्गुनी", "Uttara Phalguni" => "उत्तरा फाल्गुनी",
            "Hasta" => "हस्त", "Chitra" => "चित्रा", "Swati" => "स्वाति",
            "Vishakha" => "विशाखा", "Anuradha" => "अनुराधा", "Jyeshtha" => "ज्येष्ठा",
            "Mula" => "मूल", "Purva Ashadha" => "पूर्वाषाढ़ा", "Uttara Ashadha" => "उत्तराषाढ़ा",
            "Shravana" => "श्रवण", "Dhanishta" => "धनिष्ठा", "Shatabhisha" => "शतभिषा",
            "Purva Bhadrapada" => "पूर्वाभाद्रपद", "Uttara Bhadrapada" => "उत्तराभाद्रपद", "Revati" => "रेवती"
        ];
        return $map[$nak] ?? $nak;
    }

    protected function generateDailyDevotionalPayload(): string
    {
        $now = Carbon::now('Asia/Kolkata');
        $dateFormatted = $now->translatedFormat('d F Y, l');

        // Day of Week Deity & Auspicious Alignment
        $dayOfWeek = $now->dayOfWeek; // 0 = Sunday, 1 = Monday, etc.
        $dayDeities = [
            0 => ['day' => 'रविवार', 'deity' => 'भगवान सूर्य नारायण', 'mantra' => 'ॐ सूर्याय नमः', 'blessing' => 'आरोग्य, तेज व मान-सम्मान'],
            1 => ['day' => 'सोमवार', 'deity' => 'देवाधिदेव महादेव', 'mantra' => 'ॐ नमः शिवाय', 'blessing' => 'मानसिक शांति, आरोग्य व संकट मुक्ति'],
            2 => ['day' => 'मंगलवार', 'deity' => 'श्री संकटमोचन हनुमान', 'mantra' => 'ॐ हं हनुमते नमः', 'blessing' => 'भय मुक्ति, साहस व कार्य सिद्धि'],
            3 => ['day' => 'बुधवार', 'deity' => 'प्रथम पूज्य श्री गणेश', 'mantra' => 'ॐ गं गणपतये नमः', 'blessing' => 'विघ्न नाश, बुद्धि व व्यापार वृद्धि'],
            4 => ['day' => 'गुरुवार', 'deity' => 'श्री हरि विष्णु व देवगुरु', 'mantra' => 'ॐ नमो भगवते वासुदेवाय', 'blessing' => 'ज्ञान, सौभाग्य व वैवाहिक सुख'],
            5 => ['day' => 'शुक्रवार', 'deity' => 'मां महालक्ष्मी व आद्यशक्ति', 'mantra' => 'ॐ श्रीं महालक्ष्म्यै नमः', 'blessing' => 'स्थिर धन, ऐश्वर्य व समृद्धि'],
            6 => ['day' => 'शनिवार', 'deity' => 'कर्मफलदाता शनिदेव', 'mantra' => 'ॐ शं शनैश्चराय नमः', 'blessing' => 'ग्रह बाधा निवारण व न्याय रक्षा'],
        ];
        $currentDeity = $dayDeities[$dayOfWeek] ?? $dayDeities[1];

        // Today or Upcoming Festival Check
        $festivalNotice = "";
        try {
            $todayFest = DB::table('hindu_festivals')->whereDate('date', $now->toDateString())->first();
            if ($todayFest) {
                $festivalNotice = "🎉 *आज का पावन महापर्व:* {$todayFest->name} ({$todayFest->deity}) 🪔\n";
            }
        } catch (\Throwable $e) {}

        // Vedic Panchang core calculation
        // Astronomical Vedic Calculation for Varanasi (Lat: 25.3176, Lon: 82.9739)
        try {
            $panchang = \App\Services\VedicAstrologyEngine::calculatePanchang(
                $now->format('Y-m-d'),
                $now->format('H:i'),
                25.3176,
                82.9739
            );
            $chog = \App\Services\VedicAstrologyEngine::calculateChoghadiya(
                $now->format('Y-m-d'),
                25.3176,
                82.9739
            );

            $sunrise = $panchang['sunrise'] ?? '06:14 AM';
            $sunset = $panchang['sunset'] ?? '05:12 PM';
            $rahuKaal = $panchang['rahukaal'] ?? '10:20 AM – 11:43 AM';
            $abhijit = $chog['abhijitMuhurat'] ?? '11:21 AM – 12:05 PM';
            $rawTithi = $panchang['tithi'] ?? 'Chaturthi (Shukla Paksha)';
            $tithi = $this->translateTithiToHindi($rawTithi);
            $nakshatra = $this->translateNakshatraToHindi($panchang['nakshatra'] ?? 'Mula');
            $samvat = $panchang['vikram_samvat'] ?? '2083';
            $masa = $panchang['masa'] ?? 'Margashirsha';

            // Filter auspicious choghadiya slots only
            $shubhSlots = [];
            foreach (($chog['choghadiya'] ?? []) as $slot) {
                if (!empty($slot['isAuspicious'])) {
                    $slotName = strtr($slot['name'], ['Labh' => 'लाभ', 'Amrit' => 'अमृत', 'Shubh' => 'शुभ', 'Char' => 'चर']);
                    $shubhSlots[] = "{$slotName}: {$slot['startTime']} - {$slot['endTime']}";
                }
            }
            $choghadiya = !empty($shubhSlots) ? implode(' | ', array_slice($shubhSlots, 0, 3)) : 'शुभ मुहूर्त: 08:58 AM - 10:20 AM';
        } catch (\Throwable $e) {
            $sunrise = '06:14 AM'; $sunset = '05:12 PM';
            $rahuKaal = '10:20 AM – 11:43 AM'; $abhijit = '11:21 AM – 12:05 PM';
            $tithi = 'शुक्ल पक्ष चतुर्थी'; $nakshatra = 'मूल'; $samvat = '2083';
            $choghadiya = 'लाभ: 07:36 AM - 08:58 AM | अमृत: 08:58 AM - 10:20 AM';
        }

        $rashis = [
            ['name' => 'मेष (Aries)', 'lucky' => '9, लाल', 'upay' => 'हनुमान चालीसा का पाठ करें'],
            ['name' => 'वृषभ (Taurus)', 'lucky' => '6, श्वेत', 'upay' => 'मां लक्ष्मी को मिश्री भोग लगाएं'],
            ['name' => 'मिथुन (Gemini)', 'lucky' => '5, हरा', 'upay' => 'गणेश जी को दूर्वा अर्पित करें'],
            ['name' => 'कर्क (Cancer)', 'lucky' => '2, चांदी', 'upay' => 'शिवलिंग पर कच्चा दूध व गंगाजल चढ़ाएं'],
            ['name' => 'सिंह (Leo)', 'lucky' => '1, स्वर्ण', 'upay' => 'सूर्य देव को तांबे के लोटे से अर्घ्य दें'],
            ['name' => 'कन्या (Virgo)', 'lucky' => '5, हरा', 'upay' => 'तुलसी जी में जल अर्पित करें'],
            ['name' => 'तुला (Libra)', 'lucky' => '7, गुलाबी', 'upay' => 'कन्याओं को फल अथवा मिष्ठान दें'],
            ['name' => 'वृश्चिक (Scorpio)', 'lucky' => '9, मैरून', 'upay' => 'सुंदरकांड का पाठ करें'],
            ['name' => 'धनु (Sagittarius)', 'lucky' => '3, पीला', 'upay' => 'भगवान विष्णु को पीला चंदन लगाएं'],
            ['name' => 'मकर (Capricorn)', 'lucky' => '8, नीला', 'upay' => 'पीपल के वृक्ष पर सरसों तेल का दीपक जलाएं'],
            ['name' => 'कुंभ (Aquarius)', 'lucky' => '4, आसमानी', 'upay' => 'ॐ शं शनैश्चराय नमः का ११ बार जप करें'],
            ['name' => 'मीन (Pisces)', 'lucky' => '3, केसरिया', 'upay' => 'मस्तक पर केसर अथवा हल्दी का तिलक लगाएं'],
        ];

        $lines = [];
        $lines[] = "━━━━━━━ ✦ 🕉️ ✦ ━━━━━━━";
        $lines[] = "✨ *श्री दैनिक वैदिक पंचांग एवं १२ राशि भविष्यफल* ✨";
        $lines[] = "🛕 *श्री काशी विश्वनाथ धाम, वाराणसी*";
        $lines[] = "━━━━━━━ ✦ 🕉️ ✦ ━━━━━━━";
        if (!empty($festivalNotice)) {
            $lines[] = $festivalNotice;
        }
        $lines[] = "📅 *{$dateFormatted}*";
        $lines[] = "🚩 *वार देवता:* {$currentDeity['deity']} ({$currentDeity['day']} विशेष)";
        $lines[] = "📿 *दैनिक महामंत्र:* {$currentDeity['mantra']}";
        $lines[] = "विक्रम संवत {$samvat} • {$tithi}";
        $lines[] = "";
        $lines[] = "🌅 *सूर्योदय:* {$sunrise} | *सूर्यास्त:* {$sunset}";
        $lines[] = "✨ *नक्षत्र:* {$nakshatra}";
        $lines[] = "⚡ *अभिजित मुहूर्त:* {$abhijit} *(सर्वोत्तम कार्य सिद्धि समय)*";
        $lines[] = "⚠️ *राहुकाल:* {$rahuKaal} *(शुभ कार्य वर्जित)*";
        $lines[] = "";
        $lines[] = "━━━━━━━ ✦ 🔮 ✦ ━━━━━━━\n⭐ *आज का १२ राशि फल एवं दैनिक अचूक उपाय:*";
        foreach ($rashis as $r) {
            $lines[] = "• *{$r['name']}:* लकी {$r['lucky']} | उपाय: {$r['upay']}";
        }
        $lines[] = "";
        $lines[] = "🌿 *आज का शुभ चौघड़िया:*";
        $lines[] = "{$choghadiya}";
        $lines[] = "";
        $lines[] = "━━━━━━━ ✦ 🪔 ✦ ━━━━━━━\n🛕 *आज का पावन काशी विश्वनाथ संकल्प:*";
        $lines[] = "काशी विश्वनाथ व दशाश्वमेध घाट पर अपने गोत्र संकल्प से दीपदान व रुद्राभिषेक पूजा बुक करें:";
        $lines[] = "👉 https://onlinepuja.live/pujas";
        $lines[] = "";
        
        $lines[] = "_(दैनिक पंचांग रोकने हेतु STOP लिखकर भेजें)_";

        return implode("\n", $lines);
    }
}
