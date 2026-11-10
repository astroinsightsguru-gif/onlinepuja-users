<?php

namespace App\Brain;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The feedback half of the growth engine.
 *
 * `social:collect` has always written reach/likes/comments/shares into
 * `outcomes`, but nothing ever read them back — the system published forever
 * without learning anything. This class closes that loop: it groups published
 * posts by the dimensions we control (hour of day, caption shape, post kind,
 * idea source) and works out which settings actually earn engagement, then
 * hands that back to the planners and the copywriter as guidance.
 *
 * Two design rules keep it honest:
 *
 *  1. **Never learn from noise.** A dimension is only reported once it has
 *     MIN_SAMPLES posts behind it. Below that the getters return null and every
 *     caller keeps its hand-tuned default. On day one this class changes
 *     nothing, which is the correct behaviour for an empty dataset.
 *
 *  2. **Shrink toward the median.** A bucket's score is pulled toward the
 *     global central value in proportion to how little data it has, so one
 *     lucky post can never make "post at 3am" look like a strategy. Medians,
 *     not means, throughout: engagement is heavy-tailed, and an early test
 *     proved the difference — a single post that went 100x lifted the global
 *     mean so far that the *worst* hour, having the least data and so being
 *     shrunk hardest toward that prior, was reported as the best.
 */
class Learner
{
    /** Where the computed playbook is cached between runs. */
    public const MEMORY_KEY = 'learned_playbook';

    /** Posts a bucket needs before its lesson is trusted at all. */
    protected const MIN_SAMPLES = 5;

    /** Buckets we will actually report, out of those that clear MIN_SAMPLES. */
    protected const MIN_BUCKET_SAMPLES = 2;

    /** Shrinkage strength: a bucket with this many posts gets half its own weight. */
    protected const PRIOR_WEIGHT = 5.0;

    /** A comment is worth more than a like, a share more than a comment. */
    protected const WEIGHTS = ['likes' => 1, 'comments' => 3, 'shares' => 5];

    protected const TZ = 'Asia/Kolkata';

    /**
     * The current playbook, as cached by the last `brain:learn` run.
     *
     * @return array<string,mixed>
     */
    public function playbook(): array
    {
        $row = DB::table('brain_memories')->where('k', self::MEMORY_KEY)->first();

        if (! $row) {
            return [];
        }

        $data = json_decode((string) $row->v, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Recompute the playbook from `outcomes` and store it.
     *
     * @return array<string,mixed>
     */
    public function rebuild(): array
    {
        $posts = $this->scoredPosts();

        $playbook = [
            'generated_at' => now()->toDateTimeString(),
            'samples' => $posts->count(),
            'ready' => $posts->count() >= self::MIN_SAMPLES,
            'hours' => [],
            'caption' => [],
            'kinds' => [],
            'astrologer_sources' => [],
        ];

        if ($playbook['ready']) {
            $playbook['hours'] = $this->learnHours($posts);
            $playbook['caption'] = $this->learnCaptionShape($posts);
            $playbook['kinds'] = $this->rankBy($posts, fn ($p) => $p->kind);
            $playbook['astrologer_sources'] = $this->learnAstrologerSources($posts);
        }

        DB::table('brain_memories')->updateOrInsert(
            ['k' => self::MEMORY_KEY],
            ['v' => json_encode($playbook), 'source' => 'brain:learn', 'updated_at' => now(), 'created_at' => now()]
        );

        return $playbook;
    }

    // ---------------------------------------------------------------- getters
    // Every getter returns null when the evidence is too thin, so callers can
    // write `$learned ?? $default` and stay correct on an empty database.

    /**
     * Learned peak hours (IST, best first) for a platform, or null to keep the
     * hand-tuned defaults in SocialTiming.
     *
     * @return array<int,int>|null
     */
    public function bestHours(string $platform): ?array
    {
        $hours = $this->playbook()['hours'][$platform] ?? null;

        if (! is_array($hours) || $hours === []) {
            return null;
        }

        return array_values(array_map('intval', array_slice($hours, 0, 3)));
    }

    /**
     * The idea source ('profile'|'service'|'calendar') that performs best for
     * this astrologer, or null to keep the plain rotation.
     */
    public function preferredSource(int $astrologerId): ?string
    {
        return $this->playbook()['astrologer_sources'][(string) $astrologerId] ?? null;
    }

    /**
     * A short natural-language brief for the copywriter's system prompt —
     * empty string when we have nothing worth saying, so the prompt is
     * unchanged until the data earns a place in it.
     */
    public function copywritingBrief(): string
    {
        $book = $this->playbook();

        if (! ($book['ready'] ?? false)) {
            return '';
        }

        $lines = [];
        $caption = $book['caption'] ?? [];

        if (! empty($caption['length'])) {
            $lines[] = match ($caption['length']) {
                'short' => 'Short captions (under ~120 characters) outperform long ones here — lead with the hook and stop.',
                'long' => 'Longer, story-led captions (300+ characters) outperform short ones here — give the ritual real context.',
                default => 'Mid-length captions (roughly 120-300 characters) outperform both very short and very long ones here.',
            };
        }

        if (! empty($caption['hashtags'])) {
            $lines[] = match ($caption['hashtags']) {
                'few' => 'Posts with only 2-4 hashtags outperform hashtag-heavy ones — keep tags tight and relevant.',
                'many' => 'Posts with 10+ well-targeted hashtags outperform sparse ones — use the full tag budget.',
                default => 'Posts with roughly 5-9 hashtags perform best.',
            };
        }

        if (isset($caption['question'])) {
            $lines[] = $caption['question']
                ? 'Captions that ask the reader a direct question earn measurably more comments — include one.'
                : 'Direct questions are not driving engagement here; a clear devotional statement works better than a prompt.';
        }

        if (isset($caption['emoji'])) {
            $lines[] = $caption['emoji']
                ? 'Emojis are helping — keep using them, sparingly and devotionally.'
                : 'Emoji-heavy captions are underperforming here; keep the copy plainer.';
        }

        if (! empty($book['kinds'])) {
            $best = array_key_first($book['kinds']);
            $label = match ($best) {
                'festival' => 'festival and calendar-led posts',
                'blog_promo' => 'posts that promote a full article',
                'astrologer' => 'personal, astrologer-voice posts',
                default => "\"{$best}\" posts",
            };
            $lines[] = "Our best-performing format so far is {$label} — lean that way when the idea allows.";
        }

        if ($lines === []) {
            return '';
        }

        return "WHAT IS ACTUALLY WORKING ON OUR ACCOUNTS (learned from live engagement — follow this over generic best practice):\n- "
            .implode("\n- ", $lines);
    }

    // ------------------------------------------------------------- internals

    /**
     * Every published post that has metrics, with an engagement score attached.
     *
     * Reach is a cumulative gauge re-read hourly, so we take the highest value
     * seen per post rather than summing the rows. Where reach is known the
     * score is a rate (engagement per person reached), which is what makes a
     * small account's post comparable to a large one's.
     *
     * @return \Illuminate\Support\Collection<int,object>
     */
    protected function scoredPosts()
    {
        $metrics = DB::table('outcomes')
            ->whereIn('metric', ['reach', 'likes', 'comments', 'shares'])
            ->whereNotNull('meta')
            ->get(['metric', 'value', 'meta']);

        $byPost = [];

        foreach ($metrics as $row) {
            $meta = json_decode((string) $row->meta, true);
            $postId = (int) ($meta['social_post_id'] ?? 0);

            if ($postId === 0) {
                continue;
            }

            // Highest reading wins — these counters only ever climb.
            $byPost[$postId][$row->metric] = max(
                (int) ($byPost[$postId][$row->metric] ?? 0),
                (int) $row->value
            );
        }

        if ($byPost === []) {
            return collect();
        }

        $posts = DB::table('social_posts')
            ->whereIn('id', array_keys($byPost))
            ->where('status', 'published')
            ->get(['id', 'platform', 'astrologer_id', 'caption', 'hashtags', 'link_url', 'published_at', 'meta']);

        return $posts->map(function ($post) use ($byPost) {
            $m = $byPost[$post->id];

            $engagement = 0.0;

            foreach (self::WEIGHTS as $metric => $weight) {
                $engagement += $weight * (int) ($m[$metric] ?? 0);
            }

            $reach = (int) ($m['reach'] ?? 0);

            $post->score = $reach > 0 ? $engagement / $reach : $engagement;
            $post->reach = $reach;
            $post->hour = $post->published_at
                ? Carbon::parse($post->published_at)->setTimezone(self::TZ)->hour
                : null;

            $meta = json_decode((string) $post->meta, true) ?: [];
            $post->kind = $meta['kind'] ?? ($post->astrologer_id ? 'astrologer' : ($post->link_url ? 'blog_promo' : 'festival'));
            $post->idea_source = $meta['idea_source'] ?? null;

            return $post;
        })->values();
    }

    /**
     * Best hours per platform. Grouped inside each platform because a good
     * hour on Instagram is not a good hour on Facebook.
     *
     * @return array<string,array<int,int>>
     */
    protected function learnHours($posts): array
    {
        $out = [];

        foreach ($posts->groupBy('platform') as $platform => $group) {
            if ($group->count() < self::MIN_SAMPLES) {
                continue;
            }

            $ranked = $this->rankBy($group, fn ($p) => $p->hour === null ? null : (string) $p->hour);

            if ($ranked !== []) {
                $out[$platform] = array_map('intval', array_keys($ranked));
            }
        }

        return $out;
    }

    /**
     * Which caption shape wins: length band, hashtag density, whether asking a
     * question helps, whether emojis help.
     *
     * @return array<string,mixed>
     */
    protected function learnCaptionShape($posts): array
    {
        $out = [];

        $length = $this->rankBy($posts, function ($p) {
            $n = mb_strlen((string) $p->caption);

            return $n < 120 ? 'short' : ($n <= 300 ? 'medium' : 'long');
        });

        if ($length !== []) {
            $out['length'] = array_key_first($length);
        }

        $tags = $this->rankBy($posts, function ($p) {
            $n = substr_count((string) $p->hashtags, '#');

            return $n <= 4 ? 'few' : ($n <= 9 ? 'some' : 'many');
        });

        if ($tags !== []) {
            $out['hashtags'] = array_key_first($tags);
        }

        $question = $this->rankBy($posts, fn ($p) => str_contains((string) $p->caption, '?') ? 'yes' : 'no');

        if ($question !== []) {
            $out['question'] = array_key_first($question) === 'yes';
        }

        $emoji = $this->rankBy($posts, fn ($p) => preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', (string) $p->caption) ? 'yes' : 'no');

        if ($emoji !== []) {
            $out['emoji'] = array_key_first($emoji) === 'yes';
        }

        return $out;
    }

    /**
     * Best idea source per astrologer, keyed by astrologer id as a string so it
     * survives a JSON round-trip.
     *
     * @return array<string,string>
     */
    protected function learnAstrologerSources($posts): array
    {
        $out = [];

        foreach ($posts->whereNotNull('astrologer_id')->groupBy('astrologer_id') as $astrologerId => $group) {
            if ($group->count() < self::MIN_SAMPLES) {
                continue;
            }

            $ranked = $this->rankBy($group, fn ($p) => $p->idea_source);

            if ($ranked !== []) {
                $out[(string) $astrologerId] = (string) array_key_first($ranked);
            }
        }

        return $out;
    }

    /**
     * Rank buckets best-first by shrunken median score.
     *
     * The shrinkage is the whole point: a bucket's own typical score is blended
     * with the global typical score, weighted by how many posts it actually
     * has. A bucket with two posts barely moves off the global value; one with
     * fifty is trusted on its own evidence. Without this, the first post to get
     * a lucky share would permanently define "the best time to post".
     *
     * @param  callable(object):?string  $key
     * @return array<string,float>  bucket => shrunken score, best first
     */
    protected function rankBy($posts, callable $key): array
    {
        $buckets = [];

        foreach ($posts as $post) {
            $k = $key($post);

            if ($k === null || $k === '') {
                continue;
            }

            $buckets[(string) $k][] = (float) $post->score;
        }

        if (count($buckets) < 2) {
            return []; // Nothing to compare against — no lesson to draw.
        }

        $prior = $this->median(array_merge(...array_values($buckets)));

        $scored = [];

        foreach ($buckets as $k => $scores) {
            $n = count($scores);

            if ($n < self::MIN_BUCKET_SAMPLES) {
                continue;
            }

            $confidence = $n / ($n + self::PRIOR_WEIGHT);

            $scored[$k] = $confidence * $this->median($scores) + (1 - $confidence) * $prior;
        }

        arsort($scored);

        return $scored;
    }

    /** @param  array<int,float>  $values */
    protected function median(array $values): float
    {
        if ($values === []) {
            return 0.0;
        }

        sort($values);
        $n = count($values);
        $mid = intdiv($n, 2);

        return $n % 2 ? $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
    }
}
