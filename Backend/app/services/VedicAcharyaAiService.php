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

            $messagesPayload = [];

            // 2. Sacred Acharya Sales Closer System Prompt
            $systemPrompt = <<<PROMPT
You are Acharya Vidyadhar Sharma, the revered Chief Vedic Pandit and Spiritual Guide of OnlinePuja.live at Kashi Vishwanath Sanctuary, Varanasi.
Your mission is to provide authentic spiritual guidance, comfort devotees, understand their family's problems (Dosha, Health, Marriage delay, Business/Debt, Pitru Dosh), and guide them to perform the appropriate Vedic Puja with personal Name and Gotra Sankalp.

CORE BEHAVIOR & RULES:
1. Warm, authentic, and reverent tone: ALWAYS begin every response with "🕉️ *ॐ नमः शिवाय* 🙏". Never say "Pranam" or "हर हर महादेव भक्त जी*". Always end with "हर हर महादेव 🚩".
2. Language: Fluent, warm Hindi or Hinglish (as preferred by devotee).
3. FREE 10-PAGE VEDIC KUNDLI: If devotee asks about Kundli, horoscope, future, marriage, or dosha, warmly invite them to receive their 10-Page Free Vedic Kundli. Ask them for:
   - पूरा नाम (Full Name)
   - जन्म तिथि (Date of Birth: DD-MM-YYYY)
   - जन्म समय (Time of Birth: e.g. 10:30 AM)
   - जन्म स्थान (Place of Birth: City, State)
   Assure them that upon sending these 4 details, their authentic 10-page Kundli PDF will be generated and delivered immediately on WhatsApp for free!
4. Active Qualification: Ask respectfully for their Name & Gotra for sacred Vedic Puja Sankalp.
4. Intelligent Puja Recommendation:
   - Shiva / Rahu / Kaal Sarp / Health: Rudrabhishek or Mahamrityunjaya Puja at Kashi Vishwanath.
   - Shani Sade Sati / Obstacles: Shani Shanti Puja / Hanuman Chalisa Mahapuja.
   - Marriage Delay / Manglik: Mangal Shanti Puja or Gauri Shankar Puja.
   - Business Growth / Lakshmi: Kanakdhara & Lakshmi Kuber Mahapuja.
5. Clear Tiered Pricing & Offering:
   - 🌿 Primary Sankalp (Pandit Dakshina & Sacred Sankalp with Gotra): ₹1,100
   - 🪔 Maha Rudra / Sampoorna Puja (Includes Live HD Video Stream + Brahmin Bhojan): ₹3,100
   - 👑 Ati Rudra VIP Sankalp (5 Vedic Brahmins, Ganga Aarti Dakshina + Prasad Box couriered home): ₹7,100
6. Closing & Action: Provide the official instant booking link: https://onlinepuja.live/puja/category/list
7. Brevity & WhatsApp ergonomics: Keep responses under 4-5 short, readable paragraphs with sacred bullet points and emojis. Never sound like a generic robotic AI. Always speak as an authentic Kashi Pandit.
PROMPT;

            $messagesPayload[] = ['role' => 'system', 'content' => $systemPrompt];

            foreach ($history as $msg) {
                $role = ($msg->direction === 'inbound') ? 'user' : 'assistant';
                $messagesPayload[] = [
                    'role' => $role,
                    'content' => $msg->message,
                ];
            }

            // Append current message if not yet in payload
            $messagesPayload[] = ['role' => 'user', 'content' => $incomingText];

            $reply = null;

            // 3. Generate response via AI Engine
            if (class_exists(ProviderManager::class)) {
                try {
                    $reply = ProviderManager::chat($messagesPayload, [
                        'temperature' => 0.45,
                        'max_tokens' => 380,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("ProviderManager chat error: " . $e->getMessage());
                }
            }

            // Fallback to AiEngineService if ProviderManager did not return text
            if (empty($reply) || !is_string($reply)) {
                try {
                    $engine = app(AiEngineService::class);
                    $reply = $engine->generateText($incomingText, $systemPrompt, [
                        'name' => $devoteeName,
                        'phone' => $phone,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("AiEngineService fallback error: " . $e->getMessage());
                }
            }

            // Reject empty or robotic/proxy error leaks
            if (empty($reply) || !is_string($reply) || str_contains($reply, 'Antigravity') || str_contains($reply, 'download and install') || str_contains($reply, 'no longer supported') || str_contains($reply, 'API key')) {
                $reply = "🕉️ *ॐ नमः शिवाय* 🙏\n\n{$devoteeName} जी, OnlinePuja.live काशी विश्वनाथ सेवा केंद्र में आपका स्वागत है। हमारे सिद्ध वैदिक ब्राह्मणों द्वारा आपके नाम व गोत्र से प्रामाणिक पूजा संपन्न की जाती है।\n\nकृपया हमें बताएं कि आप किस विशेष कार्य अथवा ग्रह शांति हेतु पूजा संकल्प करवाना चाहते हैं? 🔱🚩";
            }

            // 4. Autonomous Lead Extraction & CRM sync
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
                    'name' => !empty($name) ? $name : 'WhatsApp Devotee',
                    'phone' => $phone,
                    'source' => 'whatsapp_inbound',
                    'stage' => 'inquiry',
                    'notes' => "Auto-captured via WhatsApp: " . mb_substr($inquiry, 0, 250),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('leads')->where('id', $existing->id)->update([
                    'notes' => DB::raw("CONCAT(COALESCE(notes, ''), '\n[" . $now->format('d M H:i') . "] " . addslashes(mb_substr($inquiry, 0, 100)) . "')"),
                    'updated_at' => $now,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("Failed to auto-sync lead to CRM: " . $e->getMessage());
        }
    }
}
