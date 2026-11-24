<?php

namespace App\Brain;

use App\AI\ProviderManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Master Brain command router.
 * Understands natural-language ops commands and executes them:
 *   "plan cluster for online puja in india"
 *   "schedule upcoming hindu festival wishes this month"
 *   "run seo audit" · "write article for X" · "status report" …
 */
class CommandRouter
{
    public function __construct(
        protected ProviderManager $ai,
        protected KeywordResearch $research,
        protected ContentPipeline $pipeline,
        protected SiteAuditor $auditor
    ) {}

    public function handle(string $message, int $userId): array
    {
        $cleanMsg = trim($message);
        $lower = mb_strtolower($cleanMsg);

        // 1. FAST-PATH: Deterministic intent matching for instant sub-second execution
        $action = null;
        $params = [];
        $reply = null;

        if (preg_match('/^(status|report|status report|briefing|growth status|how are we doing)/i', $lower)) {
            $action = 'status_report';
            $reply = 'Here is the live Growth & AI OS status report:';
        } elseif (preg_match('/^(run )?seo audit|audit seo|scan site|site audit|scan sitemap/i', $lower)) {
            $action = 'seo_audit';
            $reply = 'Executing technical SEO audit across sitemaps and key landing pages…';
        } elseif (preg_match('/^sync models|refresh models|update ai models/i', $lower)) {
            $action = 'sync_models';
            $reply = 'Synchronizing live AI model catalogue across active cloud providers…';
        } elseif (preg_match('/^schedule (upcoming )?hindu festival wishes|schedule festivals?|festival wishes/i', $lower)) {
            $action = 'schedule_festivals';
            $reply = 'Queuing devotional Hindu festival wishes and multi-channel social promotions…';
        } elseif (preg_match('/^plan cluster (for |about )?(.+)$/i', $cleanMsg, $m) || preg_match('/^cluster (for )?(.+)$/i', $cleanMsg, $m)) {
            $action = 'plan_cluster';
            $params = ['topic' => trim($m[2])];
            $reply = 'Planning keyword cluster and content silos for "'.trim($m[2]).'"…';
        } elseif (preg_match('/^(write|draft) (an? )?(article|blog|post) (for |about )?(.+)$/i', $cleanMsg, $m)) {
            $action = 'write_article';
            $params = ['keyword' => trim($m[4])];
            $reply = 'Drafting SEO article and running Critic quality control for "'.trim($m[4]).'"…';
        }

        // 2. SLOW-PATH: If not matched deterministically, consult AI router
        $providerUsed = 'internal-router';
        if (! $action) {
            $history = DB::table('brain_chats')->where('user_id', $userId)
                ->orderByDesc('id')->limit(4)->get()->reverse()
                ->map(fn ($c) => 'USER: '.mb_substr($c->message, 0, 150)."\nBRAIN: ".mb_substr((string) $c->response, 0, 100))
                ->implode("\n");

            $goal = DB::table('goals')->where('status', 'active')->first();
            $goalCtx = $goal
                ? sprintf('ACTIVE GOAL #%d "%s": target %s by %s.', $goal->id, $goal->title, number_format($goal->target), $goal->deadline)
                : 'No active goal.';
            $today = now()->toDateString();

            $system = <<<TXT
You are the MASTER BRAIN of OnlinePuja.live — India's premier autonomous Vedic Puja booking, Temple sanctum live-streaming, and Jyotish consultation platform.
Today is {$today}. {$goalCtx}

CORE PLATFORM CONTEXT & CAPABILITIES:
- Services: Kashi Vishwanath Rudrabhishek, Mahalakshmi Pujan, Navagraha Shanti, Kaal Sarp Dosh, Chandi Path, Dev Deepawali, Mahakumbh 2026.
- Deliverables: Live Sanctum Sankalp videos with devotee's Name & Gotra, consecrated Prasad shipped across India.
- Astrology Network: Certified Vedic Pandits & Astrologers (Call, Chat, Kundli matchmaking, Daily Panchang).
- Autonomous Growth OS: Technical SEO audits, automated keyword silos, festival wishes broadcast, and WhatsApp AI bot.

LANGUAGE & CONVERSATIONAL RULES:
1. Multilingual Fluency: You understand Hindi, English, and Hinglish perfectly.
2. Hindi Font / Devanagari Requirement: If the user asks to speak in Hindi (e.g., "Hindi me baat kro", "Hindi font me baat kro", "हिंदी में बात करें", or greets in Hindi), you MUST reply in natural, respectful, professional Devanagari Hindi (हिन्दी लिपि). Do NOT output romanized Hinglish when Hindi font is requested, and NEVER blindly echo the user's sentence back to them.
3. Conversational Answering: When the user asks a question, gives operational feedback, or seeks guidance, choose action "answer" and compose a thorough, insightful, and culturally respectful response in "reply".
4. Action Classification: If the user gives a specific ops command, choose the corresponding action below.

ACTIONS:
- plan_cluster        {topic:string}
- schedule_festivals  {month?:1-12, days_ahead?:int}
- write_article       {keyword:string}
- seo_audit           {}
- sync_models         {}
- status_report       {}
- answer              {}

Reply STRICT JSON:
{"action":"<name>","params":{},"reply":"<helpful, detailed, well-crafted response in user's preferred language/script>"}

RECENT CONTEXT:
{$history}
TXT;

            $res = $this->ai->chat($system, $cleanMsg, ['json' => true, 'temperature' => 0.4, 'max_tokens' => 1200]);
            $providerUsed = $res['provider'] ?? 'ai';
            $dec = $this->decode($res['text'] ?? '');

            $action = $dec['action'] ?? 'answer';
            $params = is_array($dec['params'] ?? null) ? $dec['params'] : [];
            $reply = trim((string) ($dec['reply'] ?? ''));

            if (! $reply) {
                // If model returned empty reply or failed JSON, check language for fallback
                $isHindi = preg_match('/[\x{0900}-\x{097F}]/u', $cleanMsg) || preg_match('/(hindi|हिंदी)/i', $cleanMsg);
                if ($action === 'answer' || $action === 'unknown') {
                    $reply = $isHindi
                        ? "प्रणाम! मैं OnlinePuja.live का मास्टर ब्रेन (Master Brain) हूँ। मैं आपके प्लेटफ़ॉर्म के विकास, पूजा बुकिंग, ज्योतिषी परामर्श, एसईओ ऑडिट और दैनिक संचालन में पूरी सहायता कर सकता हूँ। आप मुझे 'status report', 'run seo audit', या 'plan cluster for online puja' जैसे आदेश दे सकते हैं।"
                        : "Master Brain online. I am your autonomous Growth & Ops Engine for OnlinePuja.live. You can give direct ops commands like:\n• 'status report'\n• 'plan cluster for online puja'\n• 'schedule upcoming hindu festival wishes'\n• 'run seo audit'\n• 'sync models'";
                } else {
                    $reply = 'Executing action '.$action.'…';
                }
            }
        }

        $actions = [['action' => $action, 'params' => $params, 'result' => null]];

        try {
            $actions[0]['result'] = match ($action) {
                'plan_cluster' => $this->actPlanCluster($params['topic'] ?? $cleanMsg),
                'schedule_festivals' => $this->actScheduleFestivals($params),
                'write_article' => $this->actWriteArticle($params['keyword'] ?? null),
                'seo_audit' => $this->auditor->scan(16),
                'sync_models' => $this->ai->syncModels(),
                default => null,
            };

            if ($action === 'status_report') {
                $reply = $this->buildStatusReport();
                $actions[0]['result'] = 'report';
            }
        } catch (\Throwable $e) {
            $actions[0]['result'] = 'ERROR: '.$e->getMessage();
            $reply .= ' ⚠ Action notice: '.substr($e->getMessage(), 0, 160);
        }

        DB::table('brain_chats')->insert([
            'user_id' => $userId,
            'message' => mb_substr($cleanMsg, 0, 2000),
            'response' => mb_substr($reply, 0, 4000),
            'actions' => json_encode($actions),
            'provider' => $providerUsed,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return ['reply' => $reply, 'actions' => $actions];
    }

    /* ============================ ACTIONS ============================ */

    protected function actPlanCluster(string $topic): array
    {
        $topic = trim($topic);
        $list = [];

        // Try AI expansion first
        $kwRes = $this->ai->chat(
            'SEO keyword researcher for Online Puja (Indian puja booking). STRICT JSON only.',
            "Topic: \"{$topic}\". Return 15 realistic Indian search phrases (3-6 words, lowercase, mix informational + booking intent). JSON: {\"keywords\":[\"phrases\"]}",
            ['json' => true, 'temperature' => 0.85, 'max_tokens' => 500]
        );

        if ($kwRes['ok'] && ! empty($kwRes['text'])) {
            try {
                $j = json_decode(preg_replace('/^```(json)?|```$/m', '', trim($kwRes['text'])), true);
                $list = array_slice(array_filter((array) ($j['keywords'] ?? [])), 0, 20);
            } catch (\Throwable $e) {
            }
        }

        // Fallback: If AI returned empty, generate high-intent search phrases algorithmically
        if (empty($list)) {
            $base = mb_strtolower($topic);
            $list = [
                "online {$base} booking",
                "best pandit for {$base} online",
                "{$base} vidhi and samagri list",
                "cost of {$base} at home",
                "book certified vedic pandit for {$base}",
                "{$base} muhurat and timings",
                "online pandit ji for {$base} puja",
                "complete vidhi for {$base} at home",
                "how to perform {$base} puja step by step",
                "online puja service for {$base}",
            ];
        }

        $addedCount = 0;
        foreach ($list as $p) {
            $p = trim(mb_strtolower((string) $p));

            if (mb_strlen($p) >= 5 && ! DB::table('keywords')->where('phrase', $p)->exists()) {
                DB::table('keywords')->insert([
                    'phrase' => $p, 'source' => 'ai-brain', 'cluster' => mb_strtolower($topic),
                    'intent' => str_contains($p, 'book') || str_contains($p, 'online') || str_contains($p, 'cost') || str_contains($p, 'price') ? 'commercial' : 'informational',
                    'difficulty' => random_int(25, 55), 'status' => 'new',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $addedCount++;
            }
        }

        $firstWord = mb_strtolower(explode(' ', $topic)[0]);
        $byIntent = DB::table('keywords')
            ->selectRaw("COALESCE(NULLIF(cluster,''),'general') AS pillar, intent, COUNT(*) c")
            ->where(function ($q) use ($topic, $firstWord) {
                $q->where('cluster', mb_strtolower($topic))->orWhere('phrase', 'like', '%'.$firstWord.'%');
            })
            ->groupBy('pillar', 'intent')->orderByDesc('c')->limit(6)->get();

        $plans = 0;
        $goalId = DB::table('goals')->where('status', 'active')->value('id');

        foreach ($byIntent as $row) {
            $title = ucfirst($row->pillar).' ('.ucfirst($row->intent).') — cluster: '.$topic;

            if (! DB::table('content_plans')->where('title', $title)->exists()) {
                DB::table('content_plans')->insert([
                    'goal_id' => $goalId, 'title' => $title, 'pillar' => $row->pillar,
                    'channel' => 'web', 'status' => 'idea',
                    'payload' => json_encode(['keywords_in_cluster' => $row->c, 'source' => 'master-brain']),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $plans++;
            }
        }

        return ['keywords_added' => $addedCount, 'clusters' => $byIntent->count(), 'cluster_plans_created' => $plans];
    }

    public function actScheduleFestivals(array $params = []): array
    {
        Artisan::call('brain:seed-festivals');

        $days = (int) ($params['days_ahead'] ?? 75);

        $festivals = DB::table('hindu_festivals')
            ->where('scheduled', false)
            ->whereBetween('date', [now()->toDateString(), now()->addDays(max(30, $days))->toDateString()])
            ->orderBy('date')->limit(6)->get();

        if ($festivals->isEmpty()) {
            return ['queued' => 0, 'note' => 'All upcoming festivals within window are already scheduled'];
        }

        $queued = 0;

        foreach ($festivals as $match) {
            $cleanName = preg_replace('/\s*\(.*?\)/', '', $match->name);
            $cleanDeity = $match->deity ?: 'Lord Ganesha';
            $hashTag = str_replace(' ', '', $cleanName);

            $wishText = "May the divine blessings of {$cleanDeity} bring peace, prosperity, and spiritual fulfillment to you and your loved ones on the sacred occasion of {$match->name}. Book verified Vedic pandits for home pujas on OnlinePuja.live! 🙏✨";
            $hashtags = "#{$hashTag} #OnlinePuja #PujaAtHome #VedicPandit #HinduFestivals";

            DB::table('content_plans')->insert([
                'title' => 'Wish: '.$match->name.' ('.$match->date.')',
                'pillar' => 'festival', 'channel' => 'social', 'status' => 'approved',
                'payload' => json_encode([
                    'text' => $wishText,
                    'hashtags' => $hashtags,
                    'channels' => json_decode((string) $match->channels) ?: ['facebook', 'instagram', 'x', 'whatsapp'],
                ]),
                'due_at' => $match->date.' 06:00:00',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            DB::table('hindu_festivals')->where('id', $match->id)->update(['scheduled' => true]);
            $queued++;
        }

        return ['queued' => $queued, 'of' => $festivals->count(), 'message' => "Successfully queued {$queued} devotional festival wishes."];
    }

    protected function actWriteArticle(?string $keyword): array
    {
        if (! empty($keyword)) {
            $kw = mb_strtolower(trim((string) $keyword));

            if (! DB::table('keywords')->where('phrase', $kw)->exists()) {
                DB::table('keywords')->insert([
                    'phrase' => $kw, 'source' => 'ai-brain', 'cluster' => 'command',
                    'intent' => 'commercial', 'difficulty' => 40, 'status' => 'new',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        return $this->pipeline->run(1);
    }

    protected function buildStatusReport(): string
    {
        $lines = [];

        $goal = DB::table('goals')->where('status', 'active')->first();

        if ($goal) {
            $total = (int) DB::table('goal_snapshots')->where('goal_id', $goal->id)->sum('value');
            $start = \Carbon\Carbon::parse($goal->start_date);
            $deadline = \Carbon\Carbon::parse($goal->deadline);
            $elapsed = max(1, $start->diffInDays(now()));
            $horizon = max(1, $start->diffInDays($deadline) + 1);
            $expected = (int) round(($elapsed / $horizon) * $goal->target);
            $pace = $expected > 0 ? min(999, round($total / $expected * 100)) : 100;

            $lines[] = sprintf(
                '🎯 GOAL "%s": %s / %s visitors · expected %s by day %d/%d · current pace %d%%',
                $goal->title,
                number_format($total), number_format($goal->target),
                number_format($expected), $elapsed, $horizon, $pace
            );
        }

        $lines[] = '🔑 Keywords: '.DB::table('keywords')->count()
            .' total · easy-win: '.DB::table('keywords')->where('difficulty', '<=', 45)->count();
        $lines[] = '📝 Content Pipeline: drafted '.DB::table('content_plans')->where('status', 'drafted')->count()
            .' · approved '.DB::table('content_plans')->where('status', 'approved')->count()
            .' · published '.DB::table('content_plans')->where('status', 'published')->count();
        $lines[] = '⚠️ Technical SEO: '.DB::table('seo_issues')->where('status', 'open')->count().' open issues · '.DB::table('seo_issues')->where('status', 'fixed')->count().' fixed';
        $lines[] = '🤖 AI Engine: '.DB::table('ai_models')->count().' models synced';
        $lines[] = '🙏 Festival Scheduler: '.DB::table('hindu_festivals')->count().' seeded · '.DB::table('content_plans')->where('pillar', 'festival')->count().' wishes queued';

        return implode("\n", $lines);
    }

    protected function decode(string $text): array
    {
        $clean = preg_replace('/^```(json)?|```$/m', '', trim($text));
        $j = json_decode(trim($clean), true);

        return is_array($j) ? $j : [];
    }
}
