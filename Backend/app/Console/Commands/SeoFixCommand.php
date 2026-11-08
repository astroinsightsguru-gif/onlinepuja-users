<?php

namespace App\Console\Commands;

use App\AI\ProviderManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeoFixCommand extends Command
{
    protected $signature = 'seo:fix
        {--god : Enable autonomous fixes beyond the safe set}
        {--limit=10 : Max fixes this run}';

    protected $description = 'Self-healing: auto-fix SEO issues that are safe to change (revertable)';

    public function handle(ProviderManager $ai): int
    {
        $god = (bool) $this->option('god');
        $limit = max(1, (int) $this->option('limit'));

        // Healable types today: blog meta descriptions (+ titles in god mode).
        $types = ['missing_meta_description', 'short_meta_description', 'long_meta_description'];

        if ($god) {
            $types[] = 'long_title';
            $types[] = 'missing_title';
        }

        $issues = DB::table('seo_issues')
            ->where('status', 'open')
            ->where(function ($q) use ($types) {
                $q->whereIn('type', $types)
                  ->orWhere(function ($q2) use ($types) {
                      foreach ($types as $t) {
                          $q2->orWhere('type', $t);
                      }
                  });
            })
            ->where('page_url', 'like', '%/blog/%')
            ->orderByDesc('severity')
            ->limit($limit)
            ->get();

        if ($issues->isEmpty()) {
            $this->info('Nothing healable right now.');

            return self::SUCCESS;
        }

        // Discover blogs schema once.
        $cols = collect(DB::select('SHOW COLUMNS FROM blogs'))->pluck('Field')->flip();
        $descCol = collect(['meta_description', 'seo_description', 'excerpt'])
            ->first(fn ($c) => isset($cols[$c]));
        $titleCol = isset($cols['title']) ? 'title' : null;

        if (! $descCol && ! ($god && $titleCol)) {
            $this->line('No healable columns present on blogs table.');

            return self::SUCCESS;
        }

        $fixed = 0;

        foreach ($issues as $issue) {
            $slug = basename(parse_url($issue->page_url, PHP_URL_PATH) ?? '');
            $blog = DB::table('blogs')->where('slug', $slug)->first();

            if (! $blog) {
                continue;
            }

            $contentText = trim(preg_replace('/\s+/', ' ', strip_tags(
                (string) ($blog->{$descCol === 'excerpt' ? 'excerpt' : ($cols->has('content') ? 'content' : 'description')} ?? '')
            )));

            if ($contentText === '' && isset($cols['description'])) {
                $contentText = (string) $blog->description;
            }

            if (mb_strlen($contentText) < 200) {
                continue; // not enough source to write a good replacement
            }

            $wantTitle = str_contains($issue->type, 'title');
            $col = ($wantTitle && $titleCol) ? $titleCol : $descCol;

            if (! $col) {
                continue;
            }

            $current = (string) ($blog->{$col} ?? '');
            $generated = $this->generate($ai, $wantTitle ? $blog->title : $contentText, $wantTitle);

            if (! $generated) {
                continue;
            }

            $revert = ['table' => 'blogs', 'key' => ['id' => $blog->id], 'changes' => [$col => $current]];

            DB::table('blogs')->where('id', $blog->id)->update([$col => $generated]);

            // Healed the DB row, but a CLI process has no HTTP response to
            // carry a purge header — without this, the fix stays invisible
            // behind the page cache until its TTL happens to lapse.
            \App\Support\LiteSpeedCache::purgeFromCli('/blog/'.$slug, '/blog');

            DB::table('seo_issues')->where('id', $issue->id)->update([
                'status' => 'fixed',
                'fixed_at' => now(),
                'revert_payload' => json_encode($revert),
                'updated_at' => now(),
            ]);

            $fixed++;
            $this->line("✔ fixed [{$issue->type}] {$slug}");
        }

        $this->info("Healed {$fixed} issue(s).".($god ? ' (god mode)' : ''));

        return self::SUCCESS;
    }

    protected function generate(ProviderManager $ai, string $source, bool $isTitle): ?string
    {
        $ask = $isTitle
            ? 'Rewrite this article title to 45-60 characters, keep the exact topic, front-load the subject. JSON: {"title":""}'
            : 'Write a Google meta description of 140-158 characters for this article, include the main topic phrase naturally, active voice, with a soft call to action. JSON: {"description":""}';

        $src = mb_substr($source, 0, 1400);
        $res = $ai->chat('You are an SEO editor. STRICT JSON only.', $ask."\n\nARTICLE:\n".$src,
            ['json' => true, 'temperature' => 0.5, 'max_tokens' => 200]);

        if (! $res['ok']) {
            return null;
        }

        $j = $this->decodeJson($res['text']);
        $val = trim((string) ($j[$isTitle ? 'title' : 'description'] ?? ''));

        if ($val === '') {
            return null;
        }

        if (! $isTitle) {
            $len = mb_strlen($val);

            if ($len < 70 || $len > 170) {
                return null; // don't ship a bad length
            }
        } elseif (($l = mb_strlen($val)) > 65 || $l < 25) {
            return null;
        }

        return $val;
    }

    /**
     * Auto-routed models (auto/pro-chat etc.) answer through a different
     * upstream each call — some fence the JSON, some emit a reasoning
     * preamble first. Take the outermost object rather than assuming the
     * whole reply is bare JSON. Mirrors ContentPipeline::decodeJson().
     */
    protected function decodeJson(string $text): ?array
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        $clean = trim(preg_replace('/```(?:json)?|```/i', '', $text));

        if (is_array($j = json_decode($clean, true))) {
            return $j;
        }

        $start = strpos($clean, '{');
        $end = strrpos($clean, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $j = json_decode(substr($clean, $start, $end - $start + 1), true);

        return is_array($j) ? $j : null;
    }
}
