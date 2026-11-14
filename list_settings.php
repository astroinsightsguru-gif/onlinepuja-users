<?php
require '/home/onlinepujalive/htdocs/onlinepuja.live/vendor/autoload.php';
$app = require '/home/onlinepujalive/htdocs/onlinepuja.live/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$keys = \DB::table('brain_settings')->pluck('key')->toArray();
echo json_encode($keys, JSON_PRETTY_PRINT);
