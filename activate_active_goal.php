<?php
require '/home/onlinepujalive/htdocs/onlinepuja.live/vendor/autoload.php';
$app = require_once '/home/onlinepujalive/htdocs/onlinepuja.live/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// 1. Mark past goal completed
DB::table('goals')->where('id', 1)->update([
    'status' => 'completed',
    'updated_at' => now(),
]);

// 2. Generate 60-day plan arithmetic from TrafficForecaster
$forecast = \App\Brain\TrafficForecaster::forecast(60);
$aggressiveProjections = $forecast['projections']['aggressive'] ?? [];

$curve = [];
for ($d = 1; $d <= 60; $d++) {
    $fraction = pow($d / 60, 1.4);
    $curve[$d] = (int) round(502 + (1284 - 502) * $fraction);
}

// 3. Check if active 60d goal already exists
$existing = DB::table('goals')
    ->where('title', 'like', '%86,600%')
    ->first();

if (!$existing) {
    $goalId = DB::table('goals')->insertGetId([
        'title' => '86,600 Devotee Visitors & ₹56.2L Revenue in 60 Days',
        'metric' => 'pageviews',
        'target' => 86600,
        'start_date' => '2026-11-11',
        'deadline' => '2027-01-10',
        'baseline_daily' => 502,
        'channels' => json_encode(['seo_aeo', 'reels_shorts', 'whatsapp_blitz', 'astromall_mesh', 'b2b_partners']),
        'status' => 'active',
        'plan_arithmetic' => json_encode([
            'projected_curve' => $curve,
            'avg_order_value' => 2500,
            'projected_orders' => 2252,
            'projected_gross_revenue' => 5630000,
            'harvestable_monthly_visits' => 16303,
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Insert today's initial snapshot
    DB::table('goal_snapshots')->insert([
        'goal_id' => $goalId,
        'date' => '2026-11-11',
        'value' => 502,
        'sources' => json_encode(['organic_search' => 310, 'social' => 120, 'direct' => 72]),
        'created_at' => now(),
    ]);

    echo "✓ New 60-Day Hyper-Growth Goal #{$goalId} created and activated!\n";
} else {
    echo "• Active 60-Day Goal #{$existing->id} already exists.\n";
}

// 4. Test evaluate with GoalAchievingEngine
$audit = \App\Brain\GoalAchievingEngine::auditAndAchieve(false);
echo json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
