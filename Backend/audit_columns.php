<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== social_accounts columns ===\n";
print_r(Schema::getColumnListing('social_accounts'));

echo "\n=== astrologer_social_accounts columns ===\n";
print_r(Schema::getColumnListing('astrologer_social_accounts'));

echo "\n=== social_posts columns ===\n";
print_r(Schema::getColumnListing('social_posts'));
