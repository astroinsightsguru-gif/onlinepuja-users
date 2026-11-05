<?php

namespace App\Console\Commands;

use App\Brain\RunLog;
use App\Brain\SiloEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SiloAuditCommand extends Command
{
    protected $signature = 'brain:silo
        {--json : Emit machine-readable JSON}';

    protected $description = 'Audit site-wide topical authority silos, cluster mapping, and pillar page internal link equity';

    public function handle(): int
    {
        $this->info("=== OnlinePuja 5 Sacred Topic Silos Audit ===");
        $this->line("Evaluating topical clustering and link equity distribution...\n");

        $blogs = DB::table('blogs')
            ->where('isActive', 1)
            ->where('isDelete', 0)
            ->get(['id', 'title', 'slug', 'description']);

        $siloStats = [];
        foreach (SiloEngine::SILOS as $key => $silo) {
            $siloStats[$key] = [
                'name' => $silo['name'],
                'pillar_url' => $silo['pillar_url'],
                'pillar_title' => $silo['pillar_title'],
                'article_count' => 0,
                'pillar_links_received' => 0,
                'articles' => [],
            ];
        }

        foreach ($blogs as $blog) {
            $text = $blog->title . ' ' . strip_tags($blog->description ?? '');
            $siloKey = SiloEngine::detectSilo($text);

            $siloStats[$siloKey]['article_count']++;
            $siloStats[$siloKey]['articles'][] = [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
            ];

            // Count pillar mentions in html
            $pillarUrl = SiloEngine::SILOS[$siloKey]['pillar_url'];
            if (str_contains($blog->description ?? '', $pillarUrl)) {
                $siloStats[$siloKey]['pillar_links_received']++;
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode($siloStats, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        $headers = ['Silo / Pillar', 'Pillar URL', 'Cluster Articles', 'Pillar Links', 'Health'];
        $rows = [];

        foreach ($siloStats as $key => $data) {
            $health = '🟢 Healthy';
            if ($data['article_count'] === 0) {
                $health = '🔴 Empty Silo';
            } elseif ($data['pillar_links_received'] < ($data['article_count'] * 0.5)) {
                $health = '🟡 Needs Links';
            }

            $rows[] = [
                $data['name'],
                $data['pillar_url'],
                $data['article_count'],
                $data['pillar_links_received'],
                $health,
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info("=== Pillar Recommendations ===");
        foreach ($siloStats as $key => $data) {
            if ($data['pillar_links_received'] < $data['article_count']) {
                $diff = $data['article_count'] - $data['pillar_links_received'];
                $this->line(sprintf("• <fg=cyan>%s</>: %d articles missing internal link to pillar <fg=yellow>%s</>", $data['name'], $diff, $data['pillar_url']));
            }
        }

        RunLog::record(
            'brain:silo',
            'silo',
            'ok',
            "Audited " . count($blogs) . " posts across 5 Sacred Silos",
            ['silo_stats' => $siloStats, 'total_blogs' => count($blogs)],
            count($blogs),
            0,
            'audit'
        );

        return self::SUCCESS;
    }
}
