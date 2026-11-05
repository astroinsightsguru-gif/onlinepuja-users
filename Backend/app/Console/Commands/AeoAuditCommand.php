<?php

namespace App\Console\Commands;

use App\Brain\AeoEngine;
use App\Brain\RunLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AeoAuditCommand extends Command
{
    protected $signature = 'brain:aeo
        {--fix : Automatically synthesize and inject missing AEO direct answer blocks into published blogs}
        {--limit=20 : Maximum number of posts to process}';

    protected $description = 'Audit published articles for Answer Engine Optimization (ChatGPT, Perplexity, Gemini citations) and optionally auto-fix them';

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $limit = (int) $this->option('limit');

        $this->info("=== OnlinePuja Answer Engine Optimization (AEO) Engine ===");
        $this->line("Mode: " . ($fix ? "AUDIT & AUTO-FIX" : "AUDIT ONLY (Dry Run)"));
        $this->newLine();

        $blogs = DB::table('blogs')
            ->where('isActive', 1)
            ->where('isDelete', 0)
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'description']);

        if ($blogs->isEmpty()) {
            $this->warn("No published blog posts found.");
            return self::SUCCESS;
        }

        $scores = [];
        $fixedCount = 0;

        foreach ($blogs as $blog) {
            $html = (string) ($blog->description ?? '');
            $audit = AeoEngine::audit($html);
            $scores[] = $audit['score'];

            $statusIcon = $audit['score'] >= 80 ? '✅' : ($audit['score'] >= 60 ? '⚠️' : '❌');
            $this->line(sprintf("%s [#%d] %s — Score: %d/100", $statusIcon, $blog->id, mb_strimwidth($blog->title, 0, 50, '...'), $audit['score']));

            if (!empty($audit['issues'])) {
                foreach ($audit['issues'] as $issue) {
                    $this->line("   ↳ <fg=yellow>{$issue}</>");
                }
            }

            if ($fix && !$audit['features']['has_direct_answer_block']) {
                $this->line("   ⚡ Synthesizing citable AEO block...");
                $aeo = AeoEngine::synthesizeAeoBlock($blog->title, $html);

                if (!empty($aeo['html_snippet'])) {
                    $newHtml = $html;
                    if (preg_match('/<\/h2>/i', $newHtml, $matches, PREG_OFFSET_CAPTURE)) {
                        $insertPos = $matches[0][1] + strlen($matches[0][0]);
                        $newHtml = substr($newHtml, 0, $insertPos) . "\n" . $aeo['html_snippet'] . "\n" . substr($newHtml, $insertPos);
                    } else {
                        $newHtml = $aeo['html_snippet'] . "\n" . $newHtml;
                    }

                    DB::table('blogs')->where('id', $blog->id)->update([
                        'description' => $newHtml,
                        'updated_at' => now(),
                    ]);

                    $fixedCount++;
                    $this->info("   ✨ Injected direct answer block into blog #{$blog->id}!");
                }
            }
        }

        $avgScore = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0;
        $this->newLine();
        $this->info("==========================================");
        $this->info(sprintf("Audit Complete: %d post(s) analyzed.", count($blogs)));
        $this->info(sprintf("Average AEO Quotability Score: %s/100", $avgScore));
        if ($fix) {
            $this->info(sprintf("Auto-fixed: %d post(s) injected with direct answers.", $fixedCount));
        } else {
            $this->line("Run with <fg=green>--fix</> to auto-synthesize and inject direct answers.");
        }
        $this->info("==========================================");

        RunLog::record(
            'brain:aeo',
            'aeo',
            'ok',
            "Audited " . count($blogs) . " posts, avg score {$avgScore}/100, fixed {$fixedCount}",
            ['avg_score' => $avgScore, 'fixed' => $fixedCount, 'posts_checked' => count($blogs)],
            $fixedCount,
            0,
            $fix ? 'command' : 'audit'
        );

        return self::SUCCESS;
    }
}
