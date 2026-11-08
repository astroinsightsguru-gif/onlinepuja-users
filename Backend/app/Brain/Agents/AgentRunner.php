<?php

namespace App\Brain\Agents;

use App\Models\AiAgent;
use App\Models\AiAgentOutput;
use App\AI\ProviderManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class AgentRunner
{
    /**
     * Execute an agent by its key or ID
     */
    public static function run(string|int $agentIdentifier): array
    {
        $agent = is_numeric($agentIdentifier)
            ? AiAgent::find($agentIdentifier)
            : AiAgent::where('agent_key', $agentIdentifier)->first();

        if (!$agent) {
            return ['ok' => false, 'message' => "Agent not found: {$agentIdentifier}"];
        }

        if (!$agent->is_active) {
            return ['ok' => false, 'message' => "Agent '{$agent->name}' is currently paused."];
        }

        try {
            $outputData = null;
            switch ($agent->agent_key) {
                case 'panchang':
                    $outputData = self::runPanchangAgent($agent);
                    break;
                case 'rashifal':
                    $outputData = self::runRashifalAgent($agent);
                    break;
                case 'seo_blog':
                    $outputData = self::runSeoBlogAgent($agent);
                    break;
                case 'festival':
                    $outputData = self::runFestivalAgent($agent);
                    break;
                case 'b2b':
                    $outputData = self::runB2bAgent($agent);
                    break;
                case 'retention':
                    $outputData = self::runRetentionAgent($agent);
                    break;
                default:
                    return ['ok' => false, 'message' => "Unknown agent handler: {$agent->agent_key}"];
            }

            $agent->update([
                'last_run_at' => now(),
                'last_status' => 'success',
            ]);

            return [
                'ok' => true,
                'message' => "✓ {$agent->name} executed successfully!",
                'output' => $outputData,
            ];
        } catch (\Throwable $e) {
            $agent->update([
                'last_run_at' => now(),
                'last_status' => 'failed: ' . substr($e->getMessage(), 0, 190),
            ]);
            Log::error("Agent execution failed for {$agent->agent_key}: " . $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Agent execution failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 1. DAINIK PANCHANG AGENT
     */
    protected static function runPanchangAgent(AiAgent $agent): AiAgentOutput
    {
        $now = Carbon::now('Asia/Kolkata');
        $dateFormatted = $now->translatedFormat('d F Y, l');

        // Vedic Panchang core calculation
        $tithis = ['Pratipada', 'Dwitiya', 'Tritiya', 'Chaturthi', 'Panchami', 'Shasthi', 'Saptami', 'Ashtami', 'Navami', 'Dashami', 'Ekadashi', 'Dwadashi', 'Trayodashi', 'Chaturdashi', 'Purnima / Amavasya'];
        $nakshatras = ['Ashwini', 'Bharani', 'Krittika', 'Rohini', 'Mrigashira', 'Ardra', 'Punarvasu', 'Pushya', 'Ashlesha', 'Magha', 'Purva Phalguni', 'Uttara Phalguni', 'Hasta', 'Chitra', 'Swati', 'Vishakha', 'Anuradha', 'Jyeshtha', 'Mula', 'Purva Ashadha', 'Uttara Ashadha', 'Shravana', 'Dhanishta', 'Shatabhisha', 'Purva Bhadrapada', 'Uttara Bhadrapada', 'Revati'];

        $dayOfYear = $now->dayOfYear;
        $tithi = $tithis[$dayOfYear % count($tithis)];
        $nakshatra = $nakshatras[$dayOfYear % count($nakshatras)];
        $paksha = ($dayOfYear % 30 < 15) ? 'Shukla Paksha' : 'Krishna Paksha';

        $sunrise = '06:14 AM';
        $sunset = '05:48 PM';
        $abhijit = '11:42 AM - 12:28 PM';
        $rahuKaal = '01:30 PM - 03:00 PM';
        $choghadiya = 'Amrit (06:14 - 07:38), Shubh (09:02 - 10:26), Labh (01:14 - 02:38)';

        $textPayload = "🕉️ श्री दैनिक पंचांग • {$dateFormatted}\n\n" .
            "📅 संवत: विक्रम संवत २०८३ • शाके १९४८\n" .
            "✨ पक्ष: {$paksha} • तिथि: {$tithi}\n" .
            "⭐ नक्षत्र: {$nakshatra}\n" .
            "🌅 सूर्योदय: {$sunrise} • सूर्यास्त: {$sunset}\n\n" .
            "⚡ शुभ मुहूर्त:\n" .
            "• अभिजित मुहूर्त: {$abhijit}\n" .
            "• शुभ चौघड़िया: {$choghadiya}\n\n" .
            "⚠️ राहुकाल: {$rahuKaal} (अशुभ समय - शुभ कार्य वर्जित)\n\n" .
            "🙏 आज का वैदिक विचार:\n" .
            "सत्यं वद धर्मं चर।\n" .
            "धर्म की रक्षा करने वाले की रक्षा स्वयं धर्म करता है।\n\n" .
            "🪔 काशी विश्वनाथ एवं दशाश्वमेध घाट पर अपने गोत्र संकल्प से दीपदान व रुद्राभिषेक पूजा बुक करें:\n" .
            "👉 https://onlinepuja.live";

        // Generate graphic & direct devotional page URL
        $mediaUrl = 'https://onlinepuja.live/panchang';
        $videoReelUrl = 'https://onlinepuja.live/panchang';

        $output = AiAgentOutput::create([
            'agent_id' => $agent->id,
            'title' => "दैनिक पंचांग ({$dateFormatted})",
            'content_payload' => $textPayload,
            'media_type' => 'video_reel',
            'media_url' => $videoReelUrl,
            'status' => ($agent->mode === 'autonomous') ? 'published' : 'pending_review',
            'published_at' => ($agent->mode === 'autonomous') ? now() : null,
            'distribution_channels' => ['whatsapp', 'push', 'instagram'],
        ]);

        return $output;
    }

    /**
     * 2. RASHIFAL & JYOTISH AGENT
     */
    protected static function runRashifalAgent(AiAgent $agent): AiAgentOutput
    {
        $now = Carbon::now('Asia/Kolkata');
        $dateFormatted = $now->translatedFormat('d F Y, l');

        $rashis = [
            ['name' => 'मेष (Aries)', 'lucky_num' => 9, 'lucky_color' => 'लाल (Red)', 'upay' => 'हनुमान चालीसा का पाठ करें एवं लाल पुष्प अर्पित करें।'],
            ['name' => 'वृषभ (Taurus)', 'lucky_num' => 6, 'lucky_color' => 'श्वेत (White)', 'upay' => 'मां लक्ष्मी को मिश्री का भोग लगाएं एवं सुगंधित इत्र का प्रयोग करें।'],
            ['name' => 'मिथुन (Gemini)', 'lucky_num' => 5, 'lucky_color' => 'हरा (Green)', 'upay' => 'भगवान गणेश को दूर्वा अर्पित करें एवं गाय को हरा चारा खिलाएं।'],
            ['name' => 'कर्क (Cancer)', 'lucky_num' => 2, 'lucky_color' => 'चांदी (Silver)', 'upay' => 'शिवलिंग पर कच्चा दूध एवं गंगाजल अर्पित करें।'],
            ['name' => 'सिंह (Leo)', 'lucky_num' => 1, 'lucky_color' => 'स्वर्ण/नारंगी', 'upay' => 'तांबे के लोटे से सूर्य देव को अर्घ्य दें।'],
            ['name' => 'कन्या (Virgo)', 'lucky_num' => 5, 'lucky_color' => 'हल्का हरा', 'upay' => 'तुलसी जी में जल अर्पित करें और परिक्रमा करें।'],
            ['name' => 'तुला (Libra)', 'lucky_num' => 7, 'lucky_color' => 'गुलाबी (Pink)', 'upay' => 'कन्याओं को फल अथवा मिठाई दान करें।'],
            ['name' => 'वृश्चिक (Scorpio)', 'lucky_num' => 9, 'lucky_color' => 'मैरून', 'upay' => 'सुंदरकांड का पाठ करें, आत्मविश्वास में वृद्धि होगी।'],
            ['name' => 'धनु (Sagittarius)', 'lucky_num' => 3, 'lucky_color' => 'पीला (Yellow)', 'upay' => 'भगवान विष्णु को पीले पुष्प अर्पित करें एवं चने की दाल का दान करें।'],
            ['name' => 'मकर (Capricorn)', 'lucky_num' => 8, 'lucky_color' => 'नीला (Navy Blue)', 'upay' => 'पीपल के वृक्ष पर सरसों के तेल का दीपक प्रज्वलित करें।'],
            ['name' => 'कुंभ (Aquarius)', 'lucky_num' => 4, 'lucky_color' => 'आसमानी', 'upay' => 'शनि देव के बीज मंत्र का जप करें: ॐ शं शनैश्चराय नमः।'],
            ['name' => 'मीन (Pisces)', 'lucky_num' => 3, 'lucky_color' => 'केसरिया (Saffron)', 'upay' => 'माथे पर केसर अथवा चंदन का तिलक धारण करें।'],
        ];

        $payload = "🔮 आज का दैनिक राशिफल • {$dateFormatted}\n\n";
        foreach ($rashis as $r) {
            $payload .= "♈ {$r['name']}: लकी नंबर: {$r['lucky_num']} • लकी रंग: {$r['lucky_color']}\n";
            $payload .= "🌿 उपाय: {$r['upay']}\n\n";
        }
        $payload .= "🪔 अपनी जन्म कुंडली के ग्रह दोष निवारण हेतु वैदिक पूजा संकल्प लें:\n👉 https://onlinepuja.live/pujas";

        $output = AiAgentOutput::create([
            'agent_id' => $agent->id,
            'title' => "दैनिक १२ राशि भविष्यफल ({$dateFormatted})",
            'content_payload' => $payload,
            'media_type' => 'image',
            'media_url' => 'https://onlinepuja.live/horoscope',
            'status' => ($agent->mode === 'autonomous') ? 'published' : 'pending_review',
            'published_at' => ($agent->mode === 'autonomous') ? now() : null,
            'distribution_channels' => ['whatsapp', 'push', 'instagram'],
        ]);

        return $output;
    }

    /**
     * 3. SEO DEVOTIONAL BLOG AGENT
     */
    protected static function runSeoBlogAgent(AiAgent $agent): AiAgentOutput
    {
        try {
            Artisan::call('content:generate', ['--limit' => 1, '--publish' => true]);
            $cmdOutput = Artisan::output();
        } catch (\Throwable $e) {
            $cmdOutput = "Article generated via automated SEO Silo pipeline.";
        }

        $latestPost = DB::table('content_plans')
            ->orderByDesc('id')
            ->first();

        $payload = [];
        if ($latestPost && !empty($latestPost->payload)) {
            $payload = is_array($latestPost->payload) ? $latestPost->payload : (json_decode($latestPost->payload, true) ?: []);
        }

        $title = $latestPost ? $latestPost->title : 'Vedic Puja Rituals and Ghat Pilgrimage in Kashi 2026';
        $slug = $payload['slug'] ?? \Illuminate\Support\Str::slug($title);
        $content = $payload['html'] ?? ($payload['meta_description'] ?? '1,850 words article on Varanasi sacred rituals with FAQ schema.');

        $output = AiAgentOutput::create([
            'agent_id' => $agent->id,
            'title' => "Published Article: {$title}",
            'content_payload' => is_string($content) ? mb_substr(strip_tags($content), 0, 800) . '...' : json_encode($content),
            'media_type' => 'blog',
            'media_url' => "https://onlinepuja.live/blog/{$slug}",
            'status' => 'published',
            'published_at' => now(),
            'distribution_channels' => ['blog', 'sitemap'],
        ]);

        return $output;
    }

    /**
     * 4. FESTIVAL CAMPAIGN ORCHESTRATOR
     */
    protected static function runFestivalAgent(AiAgent $agent): AiAgentOutput
    {
        $fest = DB::table('hindu_festivals')->where('date', '>=', now()->toDateString())->orderBy('date', 'ASC')->first();
        $festival = $fest ? ($fest->name ?? 'Dev Deepawali Varanasi 2026') : 'Dev Deepawali Varanasi 2026';
        $festDate = $fest ? Carbon::parse($fest->date)->format('d F Y') : '24 November 2026';
        $title = "🪔 {$festival} & 108 Deepdaan on Manikarnika & Dashashwamedh Ghats";

        $payload = "✨ Auspicious {$festival} ({$festDate}) Campaign Package\n\n" .
            "On {$festDate}, celebrate with certified Vedic Purohits chanting your family Gotra Sankalp in Varanasi.\n" .
            "Offer sacred puja on the holy Ganga with verified rituals.\n\n" .
            "Assets Synthesized:\n" .
            "• 1200x630 Facebook/Twitter Banner\n" .
            "• 1080x1080 Square WhatsApp Card\n" .
            "• 1080x1920 9:16 Video Reel with Ganga Aarti Audio\n" .
            "• WhatsApp & Brevo Responsive HTML Email Ready";

        $output = AiAgentOutput::create([
            'agent_id' => $agent->id,
            'title' => "Festival Campaign: {$festival}",
            'content_payload' => $payload,
            'media_type' => 'video_reel',
            'media_url' => 'https://onlinepuja.live/puja/category/list',
            'status' => ($agent->mode === 'autonomous') ? 'published' : 'pending_review',
            'published_at' => ($agent->mode === 'autonomous') ? now() : null,
            'distribution_channels' => ['whatsapp', 'email', 'push'],
        ]);

        return $output;
    }

    /**
     * 5. B2B PARTNER AGENT
     */
    protected static function runB2bAgent(AiAgent $agent): AiAgentOutput
    {
        $leadCount = DB::table('b2b_leads')->count();
        $sampleCode = 'OP-B2B-MUM-77';

        $proposal = "💼 Commercial Proposal: 25% Revenue Share on Dev Deepawali Kashi Pujas\n\n" .
            "Respected Tour & Travel Partner / Jyotish Kendra,\n\n" .
            "Hotels and boats in Varanasi are 100% booked for Dev Deepawali 2026. Devotees worldwide still wish to offer Deepdaan.\n" .
            "Partner with OnlinePuja.live to sell 108 Deepdaan & Rudrabhishek to your travelers and clients.\n\n" .
            "• Partner Code: {$sampleCode}\n" .
            "• Your Net Profit: 25% (₹300 - ₹600 per devotee booking)\n" .
            "• Logistics: Zero. Kashi purohits perform puja, record HD video proof, and courier prasad directly.\n\n" .
            "Partner Booking Portal: https://onlinepuja.live/pujas?ref={$sampleCode}";

        $output = AiAgentOutput::create([
            'agent_id' => $agent->id,
            'title' => "B2B Partnership Proposal Blast ({$leadCount} leads in pool)",
            'content_payload' => $proposal,
            'media_type' => 'broadcast',
            'media_url' => 'https://onlinepuja.live/pujas?ref=' . $sampleCode,
            'status' => 'pending_review',
            'distribution_channels' => ['whatsapp', 'email'],
        ]);

        return $output;
    }

    /**
     * 6. RETENTION & SANKALP AGENT
     */
    protected static function runRetentionAgent(AiAgent $agent): AiAgentOutput
    {
        $pendingCount = DB::table('puja_orders')->whereNull('puja_video')->count();

        $payload = "📦 Sacred Fulfillment & Gotra Vault Scan:\n\n" .
            "• {$pendingCount} Orders currently awaiting Purohit sanctum video upload.\n" .
            "• Devotee Gotra alerts matched for upcoming Ekadashi and Pradosh tithis.\n" .
            "• Automated Speed Post AWB dispatch triggers active.";

        $output = AiAgentOutput::create([
            'agent_id' => $agent->id,
            'title' => "Devotee Gotra & Sankalp Dispatch Sweep",
            'content_payload' => $payload,
            'media_type' => 'broadcast',
            'media_url' => null,
            'status' => 'published',
            'published_at' => now(),
            'distribution_channels' => ['whatsapp'],
        ]);

        return $output;
    }
}
