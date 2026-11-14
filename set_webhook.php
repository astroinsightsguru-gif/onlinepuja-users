<?php
require_once '/home/onlinepujalive/htdocs/onlinepuja.live/vendor/autoload.php';
$payload = [
    'webhook' => [
        'enabled' => true,
        'url' => 'https://onlinepuja.live/api/whatsapp/webhook',
        'byEvents' => false,
        'base64' => false,
        'events' => [
            'MESSAGES_UPSERT',
            'MESSAGES_UPDATE',
            'CONNECTION_UPDATE',
        ],
    ],
];

$ch = curl_init('http://127.0.0.1:18080/webhook/set/onlinepuja');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: bebf8554b69b4eff665465956069890dadeb84547a964ba0ac129e43e98576a2',
    'Content-Type: application/json',
]);
$resp = curl_exec($ch);
curl_close($ch);
echo $resp . "\n";
