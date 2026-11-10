<?php

namespace App\Social;

use App\Models\AstrologerModel\AstrologerSocialSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The per-astrologer analogue of App\Brain\ContentPipeline::profileAnchors().
 *
 * personaAnchor() compiles a factual profile sheet (skills, languages, focus
 * areas, bio, the pujas they perform) that every AI caption for this astrologer
 * is grounded in. nextIdea() rotates through three post sources — who they are,
 * what they offer, and the astro calendar — deduped against what was used
 * recently, so the feed stays varied.
 */
class AstrologerContentPlanner
{
    /** Days a cached persona sheet stays valid before it is rebuilt. */
    protected const PERSONA_TTL_DAYS = 7;

    /** How many recent idea keys to remember per astrologer for de-duplication. */
    protected const RECENT_IDEAS = 24;

    protected const SOURCES = ['profile', 'service', 'calendar'];

    /**
     * Factual persona sheet for an astrologer, cached on their settings row.
     *
     * @param  bool  $force  rebuild even if the cache is fresh
     */
    public function personaAnchor(int $astrologerId, bool $force = false): string
    {
        $setting = AstrologerSocialSetting::forAstrologer($astrologerId);
        $cached = $setting->persona['text'] ?? null;
        $builtAt = $setting->persona['built_at'] ?? null;

        if (! $force && $cached && $builtAt && Carbon::parse($builtAt)->gt(now()->subDays(self::PERSONA_TTL_DAYS))) {
            return $cached;
        }

        $text = $this->buildPersona($astrologerId);

        $setting->update(['persona' => ['text' => $text, 'built_at' => now()->toIso8601String()]]);

        return $text;
    }

    protected function buildPersona(int $astrologerId): string
    {
        $a = DB::table('astrologers')->where('id', $astrologerId)->first();

        if (! $a) {
            return 'A verified Vedic astrologer on Online Puja.';
        }

        $skills = $this->names('skills', 'name', $this->ids($a->primarySkill, $a->allSkill));
        $languages = $this->names('languages', 'languageName', $this->ids($a->languageKnown));
        $categories = $this->names('astrologer_categories', 'name', $this->ids($a->astrologerCategoryId));

        $pujas = DB::table('pujas')
            ->where('astrologerId', $astrologerId)
            ->where('created_by', 'astrologer')
            ->orderByDesc('id')
            ->limit(6)
            ->pluck('puja_title')
            ->filter()
            ->all();

        $parts = [];
        $parts[] = trim((string) $a->name).' is a verified Vedic astrologer whose practice is listed on Online Puja.';

        if ($skills) {
            $parts[] = 'Specialities: '.implode(', ', $skills).'.';
        }

        if ($categories) {
            $parts[] = 'Helps people with: '.implode(', ', $categories).'.';
        }

        if ($languages) {
            $parts[] = 'Consults in: '.implode(', ', $languages).'.';
        }

        if (! empty($a->experienceInYears) && (int) $a->experienceInYears > 0) {
            $parts[] = ((int) $a->experienceInYears).'+ years of experience.';
        }

        if (! empty($a->currentCity)) {
            $parts[] = 'Based in '.trim((string) $a->currentCity).'.';
        }

        $bio = $this->cleanBio((string) ($a->loginBio ?? ''));

        if ($bio !== '') {
            $parts[] = 'In their own words: '.$bio;
        }

        if ($pujas) {
            $parts[] = 'Pujas they personally perform: '.implode('; ', $pujas).'.';
        }

        return implode(' ', $parts);
    }

    /**
     * Next post idea for an astrologer. Advances the rotation cursor and records
     * the idea so it is not repeated soon.
     *
     * @return array{source:string,text:string,key:string}
     */
    public function nextIdea(int $astrologerId): array
    {
        $setting = AstrologerSocialSetting::forAstrologer($astrologerId);
        $cursor = (int) $setting->idea_cursor;
        $recent = $this->recentIdeaKeys($astrologerId);
        $sources = $this->sourceOrder($astrologerId);

        // Try each source once, starting at the rotation position, until one
        // yields an idea we have not used recently.
        for ($step = 0; $step < count($sources) + 1; $step++) {
            $source = $sources[($cursor + $step) % count($sources)];
            $idea = $this->ideaFor($source, $astrologerId, $cursor + $step, $recent);

            if ($idea !== null) {
                $setting->update(['idea_cursor' => $cursor + $step + 1]);
                $this->rememberIdea($astrologerId, $idea['key']);

                return $idea;
            }
        }

        // Everything recent — fall back to an evergreen profile prompt.
        $setting->update(['idea_cursor' => $cursor + 1]);

        return [
            'source' => 'profile',
            'text' => 'Share a short, encouraging spiritual thought for your followers today and remind them you are available for consultations and puja bookings on Online Puja.',
            'key' => 'evergreen:'.now()->toDateString(),
        ];
    }

    /**
     * The rotation order, with this astrologer's best-performing source moved
     * to the front so it comes round most often. Every source still gets used —
     * a planner that only ever posts its current winner stops discovering, and
     * with a weekly quota in single digits there is no room to explore twice.
     *
     * @return array<int,string>
     */
    protected function sourceOrder(int $astrologerId): array
    {
        try {
            $best = app(\App\Brain\Learner::class)->preferredSource($astrologerId);
        } catch (\Throwable $e) {
            $best = null;
        }

        if ($best === null || ! in_array($best, self::SOURCES, true)) {
            return self::SOURCES;
        }

        return array_merge([$best], array_values(array_diff(self::SOURCES, [$best])));
    }

    /** @return array{source:string,text:string,key:string}|null */
    protected function ideaFor(string $source, int $astrologerId, int $spin, array $recent): ?array
    {
        $make = function (string $key, string $text) use ($source, $recent): ?array {
            return in_array($key, $recent, true) ? null : ['source' => $source, 'text' => $text, 'key' => $key];
        };

        if ($source === 'profile') {
            $angles = [
                'Introduce yourself to new followers: who you are, your journey into Vedic astrology, and the kind of guidance seekers can expect from you.',
                'Explain one Vedic astrology concept you are often asked about, in simple language, and invite questions.',
                'Share why you do this work and what a consultation with you looks like.',
                'Bust a common myth about astrology or puja that you hear from clients.',
                'Describe the kinds of problems people come to you with and how you approach them.',
            ];
            $i = intdiv($spin, count(self::SOURCES)) % count($angles);

            return $make("profile:{$i}", $angles[$i]);
        }

        if ($source === 'service') {
            $pujas = DB::table('pujas')
                ->where('astrologerId', $astrologerId)
                ->where('created_by', 'astrologer')
                ->orderBy('id')
                ->pluck('puja_title')
                ->filter()
                ->values()
                ->all();

            if (! $pujas) {
                return $make('service:generic', 'Talk about the personalised consultations and remedies you offer, and how someone can book a session with you on Online Puja.');
            }

            $i = intdiv($spin, count(self::SOURCES)) % count($pujas);
            $title = $pujas[$i];

            return $make('service:'.Str::slug($title), "Talk about the \"{$title}\" you perform — what it is for, who benefits from it, and how devotees can book it with you on Online Puja.");
        }

        // calendar
        $festival = DB::table('hindu_festivals')
            ->whereDate('date', '>=', now()->toDateString())
            ->whereDate('date', '<=', now()->addDays(21)->toDateString())
            ->orderBy('date')
            ->get()
            ->first(fn ($f) => ! in_array('festival:'.$f->id, $recent, true));

        if ($festival) {
            $deity = $festival->deity ? " (dedicated to {$festival->deity})" : '';
            $when = Carbon::parse($festival->date)->format('j M');

            return $make('festival:'.$festival->id, "Share blessings and guidance for {$festival->name}{$deity} on {$when} — its significance and what devotees can do at home, plus that you can arrange the puja for them on Online Puja.");
        }

        return $make('calendar:'.now()->toDateString(), 'Share the spiritual focus for this week — the current lunar phase or an upcoming auspicious day — and one simple practice your followers can do.');
    }

    /** @return string[] */
    protected function recentIdeaKeys(int $astrologerId): array
    {
        $row = DB::table('brain_memories')->where('k', $this->memKey($astrologerId))->first();

        if (! $row) {
            return [];
        }

        $v = json_decode((string) $row->v, true);

        return is_array($v) ? array_values(array_filter($v, 'is_string')) : [];
    }

    protected function rememberIdea(int $astrologerId, string $key): void
    {
        $keys = $this->recentIdeaKeys($astrologerId);
        $keys[] = $key;
        $keys = array_slice(array_values(array_unique($keys)), -self::RECENT_IDEAS);

        DB::table('brain_memories')->updateOrInsert(
            ['k' => $this->memKey($astrologerId)],
            ['v' => json_encode($keys), 'source' => 'astrologer-social', 'updated_at' => now(), 'created_at' => now()],
        );
    }

    protected function memKey(int $astrologerId): string
    {
        return "astro_ideas_used:{$astrologerId}";
    }

    /** Collapse repeated sentences and cap length — test bios are often junk. */
    protected function cleanBio(string $bio): string
    {
        $bio = trim(preg_replace('/\s+/', ' ', strip_tags($bio)));

        if ($bio === '') {
            return '';
        }

        // Kill back-to-back repeated phrases (common in half-filled test bios),
        // including when there is no whitespace between the repeats.
        $bio = preg_replace('/(.{12,}?[.!?])(?:\s*\1)+/u', '$1', $bio);

        $seen = [];
        $out = [];

        foreach (preg_split('/(?<=[.!?])\s+/', $bio) as $sentence) {
            $norm = mb_strtolower(trim($sentence));

            if ($norm === '' || isset($seen[$norm])) {
                continue;
            }

            $seen[$norm] = true;
            $out[] = trim($sentence);
        }

        return Str::limit(implode(' ', $out), 300);
    }

    /** Split any number of comma-separated id lists into a unique int array. */
    protected function ids(?string ...$lists): array
    {
        $ids = [];

        foreach ($lists as $list) {
            foreach (explode(',', (string) $list) as $piece) {
                $piece = trim($piece);

                if ($piece !== '' && ctype_digit($piece)) {
                    $ids[] = (int) $piece;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /** @return string[] */
    protected function names(string $table, string $column, array $ids): array
    {
        if (! $ids) {
            return [];
        }

        try {
            return DB::table($table)->whereIn('id', $ids)->pluck($column)->filter()->map(fn ($n) => trim((string) $n))->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
