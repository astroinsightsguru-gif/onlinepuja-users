<?php

namespace App\Brain;

use App\AI\ProviderManager;
use Illuminate\Support\Facades\Http;

/**
 * Tri-source keyword research — no subscription needed:
 *   1. Google autocomplete (live demand)
 *   2. AI expansion from the Brain profile (for zero-history sites)
 *   3. GSC queries (wired later when OAuth client is added)
 *
 * Scoring favours striking-distance long-tails over vanity head terms.
 */
class KeywordResearch
{
    protected const SEEDS_DEFAULT = 'online puja,puja booking,book pandit online,kundli matching online,astrology consultation,havan online,e-puja';

    protected const COMMERCIAL = ['online', 'booking', 'book', 'hire', 'price', 'cost', 'service', 'best', 'top', 'expert'];

    public function __construct(protected ProviderManager $ai)
    {
    }

    public function seeds(): array
    {
        $custom = \App\Brain\Vault::get('brain_seed_keywords');

        return array_filter(array_map('trim', explode(',', $custom ?: self::SEEDS_DEFAULT)));
    }

    /** Run the full sweep. Returns per-source counts. */
    public function run(): array
    {
        // 'ai' was English-only. keywords:seed-festival is the ONLY source that
        // has ever produced a Hindi keyword (a fixed festival template), which
        // starves the pipeline's own Hindi drafting path — Critic has a
        // Devanagari check, a separate char floor, a Hindi stock-phrase list,
        // and pickDiverse() explicitly tries to keep a run language-diverse —
        // all built for a supply this command never fed.
        $report = ['autocomplete' => 0, 'ai' => 0, 'ai-hi' => 0];
        $seen = [];

        foreach ($this->seeds() as $seed) {
            foreach ($this->autocomplete($seed) as $phrase) {
                $seen[$phrase] = ['source' => 'autocomplete', 'cluster' => $seed, 'language' => 'en'];
            }
        }

        // AI expansion pass — one call per language, many seeds each.
        foreach ($this->aiExpand('en') as $phrase) {
            if (! isset($seen[$phrase])) {
                $seen[$phrase] = ['source' => 'ai', 'cluster' => 'expansion', 'language' => 'en'];
            }
        }

        foreach ($this->aiExpand('hi') as $phrase) {
            // Hindi phrases are Devanagari, so they can never collide with an
            // English key in $seen — no need to guard for it, but do skip
            // anything the model returned in Latin script by mistake, which
            // would otherwise silently mislabel an English phrase as 'hi'.
            if (preg_match('/[\x{0900}-\x{097F}]/u', $phrase) && ! isset($seen[$phrase])) {
                $seen[$phrase] = ['source' => 'ai-hi', 'cluster' => 'expansion', 'language' => 'hi'];
            }
        }

        foreach ($seen as $phrase => $meta) {
            $this->store($phrase, $meta['source'], $meta['cluster'], $meta['language']);
            $report[$meta['source']]++;
        }

        return $report;
    }

    protected function autocomplete(string $seed): array
    {
        try {
            $res = Http::timeout(10)->get(
                'https://suggestqueries.google.com/complete/search',
                ['client' => 'firefox', 'hl' => 'en', 'q' => $seed]
            );
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];

        foreach ((array) $res->json()[1] ?? [] as $s) {
            if (($p = trim((string) $s)) !== '' && stripos($p, 'astroway') === false) {
                $out[] = $p;
            }
        }

        return array_slice($out, 0, 8);
    }

    protected function aiExpand(string $language = 'en'): array
    {
        $seeds = implode(', ', $this->seeds());

        if ($language === 'hi') {
            $system = 'You are an SEO keyword researcher for an Indian online puja booking platform. Reply with STRICT JSON only.';
            $prompt = "Seed topics (translate/adapt the intent, do not transliterate literally): {$seeds}\n".
                'Generate 18 realistic search phrases in HINDI (Devanagari script) that Indian users would actually type '.
                'into Google — natural everyday Hindi as spoken, not stiff literary translation. Keep widely-used English '.
                'words (online, booking, WhatsApp) exactly as commonly typed. Mix informational and booking-intent, '.
                '3-6 words each, no duplicates, no quotes inside. Every phrase must contain Devanagari characters. '.
                'JSON shape: {"keywords":["वाक्यांश एक","वाक्यांश दो"]}';
        } else {
            $system = 'You are an SEO keyword researcher for an Indian online puja booking platform. Reply with STRICT JSON only.';
            $prompt = "Seed topics: {$seeds}\n".
                'Generate 18 realistic search phrases Indian users would type, mixing informational and booking-intent, '.
                '3-6 words each, lowercase, no duplicates, no quotes inside. '.
                'JSON shape: {"keywords":["phrase one","phrase two"]}';
        }

        $res = $this->ai->chat($system, $prompt, ['json' => true, 'temperature' => 0.9, 'max_tokens' => 700]);

        if (! $res['ok']) {
            return [];
        }

        $j = json_decode(preg_replace('/^```(json)?|```$/', '', trim($res['text'])), true);

        return array_slice(array_map('trim', (array) ($j['keywords'] ?? [])), 0, 18);
    }

    protected function store(string $phrase, string $source, string $cluster, string $language = 'en'): void
    {
        // mb_strlen, not strlen: Devanagari is multi-byte, so a byte-length
        // check under-counts a Hindi phrase's true length against these bounds
        // by roughly 2-3x and would reject perfectly good short phrases.
        $len = mb_strlen($phrase);

        if ($len < 4 || $len > 90) {
            return;
        }

        \DB::table('keywords')->updateOrInsert(
            ['phrase' => $phrase],
            [
                'source' => $source,
                'cluster' => $cluster,
                'language' => $language,
                'intent' => $this->intent($phrase),
                'difficulty' => $this->difficulty($phrase),
                'est_volume' => null, // filled once GSC OAuth is wired
                'updated_at' => now(), 'created_at' => now(),
            ]
        );
    }

    protected function intent(string $p): string
    {
        foreach (self::COMMERCIAL as $m) {
            if (str_contains($p, $m)) {
                return 'commercial';
            }
        }

        return str_starts_with($p, 'how') || str_starts_with($p, 'what') || str_starts_with($p, 'why')
            ? 'informational' : 'informational';
    }

    /** Higher = more winnable. Pure heuristics until GSC lands. */
    protected function difficulty(string $p): int
    {
        $words = str_word_count($p);
        $score = 50;

        if ($words >= 3 && $words <= 6) {
            $score -= 15;                       // long-tail sweet spot
        }

        if ($words > 6) {
            $score -= 25;                       // very specific = easy win, low volume
        }

        foreach (['free', 'meaning', 'benefits', 'vidhi', 'samagri'] as $x) {
            if (str_contains($p, $x)) {
                $score -= 10;
            }
        }

        foreach (['astrology', 'horoscope'] as $head) {
            if ($p === $head || $p === "{$head} online") {
                $score += 30;                   // vanity head term
            }
        }

        return max(5, min(95, $score));
    }
}
