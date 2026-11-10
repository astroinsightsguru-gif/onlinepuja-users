<?php

namespace App\Console\Commands;

use App\AI\ProviderManager;
use App\Brain\InternalLinker;
use App\Brain\RunLog;
use App\Support\LiteSpeedCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Refreshes the oldest published articles. Updating and re-dating existing
 * content is consistently the highest-ROI SEO action there is — Google rewards
 * freshness, and an article that already has some authority climbs faster from
 * a refresh than a brand-new page does from scratch. Runs weekly, capped.
 *
 * It rewrites the body (tighter intro, an extra useful section, current-year
 * references, a short FAQ), re-weaves internal links, re-dates the post so it
 * resurfaces in "recent", and purges the cache.
 */
class ContentRefreshCommand extends Command
{
    protected $signature = 'content:refresh
        {--limit=2 : How many articles to refresh this run}
        {--min-age-days=21 : Only refresh posts not updated in at least this many days}';

    protected $description = 'AI-refresh the stalest published articles (freshness SEO)';

    public function handle(ProviderManager $ai): int
    {
        $minAge = (int) $this->option('min-age-days');
        $limit = max(1, (int) $this->option('limit'));

        $cutoff = now()->subDays($minAge);

        $posts = DB::table('blogs')
            ->where('isActive', 1)
            ->where('isDelete', 0)
            ->where('updated_at', '<=', $cutoff)
            ->whereNotNull('description')
            ->orderBy('updated_at') // stalest first
            ->limit($limit)
            ->get();

        if ($posts->isEmpty()) {
            $this->info("Nothing stale enough to refresh (older than {$minAge} days).");

            return self::SUCCESS;
        }

        $ok = 0;
        $failed = 0;
        $refreshed = [];

        foreach ($posts as $post) {
            $res = $this->refreshOne($post, $ai);

            if ($res['ok']) {
                $ok++;
                $refreshed[] = $post->slug;
                $this->info("REFRESHED: /blog/{$post->slug}".($res['links'] ? " (+{$res['links']} links)" : ''));
            } else {
                $failed++;
                $this->error("#{$post->id} {$post->slug}: {$res['error']}");
            }
        }

        $this->line("Refreshed: {$ok} · Failed: {$failed}");

        RunLog::recordUnlessNoop(
            'content:refresh', 'blog',
            $failed > 0 ? ($ok > 0 ? 'partial' : 'fail') : 'ok',
            "Refreshed {$ok} article(s)".($failed ? ", {$failed} failed" : ''),
            ['slugs' => $refreshed],
            $ok, $failed
        );

        return self::SUCCESS;
    }

    /** @return array{ok:bool,links:int,error:string} */
    protected function refreshOne(object $post, ProviderManager $ai): array
    {
        $year = now()->year;

        $system = <<<TXT
You are the senior content editor for Online Puja (onlinepuja.live), an Indian platform to book
verified pandits for pujas, havans and astrology online. You are REFRESHING an existing article
to make it more useful and current for {$year}. Keep the same topic and title intent. Improve the
opening, add one genuinely useful new section, update any dated references to {$year}, and add a
short 2-question FAQ at the end if none exists. Do not invent prices or rituals. Return STRICT JSON:
{"html":string,"meta_description":string(140-158 chars)}. "html" is ONLY the article fragment
(<h2>/<h3>/<p>/<ul>), no <html>/<body>/styles. 1000-1300 words.
TXT;

        $prompt = "TITLE: {$post->title}\n\nCURRENT ARTICLE HTML:\n".mb_substr((string) $post->description, 0, 8000);

        $res = $ai->chat($system, $prompt, ['json' => true, 'temperature' => 0.7, 'max_tokens' => 3400]);

        if (! $res['ok']) {
            return ['ok' => false, 'links' => 0, 'error' => $res['error']];
        }

        $j = $this->decode($res['text']);
        $html = trim((string) ($j['html'] ?? ''));

        if ($html === '' || mb_strlen(strip_tags($html)) < 400) {
            return ['ok' => false, 'links' => 0, 'error' => 'model returned too little content'];
        }

        // Re-weave internal links into the refreshed body.
        $linked = InternalLinker::inject($html, $post->slug);
        $html = $linked['html'];

        $update = [
            'description' => $html,
            'postedOn' => now(),   // resurface as fresh
            'updated_at' => now(),
            'modifiedBy' => 1,
        ];

        if (! empty($j['meta_description'])) {
            $update['meta_description'] = mb_substr($j['meta_description'], 0, 158);
        }

        try {
            DB::table('blogs')->where('id', $post->id)->update($update);
        } catch (\Throwable $e) {
            return ['ok' => false, 'links' => 0, 'error' => 'update failed: '.$e->getMessage()];
        }

        LiteSpeedCache::purgeFromCli('/blog/'.$post->slug, '/blog', '/');

        DB::table('outcomes')->insert([
            'channel' => 'web', 'entity_type' => 'blog', 'entity_id' => $post->id,
            'metric' => 'refreshed', 'value' => 1, 'at' => now(),
            'meta' => json_encode(['slug' => $post->slug, 'links_added' => $linked['links_added']]),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return ['ok' => true, 'links' => $linked['links_added'], 'error' => ''];
    }

    protected function decode(string $text): array
    {
        $clean = trim(preg_replace('/```(?:json)?|```/i', '', $text));
        $j = json_decode($clean, true);

        if (is_array($j)) {
            return $j;
        }

        $s = strpos($clean, '{');
        $e = strrpos($clean, '}');

        if ($s !== false && $e !== false && $e > $s) {
            $j = json_decode(substr($clean, $s, $e - $s + 1), true);
        }

        return is_array($j) ? $j : [];
    }
}
