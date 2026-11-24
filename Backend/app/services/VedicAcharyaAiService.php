<?php

namespace App\Services;

use App\AI\ProviderManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VedicAcharyaAiService
{
    /**
     * Handle incoming WhatsApp message with full multi-turn conversational AI
     */
    public static function processIncomingMessage(string $remoteJid, string $phone, string $devoteeName, string $incomingText): ?string
    {
        try {
            // 1. Fetch recent conversation history (last 8 messages for context)
            $history = DB::table('whatsapp_messages')
                ->where('thread_jid', $remoteJid)
                ->orderBy('created_at', 'desc')
                ->take(8)
                ->get()
                ->reverse();

            $isOngoing = $history->isNotEmpty();

            // 2. Sacred Acharya Sales Closer System Prompt
            $ongoingDirective = $isOngoing
                ? "CRITICAL CONVERSATION RULE: This is an ONGOING, ACTIVE conversation with devotee {$devoteeName}. DO NOT re-introduce yourself. DO NOT say 'Main Acharya Vidyadhar Sharma...', 'OnlinePuja.live me swagat hai', or repeat generic welcomes. Directly answer and address the devotee's latest message with spiritual warmth and continue the sacred dialogue naturally."
                : "This is the devotee's very first message. You may introduce yourself warmly as Acharya Vidyadhar Sharma, Chief Vedic Pandit of OnlinePuja.live at Kashi Vishwanath.";

            $systemPrompt = <<<PROMPT
You are Acharya Vidyadhar Sharma, the revered Chief Vedic Pandit and Spiritual Guide of OnlinePuja.live at Kashi Vishwanath Sanctuary, Varanasi.
Your mission is to provide authentic spiritual guidance, comfort devotees, understand their family's problems (Dosha, Health, Marriage delay, Business/Debt, Pitru Dosh), and guide them to perform the appropriate Vedic Puja with personal Name and Gotra Sankalp.

CONVERSATION CONTEXT:
{$ongoingDirective}

CORE BEHAVIOR & RULES:
1. Warm, authentic, and reverent tone: ALWAYS begin every response with "🕉️ *ॐ नमः शिवाय* 🙏". Never say "Pranam" or "हर हर महादेव भक्त जी*". Always end with "हर हर महादेव 🚩".
2. Language: Fluent, warm Hindi or Hinglish (matching the devotee's preference).
3. FREE 10-PAGE VEDIC KUNDLI: If devotee asks about Kundli, horoscope, future, marriage, or dosha, warmly invite them to receive their 10-Page Free Vedic Kundli. Ask them for:
   - पूरा नाम (Full Name)
   - जन्म तिथि (Date of Birth: DD-MM-YYYY)
   - जन्म समय (Time of Birth: e.g. 10:30 AM)
   - जन्म स्थान (Place of Birth: City, State)
   Assure them that upon sending these 4 details, their authentic 10-page Kundli PDF will be generated and delivered immediately on WhatsApp for free!
4. Active Qualification: Ask respectfully for their Name & Gotra for sacred Vedic Puja Sankalp.
5. Intelligent Puja Recommendation:
   - Shiva / Rahu / Kaal Sarp / Health: Rudrabhishek or Mahamrityunjaya Puja at Kashi Vishwanath.
   - Shani Sade Sati / Obstacles: Shani Shanti Puja / Hanuman Chalisa Mahapuja.
   - Marriage Delay / Manglik: Mangal Shanti Puja or Gauri Shankar Puja.
   - Business Growth / Lakshmi: Kanakdhara & Lakshmi Kuber Mahapuja.
6. Clear Tiered Pricing & Offering:
   - 🌿 Primary Sankalp (Pandit Dakshina & Sacred Sankalp with Gotra): ₹1,100
   - 🪔 Maha Rudra / Sampoorna Puja (Includes Live HD Video Stream + Brahmin Bhojan): ₹3,100
   - 👑 Ati Rudra VIP Sankalp (5 Vedic Brahmins, Ganga Aarti Dakshina + Prasad Box couriered home): ₹7,100
7. Closing & Action: Provide the official instant booking link: https://onlinepuja.live/puja/category/list
8. Brevity & WhatsApp ergonomics: Keep responses under 3-4 short, readable paragraphs with sacred bullet points and emojis. Never sound like a generic robotic AI. Always speak as an authentic Kashi Pandit.
PROMPT;

            // 3. Format conversational memory into the prompt
            $historyPrompt = "";
            if ($isOngoing) {
                $historyPrompt .= "--- RECENT CONVERSATION HISTORY ---\n";
                foreach ($history as $msg) {
                    $sender = ($msg->direction === 'inbound') ? "Devotee ({$devoteeName})" : "Acharya Vidyadhar";
                    $historyPrompt .= "{$sender}: " . trim((string)$msg->message) . "\n";
                }
                $historyPrompt .= "--- END RECENT CONVERSATION HISTORY ---\n\n";
            }
            $fullPrompt = $historyPrompt . "Devotee's New Message: " . $incomingText . "\n\nProvide your sacred Vedic reply:";

            $reply = null;

            // 4. Generate response via AI Engine (ProviderManager instance or AiEngineService fallback)
            if (class_exists(ProviderManager::class)) {
                try {
                    $manager = app(ProviderManager::class);
                    $res = $manager->chat($systemPrompt, $fullPrompt, [
                        'temperature' => 0.45,
                        'max_tokens'  => 380,
                    ]);
                    if (!empty($res['ok']) && !empty($res['text'])) {
                        $reply = $res['text'];
                    }
                } catch (\Throwable $e) {
                    Log::warning("ProviderManager chat error: " . $e->getMessage());
                }
            }

            // Fallback to AiEngineService if ProviderManager did not return text
            if (empty($reply) || !is_string($reply)) {
                try {
                    $engine = app(AiEngineService::class);
                    $reply = $engine->generateText($fullPrompt, $systemPrompt, [
                        'name'  => $devoteeName,
                        'phone' => $phone,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("AiEngineService fallback error: " . $e->getMessage());
                }
            }

            // Reject empty or robotic/proxy error leaks
            if (empty($reply) || !is_string($reply) || str_contains($reply, 'Antigravity') || str_contains($reply, 'download and install') || str_contains($reply, 'no longer supported') || str_contains($reply, 'API key')) {
                if ($isOngoing) {
                    $reply = "🕉️ *ॐ नमः शिवाय* 🙏\n\n{$devoteeName} जी, आपका संदेश प्राप्त हुआ। कृपया बताएं कि आपकी समस्या के समाधान या किस विशेष पूजा संकल्प हेतु हम आपकी सहायता करें? हर हर महादेव! 🔱🚩";
                } else {
                    $reply = "🕉️ *ॐ नमः शिवाय* 🙏\n\n{$devoteeName} जी, OnlinePuja.live काशी विश्वनाथ सेवा केंद्र में आपका स्वागत है। हमारे सिद्ध वैदिक ब्राह्मणों द्वारा आपके नाम व गोत्र से प्रामाणिक पूजा संपन्न की जाती है।\n\nकृपया हमें बताएं कि आप किस विशेष कार्य अथवा ग्रह शांति हेतु पूजा संकल्प करवाना चाहते हैं? 🔱🚩";
                }
            }

            // 5. Autonomous Lead Extraction & CRM sync
            self::syncLeadToCrm($phone, $devoteeName, $incomingText, $reply);

            return trim($reply);
        } catch (\Throwable $e) {
            Log::error("VedicAcharyaAiService exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Auto-sync qualified devotee into leads pipeline table
     */
    protected static function syncLeadToCrm(string $phone, string $name, string $inquiry, string $reply): void
    {
        try {
            $existing = DB::table('leads')->where('phone', $phone)->first();
            $now = Carbon::now();

            if (!$existing) {
                DB::table('leads')->insert([
                    'name' => ($name && $name !== 'Devotee') ? $name : 'WhatsApp Devotee',
                    'phone' => $phone,
                    'source' => 'whatsapp_chat',
                    'stage' => 'qualified',
                    'priority' => 'hot',
                    'notes' => "Devotee Query: {$inquiry}\nAcharya Guidance: " . mb_substr($reply, 0, 150),
                    'last_contacted_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('leads')->where('id', $existing->id)->update([
                    'last_contacted_at' => $now,
                    'notes' => DB::raw("CONCAT(COALESCE(notes, ''), '\n[Follow-up] " . addslashes(mb_substr($inquiry, 0, 100)) . "')"),
                    'updated_at' => $now,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("syncLeadToCrm error: " . $e->getMessage());
        }
    }
}
