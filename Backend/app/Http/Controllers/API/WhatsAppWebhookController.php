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
    public function handle(Request $request, EvolutionWaService $wa)
    {
        try {
            $data = $request->all();
            Log::info("Evolution WhatsApp Webhook Received", ['event' => $data['event'] ?? 'unknown']);

            // Only process incoming messages (not sent by us)
            $msgData = $data['data'] ?? [];
            $key = $msgData['key'] ?? [];
            $isFromMe = $key['fromMe'] ?? false;
            $remoteJid = $key['remoteJid'] ?? '';

            if ($isFromMe || empty($remoteJid) || str_contains($remoteJid, '@g.us')) {
                // Ignore outgoing messages or group chats
                return response()->json(['status' => 'ignored']);
            }

            // Extract Sender Mobile
            $phone = explode('@', $remoteJid)[0];
            $cleanPhone = $wa->cleanNumber($phone);

            // Extract Message Text
            $text = '';
            if (!empty($msgData['message']['conversation'])) {
                $text = trim($msgData['message']['conversation']);
            } elseif (!empty($msgData['message']['extendedTextMessage']['text'])) {
                $text = trim($msgData['message']['extendedTextMessage']['text']);
            }

            if (empty($text)) {
                return response()->json(['status' => 'no_text']);
            }

            $lower = mb_strtolower($text);

            // 1. OPT-OUT HANDLER (STOP / रोकें / UNSUBSCRIBE)
            if (in_array($lower, ['stop', 'unsubscribe', 'रोकें', 'बंद करो', 'बंद करें', 'cancel'])) {
                DB::table('whatsapp_optouts')->updateOrInsert(
                    ['phone' => $cleanPhone],
                    ['reason' => 'user_stop', 'created_at' => now()]
                );

                $wa->sendText(
                    $cleanPhone,
                    "प्रणाम 🙏\n\nआपकी प्रार्थना स्वीकार कर ली गई है। आपका दैनिक पंचांग व राशिफल प्रसारण बंद कर दिया गया है।\n\nपुनः शुरू करने हेतु किसी भी समय *START* लिखकर भेजें। 🕉️"
                );
                return response()->json(['status' => 'opted_out']);
            }

            // 2. RE-SUBSCRIBE (START / शुरू करें)
            if (in_array($lower, ['start', 'restart', 'शुरू करें', 'चालू करें'])) {
                DB::table('whatsapp_optouts')->where('phone', $cleanPhone)->delete();

                $wa->sendText(
                    $cleanPhone,
                    "हर हर महादेव! 🙏\n\nआपका दैनिक पंचांग व राशिफल पुनः सक्रिय कर दिया गया है। अब प्रतिदिन प्रातः ०६:३० बजे आपको शुभ मुहूर्त व दैनिक भविष्यफल प्राप्त होगा। 🕉️"
                );
                return response()->json(['status' => 'opted_in']);
            }

            // 3. RASHI HOROSCOPE LOOKUP (मेष, वृषभ, मिथुन, etc.)
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
                    $reply = "🔮 *{$r['name']} — आज का विस्तृत राशिफल* 🕉️\n\n" .
                        "⭐ *तत्व:* {$r['element']} | *स्वामी:* {$r['lord']}\n" .
                        "✨ *लकी अंक व रंग:* {$r['lucky']}\n\n" .
                        "📜 *दैनिक भविष्यफल:*\n{$r['prediction']}\n\n" .
                        "🌿 *वैदिक अचूक उपाय:*\n{$r['upay']}\n\n" .
                        "🪔 *विशेष पूजा संकल्प:*\nग्रह दोष शांति व परिवार कल्याण हेतु काशी विश्वनाथ में रुद्राभिषेक पूजा बुक करें:\n👉 https://onlinepuja.live/pujas\n\n" .
                        "_(अन्य राशि जानने हेतु राशि का नाम लिखकर भेजें)_";

                    $wa->sendText($cleanPhone, $reply);
                    return response()->json(['status' => 'rashi_replied']);
                }
            }

            // 4. AUTONOMOUS CONVERSATIONAL VEDIC ACHARYA CLOSER (Multi-turn Gemini AI)
            $pushName = $msgData['pushName'] ?? 'Devotee';
            $aiReply = \App\Services\VedicAcharyaAiService::processIncomingMessage(
                $remoteJid,
                $cleanPhone,
                $pushName,
                $text
            );

            if (!empty($aiReply)) {
                $wa->sendText($cleanPhone, $aiReply);

                // Persist message record
                $now = now();
                DB::table('whatsapp_messages')->insert([
                    'thread_jid' => $remoteJid,
                    'direction' => 'outbound',
                    'message' => $aiReply,
                    'status' => 'sent',
                    'is_ai_generated' => true,
                    'timestamp' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                return response()->json(['status' => 'ai_replied']);
            }

            // 5. DEFAULT POLITE DEVOTIONAL FALLBACK
            $reply = "हर हर महादेव! 🙏 प्रणाम।\n\n" .
                "OnlinePuja.live सेवा केंद्र में आपका स्वागत है। आप निम्न में से क्या जानना चाहते हैं?\n\n" .
                "• अपनी राशि का नाम लिखकर भेजें (उदा. *मेष*, *सिंह*, *तुला*)\n" .
                "• पूजा बुकिंग हेतु लिखें: *PUJA*\n" .
                "• जन्म कुंडली हेतु लिखें: *KUNDLI*\n" .
                "• दैनिक पंचांग रोकने हेतु लिखें: *STOP*\n\n" .
                "शुभम् भवतु! 🕉️";

            $wa->sendText($cleanPhone, $reply);
            return response()->json(['status' => 'menu_replied']);
        } catch (\Throwable $e) {
            Log::error("WhatsApp Webhook Exception: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
