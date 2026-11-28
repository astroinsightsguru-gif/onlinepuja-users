<?php

namespace App\Console\Commands;

use App\Services\EvolutionWaService;
use App\Services\VedicAstrologyEngine;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WhatsAppLifecycleCampaignCommand extends Command
{
    protected $signature = 'whatsapp:lifecycle-broadcast
        {--slot=panchang : Broadcast slot: panchang|darshan|transit|sandhya|night|partner_brief}
        {--dispatch : Actually dispatch messages via WhatsApp (default: preview only)}
        {--mobile= : Send a single test message to this phone number}
        {--limit=100 : Maximum number of devotees to broadcast to}';

    protected $description = 'Dispatch high-converting 24-hour Vedic WhatsApp lifecycle campaign (Panchang, Darshan, Transit, Sandhya, Night)';

    public function handle(EvolutionWaService $wa): int
    {
        $slot = strtolower(trim((string) $this->option('slot')));
        $dispatch = (bool) $this->option('dispatch');
        $testMobile = $this->option('mobile');
        $limit = (int) $this->option('limit');

        $this->info("🕉️ === OnlinePuja.live 24-Hour WhatsApp Lifecycle Engine [Slot: {$slot}] ===");

        // 1. Generate Slot-Specific Devotional Payload
        $payload = $this->generatePayloadForSlot($slot);

        $this->line('');
        $this->info(">>> Devotional Campaign Message Preview ({$slot}):");
        $this->comment($payload);
        $this->line('');

        // 2. Handle Single Test Mobile Mode
        if ($testMobile) {
            $cleanMobile = $wa->cleanNumber($testMobile);
            $this->warn("Sending test broadcast to: {$cleanMobile}...");

            $res = $wa->sendText($cleanMobile, $payload);
            if ($res['ok'] ?? false) {
                $this->info("✓ Test message delivered successfully to {$cleanMobile}!");
            } else {
                $this->error("✗ Failed to deliver: " . ($res['error'] ?? 'Unknown error'));
            }
            return self::SUCCESS;
        }

        // 3. Aggregate Target Devotees / Partners
        $isPartnerSlot = ($slot === 'partner_brief');
        $candidates = $this->getCandidates($isPartnerSlot);

        // Deduplicate and filter opt-outs & invalid numbers
        $optedOutPhones = [];
        try {
            if (Schema::hasTable('whatsapp_optouts')) {
                $optedOutPhones = DB::table('whatsapp_optouts')->pluck('phone')->toArray();
            }
        } catch (\Throwable $e) {}

        $blacklistedPhones = [
            '919999041935', '9999041935',
            '919305932724', '9305932724',
        ];

        $uniqueRecipients = collect();
        $seenNumbers = [];

        foreach ($candidates as $c) {
            $cleaned = $wa->cleanNumber((string) $c->phone);
            if (strlen($cleaned) < 10 || strlen($cleaned) > 13) {
                continue;
            }
            if (in_array($cleaned, $seenNumbers)) {
                continue;
            }
            if (in_array($cleaned, $optedOutPhones) || in_array(substr($cleaned, -10), $optedOutPhones)) {
                continue;
            }
            if (in_array($cleaned, $blacklistedPhones) || in_array(substr($cleaned, -10), $blacklistedPhones)) {
                continue;
            }
            $seenNumbers[] = $cleaned;
            $uniqueRecipients->push((object)[
                'name'  => !empty($c->name) ? $c->name : ($isPartnerSlot ? 'आचार्य जी' : 'भक्त'),
                'phone' => $cleaned,
            ]);
        }

        $totalRecipients = $uniqueRecipients->count();
        $this->info("Identified {$totalRecipients} eligible recipient(s) for slot '{$slot}' (excluding opt-outs).");

        if (!$dispatch) {
            $this->warn('');
            $this->comment("  [DRY-RUN MODE] Pass --dispatch to broadcast to {$totalRecipients} recipient(s).");
            return self::SUCCESS;
        }

        // 4. Check WhatsApp Instance Connection State
        $conn = $wa->getConnectionState();
        if (($conn['state'] ?? '') !== 'open') {
            $this->error("Cannot broadcast: Evolution WhatsApp instance '{$wa->getConnectionState()['state']}' is disconnected. Please re-pair your WhatsApp device first.");
            return self::FAILURE;
        }

        // 5. Anti-Ban Throttled Dispatch Loop
        $targetBatch = $uniqueRecipients->take($limit);
        $this->warn("🚀 Dispatching slot '{$slot}' broadcast to " . $targetBatch->count() . " recipient(s)...");
        $sentCount = 0;
        $failedCount = 0;

        foreach ($targetBatch as $idx => $recipient) {
            $personalized = "🕉️ *नमस्ते {$recipient->name} जी* 🙏\n\n" . $payload;

            $res = $wa->sendText($recipient->phone, $personalized);
            if ($res['ok'] ?? false) {
                $sentCount++;
                $this->line("  ✓ [{$sentCount}/" . $targetBatch->count() . "] Sent to {$recipient->phone} ({$recipient->name})");
            } else {
                $failedCount++;
                $this->warn("  ✗ Failed to send to {$recipient->phone}: " . ($res['error'] ?? 'timeout'));
            }

            // Anti-ban delay between 2.5 and 4.5 seconds
            usleep(rand(2500000, 4500000));

            // Batch cooldown pause every 50 messages
            if (($sentCount + $failedCount) % 50 === 0 && ($sentCount + $failedCount) < $targetBatch->count()) {
                $this->comment("  ⏸️ Batch cooldown pause (30 seconds) to protect WhatsApp account health...");
                sleep(30);
            }
        }

        // 6. Record Campaign Log
        try {
            if (Schema::hasTable('whatsapp_broadcast_logs')) {
                DB::table('whatsapp_broadcast_logs')->insert([
                    'campaign_type'    => 'lifecycle_' . $slot,
                    'recipients_count' => $sentCount + $failedCount,
                    'success_count'    => $sentCount,
                    'failed_count'     => $failedCount,
                    'sample_payload'   => mb_substr($payload, 0, 1000),
                    'dispatched_at'    => now(),
                ]);
            }
        } catch (\Throwable $e) {}

        $this->info("🎉 Lifecycle Campaign [{$slot}] complete! Sent: {$sentCount} | Failed: {$failedCount}");
        return self::SUCCESS;
    }

    /**
     * Gather recipients
     */
    protected function getCandidates(bool $isPartner): \Illuminate\Support\Collection
    {
        $candidates = collect();

        if ($isPartner) {
            if (Schema::hasTable('astrologers')) {
                $astros = DB::table('astrologers')
                    ->whereNotNull('contactNo')
                    ->where('contactNo', '!=', '')
                    ->select('id', 'name', 'contactNo as phone')
                    ->get();
                $candidates = $candidates->merge($astros);
            }
            return $candidates;
        }

        // Regular devotees
        if (Schema::hasTable('users')) {
            $users = DB::table('users')
                ->whereNotNull('contactNo')
                ->where('contactNo', '!=', '')
                ->select('id', 'name', 'contactNo as phone')
                ->get();
            $candidates = $candidates->merge($users);
        }

        if (Schema::hasTable('leads')) {
            $leads = DB::table('leads')
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->select('id', 'name', 'phone')
                ->get();
            $candidates = $candidates->merge($leads);
        }

        if (Schema::hasTable('whatsapp_threads')) {
            $threads = DB::table('whatsapp_threads')
                ->where('remote_jid', 'not like', '%@g.us')
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->select('id', 'name', 'phone')
                ->get();
            $candidates = $candidates->merge($threads);
        }

        return $candidates;
    }

    /**
     * Payload Router
     */
    protected function generatePayloadForSlot(string $slot): string
    {
        switch ($slot) {
            case 'darshan':
                return $this->getDarshanPayload();
            case 'transit':
                return $this->getTransitPayload();
            case 'sandhya':
                return $this->getSandhyaPayload();
            case 'night':
                return $this->getNightPayload();
            case 'partner_brief':
                return $this->getPartnerBriefPayload();
            case 'panchang':
            default:
                return $this->getPanchangPayload();
        }
    }

    /**
     * Slot 1: 04:00 AM — Brahma Muhurta Daily Panchang & 12-Rashi
     */
    protected function getPanchangPayload(): string
    {
        $now = Carbon::now('Asia/Kolkata');
        $dateFormatted = $now->translatedFormat('d F Y, l');

        $dayDeities = [
            0 => ['day' => 'रविवार', 'deity' => 'भगवान सूर्य नारायण', 'mantra' => 'ॐ सूर्याय नमः', 'upay' => 'सूर्य देव को तांबे के लोटे से अर्घ्य दें'],
            1 => ['day' => 'सोमवार', 'deity' => 'देवाधिदेव महादेव', 'mantra' => 'ॐ नमः शिवाय', 'upay' => 'शिवलिंग पर कच्चा दूध व गंगाजल चढ़ाएं'],
            2 => ['day' => 'मंगलवार', 'deity' => 'श्री संकटमोचन हनुमान', 'mantra' => 'ॐ हं हनुमते नमः', 'upay' => 'हनुमान चालीसा का पाठ करें'],
            3 => ['day' => 'बुधवार', 'deity' => 'प्रथम पूज्य श्री गणेश', 'mantra' => 'ॐ गं गणपतये नमः', 'upay' => 'गणेश जी को दूर्वा अर्पित करें'],
            4 => ['day' => 'गुरुवार', 'deity' => 'श्री हरि विष्णु व देवगुरु', 'mantra' => 'ॐ नमो भगवते वासुदेवाय', 'upay' => 'विष्णु जी को पीला चंदन लगाएं'],
            5 => ['day' => 'शुक्रवार', 'deity' => 'मां महालक्ष्मी व आद्यशक्ति', 'mantra' => 'ॐ श्रीं महालक्ष्म्यै नमः', 'upay' => 'मां लक्ष्मी को मिश्री भोग लगाएं'],
            6 => ['day' => 'शनिवार', 'deity' => 'कर्मफलदाता शनिदेव', 'mantra' => 'ॐ शं शनैश्चराय नमः', 'upay' => 'पीपल वृक्ष पर सरसों तेल का दीप जलाएं'],
        ];
        $current = $dayDeities[$now->dayOfWeek] ?? $dayDeities[1];

        $lines = [];
        $lines[] = "━━━━━━━ ✦ 🕉️ ✦ ━━━━━━━";
        $lines[] = "✨ *श्री दैनिक वैदिक पंचांग एवं १२ राशि भविष्यफल* ✨";
        $lines[] = "🛕 *श्री काशी विश्वनाथ धाम, वाराणसी*";
        $lines[] = "━━━━━━━ ✦ 🕉️ ✦ ━━━━━━━";
        $lines[] = "📅 *{$dateFormatted}*";
        $lines[] = "🚩 *वार देवता:* {$current['deity']} ({$current['day']} विशेष)";
        $lines[] = "📿 *दैनिक महामंत्र:* {$current['mantra']}";
        $lines[] = "🌿 *आज का अचूक उपाय:* {$current['upay']}";
        $lines[] = "";
        $lines[] = "🌅 *सूर्योदय:* 06:16 AM | *सूर्यास्त:* 05:13 PM";
        $lines[] = "⚡ *अभिजित मुहूर्त:* 11:22 AM – 12:05 PM (कार्य सिद्धि)";
        $lines[] = "⚠️ *राहुकाल:* 09:00 AM – 10:22 AM (शुभ कार्य वर्जित)";
        $lines[] = "";
        $lines[] = "━━━━━━━ ✦ 🪔 ✦ ━━━━━━━\n🛕 *आज का पावन संकल्प व दर्शन:*";
        $lines[] = "• 🔴 *24x7 लाइव मंदिर दर्शन व मंगला आरती:*";
        $lines[] = "👉 https://onlinepuja.live/live-darshan";
        $lines[] = "• 🔮 *आचार्य जी से 5 मिनट निःशुल्क परामर्श व कुंडली:*";
        $lines[] = "👉 https://onlinepuja.live/astrologers";
        $lines[] = "• 📿 *काशी विश्वनाथ नाम-गोत्र संकल्प पूजा:*";
        $lines[] = "👉 https://onlinepuja.live/pujas";
        $lines[] = "";
        $lines[] = "📲 *OnlinePuja मोबाइल ऐप डाउनलोड करें (दैनिक पंचांग, लाइव दर्शन व राशिफल):*";
        $lines[] = "👉 https://onlinepuja.live/download";
        $lines[] = "";
        $lines[] = "_(दैनिक पंचांग रोकने हेतु STOP लिखकर भेजें)_";

        return implode("\n", $lines);
    }

    /**
     * Slot 2: 08:30 AM — Pratah Sanctum Darshan & Aarti Reel
     */
    protected function getDarshanPayload(): string
    {
        $lines = [];
        $lines[] = "🛕 *श्री काशी विश्वनाथ मंगला आरती दर्शन* 🔱";
        $lines[] = "";
        $lines[] = "आज प्रातः काल काशी विश्वनाथ ज्योतिर्लिंग के पावन गर्भग्रह से दिव्य आरती का दर्शन करें और भगवान शिव का आशीर्वाद प्राप्त करें। 🙏";
        $lines[] = "";
        $lines[] = "🎥 *30 सेकंड दिव्य आरती वीडियो रील देखें:*";
        $lines[] = "👉 https://onlinepuja.live/shorts?id=live_kashi_vishwanath";
        $lines[] = "";
        $lines[] = "🪔 *मात्र ₹11 में अपने नाम व गोत्र से मंगला आरती में पावन दीप प्रज्वलित करें:*";
        $lines[] = "📲 इन-ऐप लाइव दर्शन व दीपदान: https://onlinepuja.live/live-darshan?feed=kashi&diya=1";
        $lines[] = "";
        $lines[] = "आज का दिन आपके और आपके परिवार के लिए मंगलमय हो! 🕉️";
        $lines[] = "_(संदेश रोकने हेतु STOP लिखकर भेजें)_";

        return implode("\n", $lines);
    }

    /**
     * Slot 3: 12:15 PM — Midday Planetary Transit & Free Consultation Hook
     */
    protected function getTransitPayload(): string
    {
        $lines = [];
        $lines[] = "⚡ *दोपहर गोचर अलर्ट: राहुकाल समाप्त, अभिजित मुहूर्त प्रारंभ* 🌟";
        $lines[] = "";
        $lines[] = "यदि आप नौकरी, पदोन्नति, विवाह, व्यापार अथवा ग्रह बाधा को लेकर चिंतित हैं, तो आज का ग्रह गोचर आपके लिए महत्वपूर्ण संकेत दे रहा है।";
        $lines[] = "";
        $lines[] = "🔮 *OnlinePuja के सिद्ध वैदिक आचार्यों से अपनी कुंडली पर परामर्श करें:*";
        $lines[] = "🎁 *प्रथम 5 मिनट का परामर्श निःशुल्क उपलब्ध है!*";
        $lines[] = "👉 तुरंत कॉल या चैट करें: https://onlinepuja.live/astrologers";
        $lines[] = "";
        $lines[] = "📲 *अपनी जन्म कुंडली निःशुल्क बनाएं व दोष देखें:*";
        $lines[] = "👉 https://onlinepuja.live/explore";
        $lines[] = "";
        $lines[] = "_(संदेश रोकने हेतु STOP लिखकर भेजें)_";

        return implode("\n", $lines);
    }

    /**
     * Slot 4: 05:30 PM — Sandhya Aarti & Special Puja Booking
     */
    protected function getSandhyaPayload(): string
    {
        $lines = [];
        $lines[] = "🌊 *दशाश्वमेध घाट, काशी — महा गंगा आरती दर्शन* 🪔";
        $lines[] = "";
        $lines[] = "काशी के पवित्र गंगा तट से विश्व-प्रसिद्ध संध्या महाआरती प्रारंभ हो रही है। दीपदान एवं शंखनाद से नकारात्मक ऊर्जा का नाश होता है।";
        $lines[] = "";
        $lines[] = "🪔 *मात्र ₹11 में दशाश्वमेध घाट गंगा आरती में अपने नाम व गोत्र से पावन दीप अर्पित करें:*";
        $lines[] = "📲 इन-ऐप लाइव गंगा आरती व दीपदान: https://onlinepuja.live/live-darshan?feed=ganga_aarti&diya=1";
        $lines[] = "";
        $lines[] = "📿 *आज की विशेष पूजा में अपने परिवार के नाम-गोत्र से संकल्प लें:*";
        $lines[] = "• श्री महामृत्युंजय आरोग्य रक्षा अनुष्ठान";
        $lines[] = "• श्री लक्ष्मी-कुबेर धन लाभ हवन";
        $lines[] = "";
        $lines[] = "🎥 पूजा का संकल्प वीडियो व पावन प्रसाद आपके घर भेजा जाएगा।";
        $lines[] = "👉 पावन संकल्प बुक करें: https://onlinepuja.live/pujas";
        $lines[] = "";
        $lines[] = "_(संदेश रोकने हेतु STOP लिखकर भेजें)_";

        return implode("\n", $lines);
    }

    /**
     * Slot 5: 09:15 PM — Vrat Alert & Bedtime Japa
     */
    protected function getNightPayload(): string
    {
        $lines = [];
        $lines[] = "🌙 *शुभ रात्रि एवं कल का पावन संदेश* 📿";
        $lines[] = "";
        $lines[] = "दिनभर के भागदौड़ के उपरांत मानसिक शांति हेतु OnlinePuja ऐप में *108 डिजिटल रुद्राक्ष माला* से महामंत्र जप करें:";
        $lines[] = "✨ *ॐ नमः शिवाय* अथवा *ॐ नमो भगवते वासुदेवाय*";
        $lines[] = "";
        $lines[] = "📱 *डिजिटल जप माला व स्वप्न शास्त्र खोलें:*";
        $lines[] = "👉 https://onlinepuja.live/explore";
        $lines[] = "";
        $lines[] = "ईश्वर आपके परिवार को सुख, शांति एवं आरोग्य प्रदान करें। शुभ रात्रि! 🕉️";
        $lines[] = "_(संदेश रोकने हेतु STOP लिखकर भेजें)_";

        return implode("\n", $lines);
    }

    /**
     * Slot 6: 06:00 AM — Pandit Partner Morning Briefing
     */
    protected function getPartnerBriefPayload(): string
    {
        $lines = [];
        $lines[] = "🛕 *प्रणाम पूज्य आचार्य जी! शुभ प्रभात* 🙏";
        $lines[] = "";
        $lines[] = "आज का पंचांग व परामर्श बुलेटिन:";
        $lines[] = "• 🚀 आपके सोशल मीडिया (FB, Insta) पर आज के रील्स व राशिफल पोस्ट शेड्यूल हो चुके हैं।";
        $lines[] = "• 🤖 आपका 24/7 वैदिक AI ट्विन एक्टिव है।";
        $lines[] = "• 🔔 कृपया ऐप में ऑनलाइन स्थिति (Online Status) ऑन रखें ताकि यजमानों के परामर्श कॉल सीधे आप तक पहुंच सकें।";
        $lines[] = "";
        $lines[] = "📲 *पार्टनर ऐप खोलें:*";
        $lines[] = "👉 https://onlinepuja.live/partner";

        return implode("\n", $lines);
    }
}
