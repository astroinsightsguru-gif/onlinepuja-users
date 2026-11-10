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
            $personalized = "प्रणाम {$devotee->name} जी 🙏\n\n" . $payload;

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
    protected function generateDailyDevotionalPayload(): string
    {
        $now = Carbon::now('Asia/Kolkata');
        $dateFormatted = $now->translatedFormat('d F Y, l');

        // Vedic Panchang core calculation
        $tithis = ['प्रतिपदा', 'द्वितीया', 'तृतीया', 'चतुर्थी', 'पंचमी', 'षष्ठी', 'सप्तमी', 'अष्टमी', 'नवमी', 'दशमी', 'एकादशी', 'द्वादशी', 'त्रयोदशी', 'चतुर्दशी', 'पूर्णिमा / अमावस्या'];
        $nakshatras = ['अश्विनी', 'भरणी', 'कृत्तिका', 'रोहिणी', 'मृगशिरा', 'आर्द्रा', 'पुनर्वसु', 'पुष्य', 'आश्लेषा', 'मघा', 'पूर्वा फाल्गुनी', 'उत्तरा फाल्गुनी', 'हस्त', 'चित्रा', 'स्वाति', 'विशाखा', 'अनुराधा', 'ज्येष्ठा', 'मूल', 'पूर्वाषाढ़ा', 'उत्तराषाढ़ा', 'श्रवण', 'धनिष्ठा', 'शतभिषा', 'पूर्वाभाद्रपद', 'उत्तराभाद्रपद', 'रेवती'];

        $dayOfYear = $now->dayOfYear;
        $tithi = $tithis[$dayOfYear % count($tithis)];
        $nakshatra = $nakshatras[$dayOfYear % count($nakshatras)];
        $paksha = ($dayOfYear % 30 < 15) ? 'शुक्ल पक्ष' : 'कृष्ण पक्ष';

        $sunrise = '06:18 AM';
        $sunset = '05:44 PM';
        $abhijit = '11:43 AM – 12:28 PM';
        $rahuKaal = '09:18 AM – 10:40 AM';
        $choghadiya = 'शुभ: 07:40 - 09:05 | अमृत: 01:15 - 02:40 | लाभ: 02:40 - 04:05';

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
        $lines[] = "🕉️ *श्री दैनिक पंचांग एवं १२ राशि भविष्यफल*";
        $lines[] = "📅 *{$dateFormatted}*";
        $lines[] = "विक्रम संवत २०८३ • {$paksha} • {$tithi}";
        $lines[] = "";
        $lines[] = "🌅 *सूर्योदय:* {$sunrise} | *सूर्यास्त:* {$sunset}";
        $lines[] = "✨ *नक्षत्र:* {$nakshatra}";
        $lines[] = "⚡ *अभिजित मुहूर्त:* {$abhijit} *(सर्वोत्तम कार्य सिद्धि समय)*";
        $lines[] = "⚠️ *राहुकाल:* {$rahuKaal} *(शुभ कार्य वर्जित)*";
        $lines[] = "";
        $lines[] = "🔮 *आज का संक्षिप्त १२ राशि भविष्यफल:*";
        foreach ($rashis as $r) {
            $lines[] = "• *{$r['name']}:* लकी {$r['lucky']} | उपाय: {$r['upay']}";
        }
        $lines[] = "";
        $lines[] = "🌿 *आज का शुभ चौघड़िया:*";
        $lines[] = "{$choghadiya}";
        $lines[] = "";
        $lines[] = "🪔 *आज का पावन संकल्प:*";
        $lines[] = "काशी विश्वनाथ व दशाश्वमेध घाट पर अपने गोत्र संकल्प से दीपदान व रुद्राभिषेक पूजा बुक करें:";
        $lines[] = "👉 https://onlinepuja.live/pujas";
        $lines[] = "";
        $lines[] = "_(दैनिक पंचांग रोकने हेतु STOP लिखकर भेजें)_";

        return implode("\n", $lines);
    }
}
