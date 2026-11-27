<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== SOCIAL TABLES AUDIT ===\n";
echo "social_accounts exists: " . (Schema::hasTable('social_accounts') ? 'YES' : 'NO') . "\n";
if (Schema::hasTable('social_accounts')) {
    $accs = DB::table('social_accounts')->get();
    echo "social_accounts count: " . count($accs) . "\n";
    foreach ($accs as $a) {
        echo "  - ID: {$a->id} | Platform: {$a->platform} | Status: {$a->status} | Token length: " . strlen($a->access_token ?? '') . "\n";
    }
}

echo "\nastrologer_social_accounts exists: " . (Schema::hasTable('astrologer_social_accounts') ? 'YES' : 'NO') . "\n";
if (Schema::hasTable('astrologer_social_accounts')) {
    $astroAccs = DB::table('astrologer_social_accounts')->get();
    echo "astrologer_social_accounts count: " . count($astroAccs) . "\n";
    foreach ($astroAccs as $a) {
        echo "  - AstrologerID: {$a->astrologer_id} | Platform: {$a->platform} | Handle: {$a->handle} | Autopilot: {$a->is_autopilot_enabled} | Token: " . substr($a->access_token ?? '', 0, 15) . "... (len: " . strlen($a->access_token ?? '') . ")\n";
    }
}

echo "\nsocial_posts exists: " . (Schema::hasTable('social_posts') ? 'YES' : 'NO') . "\n";
if (Schema::hasTable('social_posts')) {
    $posts = DB::table('social_posts')->get();
    echo "social_posts count: " . count($posts) . "\n";
}

echo "\nastrologer_social_creatives exists: " . (Schema::hasTable('astrologer_social_creatives') ? 'YES' : 'NO') . "\n";
if (Schema::hasTable('astrologer_social_creatives')) {
    $creatives = DB::table('astrologer_social_creatives')->get();
    echo "astrologer_social_creatives count: " . count($creatives) . "\n";
    foreach ($creatives as $c) {
        echo "  - ID: {$c->id} | AstroID: {$c->astrologer_id} | Title: {$c->title} | Status: {$c->status} | PublishedAt: {$c->published_at}\n";
    }
}
