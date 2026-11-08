<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\frontend\SitemapController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class GenerateSitemapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate {--ping : Ping search engines after generation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate static XML sitemaps for Google Search Console and ping search engines';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating XML sitemaps for OnlinePuja.live...');

        $controller = new SitemapController();

        // 1. Generate master index sitemap
        $indexResponse = $controller->index();
        $indexPath = public_path('sitemap.xml');
        File::put($indexPath, $indexResponse->getContent());
        $this->line("  ✓ Generated: public/sitemap.xml");

        // 2. Generate each sub-sitemap
        $types = ['pujas', 'pandits', 'astrologers', 'blogs', 'dailyhoroscope', 'pages'];
        foreach ($types as $type) {
            $subResponse = $controller->sitemapType($type);
            $subPath = public_path("sitemap-{$type}.xml");
            File::put($subPath, $subResponse->getContent());
            $this->line("  ✓ Generated: public/sitemap-{$type}.xml");
        }

        $this->info('All XML sitemaps generated successfully!');

        // 3. Ping search engines if requested
        if ($this->option('ping')) {
            $this->info('Pinging Google & Bing Webmaster endpoints...');
            $sitemapUrl = urlencode('https://onlinepuja.live/sitemap.xml');

            try {
                $resGoogle = Http::timeout(10)->get("https://www.google.com/ping?sitemap={$sitemapUrl}");
                $this->line("  Google Ping: " . ($resGoogle->successful() ? 'OK (200)' : 'HTTP ' . $resGoogle->status()));
            } catch (\Exception $e) {
                $this->warn("  Google Ping skipped: " . $e->getMessage());
            }

            try {
                $resBing = Http::timeout(10)->get("https://www.bing.com/ping?sitemap={$sitemapUrl}");
                $this->line("  Bing Ping: " . ($resBing->successful() ? 'OK (200)' : 'HTTP ' . $resBing->status()));
            } catch (\Exception $e) {
                $this->warn("  Bing Ping skipped: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
