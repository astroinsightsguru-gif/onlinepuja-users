<?php

namespace App\Console\Commands;

use App\Support\SitemapGenerator;
use Illuminate\Console\Command;

class SitemapGenerateCommand extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Rebuild public/sitemap.xml from live blogs and pujas';

    public function handle(): int
    {
        $res = SitemapGenerator::generate();

        if (! $res['ok']) {
            $this->error($res['error']);

            return self::FAILURE;
        }

        $this->info("Sitemap written: {$res['urls']} URLs → {$res['path']}");

        return self::SUCCESS;
    }
}
