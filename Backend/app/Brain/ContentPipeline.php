<?php

namespace App\Brain;

use App\AI\ProviderManager;

/**
 * The self-growing content loop:
 * keyword -> AI draft -> Critic lint -> rewrite-once -> content_plans(drafted).
 * Publishing is a separate, gated step (content:publish).
 */
class ContentPipeline
{
    protected const PERSONA = <<<'TXT'
You are the senior content lead for ONLINE PUJA (https://onlinepuja.live) — an Indian
platform where devotees book verified pandits for pujas, havans and astrology consultations.
Voice: warm, devotional yet practical; rooted in authentic Vedic tradition; helpful to a
first-time devotee. Never invent rituals or prices. Always write for Indian readers in
clear global English.
TXT;

    /**
     * Filler that says nothing about the subject. Left in, "for" and "and"
     * padded token sets enough to make two identical topics look distinct.
     */
    /** How far ahead a festival still counts as worth writing for. */
    protected const FESTIVAL_HORIZON_DAYS = 45;

    /**
     * A festival that is tomorrow gets no priority: a fresh page needs time to
     * be crawled and to rank, so the runway matters more than the proximity.
     * Below this, the keyword falls back to plain difficulty ordering.
     */
    protected const FESTIVAL_MIN_LEAD_DAYS = 5;

    protected const STOP_WORDS = [
        'the', 'and', 'for', 'with', 'your', 'you', 'our', 'are', 'that', 'this',
        'from', 'into', 'its', 'all', 'but', 'not', 'can', 'how', 'what', 'why',
        'when', 'who', 'will', 'has', 'have', 'about', 'best', 'top',
    ];

    public function __construct(
        protected ProviderManager $ai,
        protected GoalPlanner $planner,
        protected BusinessReadiness $readiness
    ) {}

    protected function profileAnchors(): string
    {
        $row = \DB::table('brain_profile')->orderByDesc('id')->first();
        $data = $row ? json_decode($row->data, true) : [];

        return $data['summary']
            ?? 'Online Puja helps devotees across India book verified pandits for pujas, havans, '
               .'kundli matching and astrology consultation entirely online, with live streaming of rituals.';
    }

    /**
     * Draft articles for up to $limit planned/new keywords.
     *
     * @return array{drafted:int,failed:int,results:array}
     */
    public function run(int $limit = 2, bool $publishImmediately = false): array
    {
        // Supply before demand. Drafting at full velocity into a catalogue with
        // nothing bookable in it is how this engine spent weeks generating
        // articles for a storefront that could not take a single order. The
        // gate never stops the pipeline outright — a site that goes silent
        // loses the crawl cadence it has earned — but it refuses to scale, and
        // it files the supply work that would actually unblock growth.
        $requested = $limit;
        $limit = $this->readiness->contentBudget($limit);

        $gated = $limit < $requested;

        if ($gated) {
            $supplyTasks = $this->readiness->emitSupplyTasks();

            RunLog::record(
                'content:generate',
                'blog',
                'partial',
                "Readiness gate: drafting {$limit} of {$requested} requested",
                ['readiness' => $this->readiness->assess(), 'supply_tasks_created' => $supplyTasks],
                0,
                0,
                'gate'
            );
        }

        // Candidate pool: unworked, rankable keywords. We pull a wide slice and
        // then diversify + de-overlap in PHP, because ordering by difficulty
        // alone kept handing back the same one or two easiest clusters — which
        // is exactly why every recent article was "puja samagri" or "kundli".
        $pool = \DB::table('keywords')
            ->where('status', 'new')
            ->where('difficulty', '<=', 60)
            ->orderBy('difficulty')
            ->limit(max($limit * 8, 40))
            ->get();

        $pool = $this->festivalFirst($pool);

        $keywords = $this->pickDiverse($pool, $limit);

        $out = ['drafted' => 0, 'failed' => 0, 'gated' => $gated, 'budget' => $limit, 'requested' => $requested, 'results' => []];

        foreach ($keywords as $kw) {
            $res = $this->draftOne($kw);
            $out['results'][] = $res + ['keyword' => $kw->phrase];

            if ($res['ok']) {
                $out['drafted']++;
            } else {
                $out['failed']++;
            }
        }

        return $out;
    }

    /**
     * Choose up to $limit keywords that (a) aren't already covered by a live or
     * drafted article and (b) span different clusters, so a run produces varied
     * topics instead of hammering the single easiest one.
     *
     * @param  \Illuminate\Support\Collection  $pool
     * @return array<int,object>
     */
    /**
     * Move keywords for an imminent festival to the front of the pool, nearest
     * festival first.
     *
     * Ordering by difficulty alone is the right call for evergreen work and the
     * wrong one in October: a Navratri guide is worth writing in the four weeks
     * *before* Navratri and worth almost nothing the week after, while "online
     * puja samagri" is worth the same in any month. Anything past, further out
     * than the horizon, or too close to be indexed in time keeps its plain
     * difficulty ordering.
     *
     * @param  \Illuminate\Support\Collection  $pool
     * @return \Illuminate\Support\Collection
     */
    protected function festivalFirst($pool)
    {
        $upcoming = \DB::table('hindu_festivals')
            ->whereBetween('date', [
                now()->addDays(self::FESTIVAL_MIN_LEAD_DAYS)->toDateString(),
                now()->addDays(self::FESTIVAL_HORIZON_DAYS)->toDateString(),
            ])
            ->orderBy('date')
            ->get();

        if ($upcoming->isEmpty()) {
            return $pool;
        }

        // cluster slug => days until the festival
        $urgency = [];

        foreach ($upcoming as $festival) {
            $cluster = \Illuminate\Support\Str::slug($festival->name);
            $days = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($festival->date), false);
            $urgency[$cluster] = min($urgency[$cluster] ?? PHP_INT_MAX, $days);
        }

        return $pool->sortBy(function ($kw) use ($urgency) {
            $days = $urgency[$kw->cluster] ?? null;

            // Festival keywords sort ahead of everything by days-to-festival;
            // the rest keep their difficulty order behind them.
            return $days === null
                ? [1, (int) $kw->difficulty]
                : [0, $days];
        })->values();
    }

    protected function pickDiverse($pool, int $limit): array
    {
        $covered = $this->coveredTopics();
        $picked = [];
        $usedClusters = [];
        $usedLanguages = [];

        // First pass: one per cluster, skipping already-covered topics.
        foreach ($pool as $kw) {
            if (count($picked) >= $limit) {
                break;
            }

            $topic = $this->topicKey($kw->phrase);
            $cluster = $kw->cluster ?: $topic;
            $language = $kw->language ?? 'en';

            // One per cluster, and one per language while other languages are
            // still on offer — publishing two Hindi guides and no English one
            // on the same day wastes half the day's reach.
            if ($this->isCovered($kw->phrase, $covered) || isset($usedClusters[$cluster])) {
                continue;
            }

            if (isset($usedLanguages[$language]) && $this->hasOtherLanguage($pool, $usedLanguages)) {
                continue;
            }

            $picked[] = $kw;
            $usedClusters[$cluster] = true;
            $usedLanguages[$language] = true;
            $covered[] = $this->topicTokens($topic);
        }

        // Second pass: if we still need more, relax the one-per-cluster rule but
        // keep skipping already-covered topics.
        if (count($picked) < $limit) {
            foreach ($pool as $kw) {
                if (count($picked) >= $limit) {
                    break;
                }

                if ($this->isCovered($kw->phrase, $covered) || in_array($kw->id, array_column($picked, 'id'), true)) {
                    continue;
                }

                $picked[] = $kw;
                $covered[] = $this->topicTokens($this->topicKey($kw->phrase));
            }
        }

        return $picked;
    }

    /**
     * Is there still a keyword in the pool in a language we have not used in
     * this run? Only then is it worth passing over an otherwise good keyword.
     *
     * @param  \Illuminate\Support\Collection  $pool
     * @param  array<string,bool>  $usedLanguages
     */
    protected function hasOtherLanguage($pool, array $usedLanguages): bool
    {
        foreach ($pool as $kw) {
            if (! isset($usedLanguages[$kw->language ?? 'en'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Topics already published or drafted, as token sets, so the pipeline never
     * re-covers ground it already has.
     *
     * @return array<int,array<int,string>>
     */
    protected function coveredTopics(): array
    {
        $out = [];

        foreach (\DB::table('blogs')->where('isDelete', 0)->pluck('title') as $title) {
            $out[] = $this->topicTokens($this->topicKey((string) $title));
        }

        foreach (\DB::table('content_plans')->whereIn('status', ['drafted', 'approved', 'published'])->pluck('title') as $title) {
            $out[] = $this->topicTokens($this->topicKey((string) $title));
        }

        return array_values(array_filter($out));
    }

    /**
     * Is this phrase already covered by something we've written?
     *
     * Exact fingerprint equality was too literal: "kundli matching online free"
     * and "Free Kundli Matching Online for Marriage & Gun Milan" are the same
     * article to a search engine but different token sets, which is how seven
     * near-identical posts reached the blog. Compare by overlap instead.
     *
     * @param  array<int,array<int,string>>  $covered
     */
    protected function isCovered(string $phrase, array $covered): bool
    {
        $tokens = $this->topicTokens($this->topicKey($phrase));

        if (! $tokens) {
            return false;
        }

        foreach ($covered as $other) {
            if (! $other) {
                continue;
            }

            $shared = count(array_intersect($tokens, $other));
            $union = count(array_unique(array_merge($tokens, $other)));

            // Same subject said two ways.
            if ($union > 0 && $shared / $union >= 0.7) {
                return true;
            }

            // Or: everything this phrase is about already sits inside a title
            // we've written, and it's specific enough that that's no accident.
            if ($shared === count($tokens) && count($tokens) >= 4) {
                return true;
            }
        }

        return false;
    }

    /** Order-insensitive fingerprint of the head topic of a phrase/title. */
    protected function topicKey(string $text): string
    {
        $head = preg_split('/[:\-–—|(]/u', $text)[0];
        $words = preg_split('/\s+/', strtolower(trim(preg_replace('/[^a-z0-9\s]/i', ' ', $head))));
        $words = array_values(array_filter($words, fn ($w) => strlen($w) > 2 && ! in_array($w, self::STOP_WORDS, true)));
        sort($words);

        return implode(' ', $words);
    }

    /** @return array<int,string> */
    protected function topicTokens(string $key): array
    {
        return $key === '' ? [] : array_values(array_unique(explode(' ', $key)));
    }

    /** How the model should handle the keyword's language. */
    protected function languageDirective(string $language): string
    {
        return match ($language) {
            'hi' => 'WRITE THE ENTIRE ARTICLE IN HINDI, in Devanagari script — the title, the '
                .'meta description and every heading and paragraph. Use natural, warm, everyday '
                .'Hindi as spoken in India, not stiff literary translation, and keep widely used '
                .'English words (booking, online, WhatsApp) as they are actually said. Never mix '
                .'in English sentences.',
            default => 'Write the entire article in clear global English for Indian readers.',
        };
    }

    /**
     * A URL-safe slug for a title that may be entirely Devanagari. Str::slug
     * strips non-latin characters, so a Hindi title yields an empty string and
     * the draft used to be thrown away as malformed — the keyword phrase is the
     * next best source, and a date-stamped fallback guarantees we never lose a
     * finished article to a naming problem.
     */
    protected function slugFor(string $title, object $kw): string
    {
        foreach ([$title, (string) $kw->phrase, (string) ($kw->cluster ?? '')] as $candidate) {
            $slug = $this->asciiSlug($candidate);

            if ($slug !== '') {
                return $slug;
            }
        }

        return 'puja-guide-'.now()->format('Ymd-His');
    }

    /** Str::slug, but guaranteed to return latin-or-nothing. */
    protected function asciiSlug(string $text): string
    {
        $slug = \Illuminate\Support\Str::slug($text);

        return preg_match('/[a-z0-9]/', $slug) ? $slug : '';
    }

    public function draftOne(object $kw): array
    {
        $profile = $this->profileAnchors();

        $language = $kw->language ?? 'en';

        $system = self::PERSONA."\nBUSINESS CONTEXT (anchor every claim to this):\n{$profile}\n\n".
            $this->languageDirective($language)."\n\n".
            // Title must be <=60 so it survives the SEO auditor's 65-char rule
            // without needing a god-mode heal right after publication.
            'Output STRICT JSON: {"title":string(45-60 chars, MUST NOT exceed 60),'.
            '"slug":string,"meta_description":string(140-158 chars),'.
            '"html":string}. "html" is ONLY the article fragment: start with an <h2>, use <h2>/<h3>/<p>/<ul>, '.
            'natural internal anchor suggestion is NOT needed, no <html>/<body>/styles. 900-1200 words. '.
            // A Devanagari slug survives neither Str::slug() nor a URL bar.
            'The "slug" MUST be lowercase ASCII latin letters, digits and hyphens ONLY, even when the '.
            'article itself is not in English — transliterate the title if you have to.';

        $prompt = "Write a complete, genuinely useful article targeting the exact search phrase:\n\"{$kw->phrase}\"\n".
            "Search intent: {$kw->intent}. Include practical steps, what to expect, costs-in-principle guidance, ".
            "and a short FAQ (2 questions) at the end.";

        $draft = $this->ai->chat($system, $prompt, ['json' => true, 'temperature' => 0.85, 'max_tokens' => 3200]);

        if (! $draft['ok']) {
            $this->savePlan($kw, null, $draft['error']);

            return ['ok' => false, 'error' => $draft['error']];
        }

        $j = $this->decodeJson($draft['text']);

        // A usable article only truly needs a title and body. The model
        // frequently omits (or nulls) "slug" even when the prose is perfect —
        // discarding those wasted ~40% of drafts. Derive the slug from the
        // title instead, which is exactly what a human editor would do.
        if (isset($j['title'], $j['html']) && empty($j['slug'])) {
            $j['slug'] = $this->slugFor($j['title'], $kw);
        }

        $j['slug'] = isset($j['slug']) ? $this->asciiSlug((string) $j['slug']) : '';

        if ($j['slug'] === '' && isset($j['title'])) {
            $j['slug'] = $this->slugFor($j['title'], $kw);
        }

        if (! isset($j['title'], $j['html']) || $j['slug'] === '' || $j['html'] === '') {
            $this->savePlan($kw, null, 'model returned non-JSON');

            return ['ok' => false, 'error' => 'bad-json'];
        }

        // Critic pass — rewrite once with failures fed back.
        $lint = Critic::lint($j['html'], $kw->phrase, $j['title'] ?? null, $language);

        if (! $lint["pass"]) {
            // Naming the failures is not enough — the model reuses the exact
            // phrase it was pulled up on. Ban the offending strings outright.
            $banned = [];

            foreach ($lint['failures'] as $f) {
                if (preg_match("/stock phrase: '(.+)'/", $f, $mm)) {
                    $banned[] = $mm[1];
                }
            }

            $retryPrompt = $prompt
                ."\n\nYour previous draft was REJECTED by editorial review for: "
                .implode('; ', $lint['failures']).'.';

            if ($banned) {
                $retryPrompt .= "\nThese exact phrases are BANNED. Do not use them anywhere, in any form: \""
                    .implode('", "', $banned).'". Write around them with plain, concrete language.';
            }

            $retryPrompt .= "\nRewrite the complete article fixing every point. Same JSON shape.";

            $retry = $this->ai->chat($system, $retryPrompt, ["json" => true, "temperature" => 0.7, "max_tokens" => 3200]);
            $j2 = $retry["ok"] ? $this->decodeJson($retry["text"]) : null;

            if ($j2 && isset($j2["title"], $j2["html"]) && empty($j2["slug"])) {
                $j2["slug"] = $this->slugFor($j2["title"], $kw);
            }

            if ($j2 && isset($j2["title"], $j2["slug"], $j2["html"]) && $j2["html"] !== "") {
                $j = $j2;
                $lint = Critic::lint($j["html"], $kw->phrase, $j["title"] ?? null, $language);
            }
        }

        if ($lint['pass'] && isset($j['html'])) {
            $j['html'] = $this->injectPujaBookingCta($j['html'], $kw);

            // Silo Engine: Determine topical cluster and pillar
            $siloKey = SiloEngine::detectSilo($kw->phrase . ' ' . ($j['title'] ?? ''));
            $j['silo'] = $siloKey;

            // AEO Engine: Synthesize self-contained direct answer block and takeaways for AI search engines
            $aeo = AeoEngine::synthesizeAeoBlock($j['title'] ?? $kw->phrase, $j['html']);
            $j['aeo_direct_answer'] = $aeo['direct_answer'] ?? null;
            $j['aeo_takeaways'] = $aeo['key_takeaways'] ?? null;

            if (!empty($aeo['html_snippet'])) {
                if (preg_match('/<\/h2>/i', $j['html'], $matches, PREG_OFFSET_CAPTURE)) {
                    $insertPos = $matches[0][1] + strlen($matches[0][0]);
                    $j['html'] = substr($j['html'], 0, $insertPos) . "\n" . $aeo['html_snippet'] . "\n" . substr($j['html'], $insertPos);
                } else {
                    $j['html'] = $aeo['html_snippet'] . "\n" . $j['html'];
                }
            }

            // Vedic Schema Engine: Inject structured FAQ JSON-LD if FAQs present
            if (!empty($aeo['faq'])) {
                $faqSchema = VedicSchemaEngine::forFaq($aeo['faq']);
                if ($faqSchema !== '') {
                    $j['html'] .= "\n" . $faqSchema;
                }
            }
        }

        $status = $lint['pass'] ? 'drafted' : 'critic_failed';
        $id = $this->savePlan($kw, $j, implode('; ', $lint['failures']), $status);

        return ['ok' => $lint['pass'], 'plan_id' => $id, 'status' => $status, 'failures' => $lint['failures']];
    }

    /**
     * Models reached through an auto-routing alias differ call to call: some
     * return bare JSON, some fence it, some emit a reasoning preamble first.
     * Take the outermost JSON object rather than trusting the whole reply.
     */
    protected function decodeJson(string $text): ?array
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        // 1. Direct JSON decode
        $clean = trim(preg_replace('/```(?:json)?|```/i', '', $text));
        if (is_array($j = json_decode($clean, true))) {
            return $j;
        }

        // 2. Substring between outermost braces
        $start = strpos($clean, '{');
        $end = strrpos($clean, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $extracted = substr($clean, $start, $end - $start + 1);
            $j = json_decode($extracted, true);
            if (is_array($j)) {
                return $j;
            }

            // 3. Fallback: Fix unescaped control characters/newlines inside JSON string values
            $sanitized = preg_replace_callback('/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/s', function ($m) {
                return '"' . str_replace(["\r\n", "\r", "\n", "\t"], ['\n', '\n', '\n', '\t'], $m[1]) . '"';
            }, $extracted);
            $j = json_decode($sanitized, true);
            if (is_array($j)) {
                return $j;
            }

            // 4. Regex extraction for malformed JSON fields
            $title = null;
            $slug = null;
            $meta = null;
            $html = null;

            if (preg_match('/"title"\s*:\s*"([^"]+)"/u', $clean, $m)) $title = $m[1];
            if (preg_match('/"slug"\s*:\s*"([^"]+)"/u', $clean, $m)) $slug = $m[1];
            if (preg_match('/"meta_description"\s*:\s*"([^"]+)"/u', $clean, $m)) $meta = $m[1];
            if (preg_match('/"html"\s*:\s*"(.*)"\s*,\s*"faq"/su', $clean, $m)) {
                $html = stripslashes($m[1]);
            } elseif (preg_match('/"html"\s*:\s*"(.*)"\s*\}\s*$/su', $clean, $m)) {
                $html = stripslashes($m[1]);
            }

            if (!empty($title) && !empty($html)) {
                return [
                    'title' => $title,
                    'slug' => $slug,
                    'meta_description' => $meta,
                    'html' => $html,
                ];
            }
        }

        // 5. Ultimate Fallback: Model returned raw Markdown or HTML prose
        if (preg_match('/^#\s+(.+)$/m', $clean, $tm)) {
            $title = trim($tm[1]);
            $body = trim(preg_replace('/^#\s+.+$/m', '', $clean));
            // Wrap in basic HTML if plain text
            $htmlBody = str_starts_with($body, '<') ? $body : '<p>' . nl2br($body) . '</p>';
            return [
                'title' => $title,
                'slug' => \Illuminate\Support\Str::slug($title),
                'meta_description' => \Illuminate\Support\Str::limit(strip_tags($htmlBody), 150),
                'html' => $htmlBody,
            ];
        }

        return null;
    }

    protected function savePlan(object $kw, ?array $j, string $note, string $status = 'failed'): int
    {
        $id = \DB::table('content_plans')->insertGetId([
            'title' => $j['title'] ?? ('Draft: '.$kw->phrase),
            'pillar' => $j['silo'] ?? ($kw->cluster ?: 'general'),
            'channel' => 'web',
            'keyword_id' => $kw->id,
            'status' => $status,
            'payload' => json_encode([
                'slug' => $j['slug'] ?? null,
                'meta_description' => $j['meta_description'] ?? null,
                'html' => $j['html'] ?? null,
                'silo' => $j['silo'] ?? null,
                'aeo' => [
                    'direct_answer' => $j['aeo_direct_answer'] ?? null,
                    'key_takeaways' => $j['aeo_takeaways'] ?? null,
                ],
                'critic_note' => $note,
                'language' => $kw->language ?? 'en',
                'provider' => 'brain',
            ]),
            'updated_at' => now(), 'created_at' => now(),
        ]);

        // Retire the keyword once it yields a usable draft so the diversifier
        // never hands it back. Failures stay 'new' to be retried later.
        if ($status === 'drafted') {
            \DB::table('keywords')->where('id', $kw->id)->update(['status' => 'drafted', 'updated_at' => now()]);
        }

        return $id;
    }

    /**
     * Inject a high-converting, contextual Puja Booking Card into the article HTML.
     */
    protected function injectPujaBookingCta(string $html, object $kw): string
    {
        if (str_contains($html, 'puja-booking-card-wrapper')) {
            return $html;
        }

        $phrase = strtolower($kw->phrase);
        $words = array_filter(explode(' ', preg_replace('/[^a-z0-9 ]/', '', $phrase)), fn($w) => strlen($w) > 3);

        $query = \Illuminate\Support\Facades\DB::table('pujas')->where('puja_status', 1);
        if (!empty($words)) {
            $query->where(function($q) use ($words) {
                foreach ($words as $w) {
                    $q->orWhere('puja_title', 'LIKE', "%{$w}%")
                      ->orWhere('puja_place', 'LIKE', "%{$w}%");
                }
            });
        }
        $puja = $query->first() ?: \Illuminate\Support\Facades\DB::table('pujas')->where('puja_status', 1)->first();

        if (!$puja) {
            return $html;
        }

        $title = htmlspecialchars($puja->puja_title, ENT_QUOTES);
        $place = htmlspecialchars($puja->puja_place ?: 'Varanasi', ENT_QUOTES);
        $price = number_format((float) ($puja->puja_price ?: 1100));
        $url = url('puja/' . ($puja->slug ?: 'category/list'));

        $ctaHtml = <<<HTML
<div class="puja-booking-card-wrapper" style="margin: 32px 0; padding: 22px; background: linear-gradient(135deg, #090d16 0%, #0f172a 100%); border: 2px solid #f59e0b; border-radius: 16px; color: #ffffff; box-shadow: 0 12px 28px -6px rgba(15, 23, 42, 0.4);">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div style="flex: 1; min-width: 250px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span style="background: #f59e0b; color: #020617; font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.05em;">Sanctified Vedic Ritual</span>
                <span style="color: #94a3b8; font-size: 11px; font-weight: 600;">Direct from {$place}</span>
            </div>
            <h3 style="margin: 0 0 6px 0; font-size: 18px; font-weight: 800; color: #fbbf24;">Book {$title} with Personal Sankalp</h3>
            <p style="margin: 0; font-size: 12px; color: #cbd5e1; line-height: 1.5;">Conducted by verified Kashi Purohits chanting your Gotra. Receive sanctified temple prasad delivered to your doorstep.</p>
        </div>
        <div style="text-align: right; min-width: 160px;">
            <div style="font-size: 20px; font-weight: 900; color: #fbbf24; font-family: monospace;">₹{$price}</div>
            <div style="font-size: 10px; color: #94a3b8; margin-bottom: 8px;">Dakshina + Free Prasad Delivery</div>
            <a href="{$url}" target="_blank" style="display: inline-block; background: #f59e0b; color: #020617; font-size: 12px; font-weight: 800; padding: 10px 18px; border-radius: 10px; text-decoration: none; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);">Book Sacred Puja →</a>
        </div>
    </div>
</div>
HTML;

        // Try inserting before FAQ
        if (preg_match('/<h2[^>]*>(?:Frequently Asked|FAQ)/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            $pos = $matches[0][1];
            return substr($html, 0, $pos) . $ctaHtml . "
" . substr($html, $pos);
        }

        return $html . "
" . $ctaHtml;
    }

}
