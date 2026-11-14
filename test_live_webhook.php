<?php
require_once '/home/onlinepujalive/htdocs/onlinepuja.live/vendor/autoload.php';
$app = require_once '/home/onlinepujalive/htdocs/onlinepuja.live/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$testJid = '919999988888@s.whatsapp.net';
$payload = [
    'event' => 'messages.upsert',
    'instance' => 'onlinepuja',
    'data' => [
        'key' => [
            'remoteJid' => $testJid,
            'fromMe' => false,
            'id' => 'TEST_' . uniqid(),
        ],
        'pushName' => 'Rahul Sharma Devotee',
        'message' => [
            'conversation' => 'Pranam pandit ji, mujhe Kashi me Rudrabhishek karwana hai',
        ],
    ],
];

$ctrl = new App\Http\Controllers\API\WhatsAppWebhookController();
$wa = new App\Services\EvolutionWaService();
$req = Illuminate\Http\Request::create('/api/whatsapp/webhook', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
$resp = $ctrl->handle($req, $wa);
echo "Webhook Response: " . $resp->getContent() . "\n";

// Verify database insertion
$msg = Illuminate\Support\Facades\DB::table('whatsapp_messages')->where('thread_jid', $testJid)->latest('id')->first();
$th = Illuminate\Support\Facades\DB::table('whatsapp_threads')->where('remote_jid', $testJid)->first();
echo "Inserted Message Direction: " . ($msg->direction ?? 'none') . "\n";
echo "Inserted Message Text: " . ($msg->message ?? 'none') . "\n";
echo "Thread Name: " . ($th->name ?? 'none') . "\n";
echo "Thread Unread Count: " . ($th->unread_count ?? 'none') . "\n";

// Clean up test records
Illuminate\Support\Facades\DB::table('whatsapp_messages')->where('thread_jid', $testJid)->delete();
Illuminate\Support\Facades\DB::table('whatsapp_threads')->where('remote_jid', $testJid)->delete();
echo "Cleaned up test data successfully.\n";
