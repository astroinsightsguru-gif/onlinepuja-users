<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\EvolutionWaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Unified Webhook Handler for Evolution API v2
     * Listens for MESSAGES_UPSERT, saves all inbound/outbound chats,
     * respects Human Takeover (Kill AI), and auto-triggers Vedic Acharya AI.
     */
    public function handle(Request $request, EvolutionWaService $wa)
    {
        try {
            $data = $request->json()->all() ?: $request->all();
            if (empty($data)) {
                $raw = json_decode($request->getContent(), true);
                if (is_array($raw)) $data = $raw;
            }

            $event = strtolower(str_replace('_', '.', (string)($data['event'] ?? '')));
            Log::info("Evolution WhatsApp Webhook Received", ['event' => $event]);

            // Only process message upserts
            if ($event !== 'messages.upsert' && $event !== 'messages_upsert') {
                return response()->json(['status' => 200, 'message' => 'Event acknowledged: ' . $event]);
            }

            $d = $data['data'] ?? $data;
            $key = $d['key'] ?? [];
            $remoteJid = trim((string)($key['remoteJid'] ?? ''));
            $msgId = trim((string)($key['id'] ?? ''));

            // Ignore broadcast / newsletter feeds
            if (empty($remoteJid) || str_contains($remoteJid, 'status@broadcast') || str_contains($remoteJid, '@newsletter')) {
                return response()->json(['status' => 200, 'message' => 'Broadcast or newsletter ignored']);
            }

            $isGroup = str_contains($remoteJid, '@g.us');
            $fromMe = (bool)($key['fromMe'] ?? false);
            $pushName = trim((string)($d['pushName'] ?? ($isGroup ? 'WhatsApp Group' : 'Devotee')));
            $phone = explode('@', $remoteJid)[0];
            $cleanPhone = $wa->cleanNumber($remoteJid);

            // Deduplicate webhooks if already processed
            if (!empty($msgId)) {
                $exists = DB::table('whatsapp_messages')->where('whatsapp_msg_id', $msgId)->exists();
                if ($exists) {
                    return response()->json(['status' => 200, 'message' => 'Message already processed']);
                }
            }

            // Extract message body
            $msg = $d['message'] ?? [];
            if (!empty($msg['reactionMessage']) || !empty($d['reaction'])) {
                // Drop reactions so they are not saved as chat bubbles
                return response()->json(['status' => 200, 'message' => 'Reaction ignored']);
            }

            $text = trim((string)(
                $msg['conversation'] ??
                $msg['extendedTextMessage']['text'] ??
                $msg['imageMessage']['caption'] ??
                $msg['videoMessage']['caption'] ??
                $msg['documentMessage']['caption'] ??
                ''
            ));

            $hasMedia = !empty($msg['imageMessage']) || !empty($msg['videoMessage']) || !empty($msg['documentMessage']) || !empty($msg['audioMessage']);
            $mediaType = !empty($msg['videoMessage']) ? 'video' : (!empty($msg['imageMessage']) ? 'image' : (!empty($msg['documentMessage']) ? 'document' : (!empty($msg['audioMessage']) ? 'audio' : null)));

            if (empty($text) && !$hasMedia) {
                // Drop empty protocol stubs
                return response()->json(['status' => 200, 'message' => 'Empty content ignored']);
            }

            if ($text === '[reaction]' || str_starts_with($text, '[reaction') || str_starts_with($text, '[protocol') || str_starts_with($text, '[album')) {
                return response()->json(['status' => 200, 'message' => 'Protocol/reaction ignored']);
            }

            $now = Carbon::now();

            // 1. Thread Tracking & Human Takeover State
            $thread = DB::table('whatsapp_threads')->where('remote_jid', $remoteJid)->first();
            $isAiHandling = $thread ? (bool)$thread->is_ai_handling : true;

            if ($fromMe) {
                // If typed manually from the physical phone (7007158014), auto-pause AI bot (Human Takeover)
                $isAiHandling = false;
            }

            DB::table('whatsapp_threads')->updateOrInsert(
                ['remote_jid' => $remoteJid],
                [
                    'phone' => $phone,
                    'name' => ($thread && !empty($thread->name) && $thread->name !== 'Devotee') ? $thread->name : $pushName,
                    'last_message' => $text ?: ($hasMedia ? '📎 Media message' : ''),
                    'last_message_at' => $now,
                    'unread_count' => $fromMe ? 0 : DB::raw('unread_count + 1'),
                    'is_ai_handling' => $isAiHandling,
                    'updated_at' => $now,
                ]
            );

            // 2. Persist Inbound or Outbound Message
            DB::table('whatsapp_messages')->insert([
                'thread_jid' => $remoteJid,
                'whatsapp_msg_id' => $msgId ?: null,
                'direction' => $fromMe ? 'outbound' : 'inbound',
                'message' => $text ?: ($hasMedia ? '📎 Media message' : ''),
                'media_type' => $mediaType,
                'status' => $fromMe ? 'sent' : 'delivered',
                'is_ai_generated' => false,
                'timestamp' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // If message was sent from physical phone or is a group community chat, do not auto-reply
            if ($fromMe || $isGroup) {
                return response()->json(['status' => 200, 'message' => 'Outbound or group message synced']);
            }

            // 3. HUMAN TAKEOVER GUARD: If manager turned off AI for this thread, DO NOT AUTO-REPLY!
            if (!$isAiHandling) {
                Log::info("AI Auto-reply muted for thread {$remoteJid} (Human Takeover Active)");
                return response()->json(['status' => 200, 'message' => 'Human takeover active, AI muted']);
            }

            $lower = mb_strtolower($text);

            // 4. OPT-OUT HANDLER (STOP / रोकें / UNSUBSCRIBE)
            if (in_array($lower, ['stop', 'unsubscribe', 'रोकें', 'बंद करो', 'बंद करें', 'cancel'])) {
                DB::table('whatsapp_optouts')->updateOrInsert(
                    ['phone' => $cleanPhone],
                    ['reason' => 'user_stop', 'created_at' => now()]
                );

                $optOutReply = "🕉️ *ॐ नमः शिवाय* 🙏\n\nआपकी प्रार्थना स्वीकार कर ली गई है। आपका दैनिक पंचांग व राशिफल प्रसारण बंद कर दिया गया है।\n\nपुनः शुरू करने हेतु किसी भी समय *START* लिखकर भेजें। हर हर महादेव! 🚩";
                $wa->sendText($cleanPhone, $optOutReply);
                $this->saveAiReply($remoteJid, $optOutReply);
                return response()->json(['status' => 200, 'action' => 'opted_out']);
            }

            // 5. RE-SUBSCRIBE (START / शुरू करें)
            if (in_array($lower, ['start', 'restart', 'शुरू करें', 'चालू करें'])) {
                DB::table('whatsapp_optouts')->where('phone', $cleanPhone)->delete();

                $optInReply = "🕉️ *ॐ नमः शिवाय* 🙏\n\nआपका दैनिक पंचांग व राशिफल पुनः सक्रिय कर दिया गया है। अब प्रतिदिन प्रातः ०६:३० बजे आपको शुभ मुहूर्त व दैनिक भविष्यफल प्राप्त होगा। हर हर महादेव! 🔱🚩";
                $wa->sendText($cleanPhone, $optInReply);
                $this->saveAiReply($remoteJid, $optInReply);
                return response()->json(['status' => 200, 'action' => 'opted_in']);
            }

            // 6. RASHI HOROSCOPE LOOKUP (मेष, वृषभ, मिथुन, कर्क, सिंह, कन्या, तुला, वृश्चिक, धनु, मकर, कुंभ, मीन)
            $rashiDetails = [
                'मेष' => ['name' => 'मेष (Aries)', 'element' => 'अग्नि', 'lord' => 'मंगल', 'lucky' => '9, लाल', 'prediction' => 'आज कार्यक्षेत्र में नया उत्साह रहेगा। रुके हुए कार्य गति पकड़ेंगे। परिवार का सहयोग मिलेगा।', 'upay' => 'हनुमान चालीसा का पाठ करें एवं शिवलिंग पर लाल पुष्प अर्पित करें।'],
                'वृषभ' => ['name' => 'वृषभ (Taurus)', 'element' => 'पृथ्वी', 'lord' => 'शुक्र', 'lucky' => '6, सफेद', 'prediction' => 'आर्थिक लाभ के योग बन रहे हैं। वाणी में मधुरता रखें, व्यापार में नई साझेदारी संभव है।', 'upay' => 'मां लक्ष्मी को मिश्री का भोग लगाएं एवं सुगंधित इत्र का प्रयोग करें।'],
                'मिथुन' => ['name' => 'मिथुन (Gemini)', 'element' => 'वायु', 'lord' => 'बुध', 'lucky' => '5, हरा', 'prediction' => 'बुद्धि व चातुर्य से कठिन समस्याएं हल होंगी। मित्रों का सहयोग प्राप्त होगा।', 'upay' => 'भगवान श्री गणेश को २१ दूर्वा अर्पित करें।'],
                'कर्क' => ['name' => 'कर्क (Cancer)', 'element' => 'जल', 'lord' => 'चंद्रमा', 'lucky' => '2, चांदी', 'prediction' => 'मानसिक शांति मिलेगी। माता के स्वास्थ्य में सुधार होगा। आध्यात्मिक कार्यों में रुचि बढ़ेगी।', 'upay' => 'शिवलिंग पर कच्चा दूध व गंगाजल अर्पित करें।'],
                'सिंह' => ['name' => 'सिंह (Leo)', 'element' => 'अग्नि', 'lord' => 'सूर्य', 'lucky' => '1, स्वर्ण', 'prediction' => 'आत्मविश्वास शिखर पर रहेगा। नेतृत्व क्षमता से सम्मान मिलेगा। उच्चाधिकारियों से सहयोग।', 'upay' => 'प्रातः सूर्य देव को तांबे के लोटे से जल व कुमकुम डालकर अर्घ्य दें।'],
                'कन्या' => ['name' => 'कन्या (Virgo)', 'element' => 'पृथ्वी', 'lord' => 'बुध', 'lucky' => '5, हरा', 'prediction' => 'धन प्रबंधन सुधरेगा। नई योजनाओं पर कार्य प्रारंभ कर सकते हैं। सेहत उत्तम रहेगी।', 'upay' => 'तुलसी जी के समक्ष घी का दीपक जलाएं।'],
                'तुला' => ['name' => 'तुला (Libra)', 'element' => 'वायु', 'lord' => 'शुक्र', 'lucky' => '7, गुलाबी', 'prediction' => 'कला व व्यापार में सफलता मिलेगी। दांपत्य जीवन में मधुरता बनी रहेगी।', 'upay' => 'कन्याओं को फल अथवा मिष्ठान का दान करें।'],
                'वृश्चिक' => ['name' => 'वृश्चिक (Scorpio)', 'element' => 'जल', 'lord' => 'मंगल', 'lucky' => '9, मैरून', 'prediction' => 'साहस व पराक्रम में वृद्धि होगी। गूढ़ विद्याओं व धर्म कर्म में मन लगेगा।', 'upay' => 'सुंदरकांड का पाठ करें या श्रवण करें।'],
                'धनु' => ['name' => 'धनु (Sagittarius)', 'element' => 'अग्नि', 'lord' => 'बृहस्पति', 'lucky' => '3, पीला', 'prediction' => 'गुरुजनों का आशीर्वाद मिलेगा। यात्रा के शुभ योग हैं। ज्ञान व प्रतिष्ठा में वृद्धि।', 'upay' => 'भगवान विष्णु को पीले पुष्प व चने की दाल अर्पित करें।'],
                'मकर' => ['name' => 'मकर (Capricorn)', 'element' => 'पृथ्वी', 'lord' => 'शनि', 'lucky' => '8, नीला', 'prediction' => 'कठिन परिश्रम का पूर्ण फल मिलेगा। धैर्य से कार्य संपन्न होंगे।', 'upay' => 'पीपल के वृक्ष के समीप सरसों के तेल का दीपक प्रज्वलित करें।'],
                'कुंभ' => ['name' => 'कुंभ (Aquarius)', 'element' => 'वायु', 'lord' => 'शनि', 'lucky' => '4, आसमानी', 'prediction' => 'सामाजिक कार्यों में सम्मान मिलेगा। नवीन विचारों से प्रगति होगी।', 'upay' => 'ॐ शं शनैश्चराय नमः का ११ बार जप करें।'],
                'मीन' => ['name' => 'मीन (Pisces)', 'element' => 'जल', 'lord' => 'बृहस्पति', 'lucky' => '3, केसरिया', 'prediction' => 'मनोकामनाएं पूर्ण होने का दिन है। दान पुण्य से आंतरिक शांति प्राप्त होगी।', 'upay' => 'माथे पर केसर अथवा चंदन का तिलक लगाएं।'],
            ];

            foreach ($rashiDetails as $rashiKey => $r) {
                if (str_contains($lower, mb_strtolower($rashiKey))) {
                    $rashiReply = "🕉️ *ॐ नमः शिवाय* 🙏\n\n🔮 *{$r['name']} — आज का विस्तृत राशिफल* 🔱\n\n" .
                        "⭐ *तत्व:* {$r['element']} | *स्वामी:* {$r['lord']}\n" .
                        "✨ *लकी अंक व रंग:* {$r['lucky']}\n\n" .
                        "📜 *दैनिक भविष्यफल:*\n{$r['prediction']}\n\n" .
                        "🌿 *वैदिक अचूक उपाय:*\n{$r['upay']}\n\n" .
                        "🪔 *विशेष पूजा संकल्प:*\nग्रह दोष शांति व परिवार कल्याण हेतु काशी विश्वनाथ में रुद्राभिषेक पूजा बुक करें:\n👉 https://onlinepuja.live/puja/category/list\n\n" .
                        "हर हर महादेव! 🔱🚩";

                    $wa->sendText($cleanPhone, $rashiReply);
                    $this->saveAiReply($remoteJid, $rashiReply);
                    return response()->json(['status' => 200, 'action' => 'rashi_replied']);
                }
            }

            // 7. AUTONOMOUS CONVERSATIONAL VEDIC ACHARYA CLOSER (Multi-turn Gemini AI)
            $aiReply = \App\Services\VedicAcharyaAiService::processIncomingMessage(
                $remoteJid,
                $cleanPhone,
                $pushName,
                $text
            );

            if (!empty($aiReply)) {
                // Ensure devotional opening constraint
                if (!str_starts_with($aiReply, '🕉️ *ॐ नमः शिवाय* 🙏')) {
                    $aiReply = "🕉️ *ॐ नमः शिवाय* 🙏\n\n" . $aiReply;
                }

                $wa->sendText($cleanPhone, $aiReply);
                $this->saveAiReply($remoteJid, $aiReply);
                return response()->json(['status' => 200, 'action' => 'ai_replied']);
            }

            // 8. DEFAULT POLITE DEVOTIONAL FALLBACK
            $defaultReply = "🕉️ *ॐ नमः शिवाय* 🙏\n\n" .
                "OnlinePuja.live काशी विश्वनाथ सेवा केंद्र में आपका स्वागत है। आप निम्न में से क्या जानना चाहते हैं?\n\n" .
                "• अपनी राशि का नाम लिखकर भेजें (उदा. *मेष*, *सिंह*, *तुला*)\n" .
                "• पूजा बुकिंग हेतु लिखें: *PUJA*\n" .
                "• जन्म कुंडली हेतु लिखें: *KUNDLI*\n" .
                "• दैनिक पंचांग रोकने हेतु लिखें: *STOP*\n\n" .
                "हर हर महादेव! 🔱🚩";

            $wa->sendText($cleanPhone, $defaultReply);
            $this->saveAiReply($remoteJid, $defaultReply);
            return response()->json(['status' => 200, 'action' => 'menu_replied']);

        } catch (\Throwable $e) {
            Log::error("WhatsApp Webhook Exception: " . $e->getMessage());
            return response()->json(['status' => 500, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper to save AI outbound reply to database and update thread
     */
    protected function saveAiReply(string $remoteJid, string $reply): void
    {
        $now = Carbon::now();

        DB::table('whatsapp_messages')->insert([
            'thread_jid' => $remoteJid,
            'direction' => 'outbound',
            'message' => $reply,
            'status' => 'sent',
            'is_ai_generated' => true,
            'timestamp' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('whatsapp_threads')->where('remote_jid', $remoteJid)->update([
            'last_message' => $reply,
            'last_message_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
