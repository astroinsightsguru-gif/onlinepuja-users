<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$forecast = \App\Brain\TrafficForecaster::forecast(60);
echo json_encode([
    'forecast_keys' => array_keys($forecast),
    'forecast_data' => $forecast,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
