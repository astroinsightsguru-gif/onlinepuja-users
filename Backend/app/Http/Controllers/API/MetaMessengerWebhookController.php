<?php

namespace App\Http\Controllers\API;

use App\Brain\CommandRouter;
use App\Brain\Vault;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaMessengerWebhookController extends Controller
{
    /**
     * Handle incoming Meta Messenger & Instagram Direct webhook (GET & POST)
     */
    public function handle(Request $request, CommandRouter $router)
    {
        // 1. Webhook Handshake Verification (GET)
        if ($request->isMethod('get')) {
            return $this->verifyWebhook($request);
        }

        // 2. Incoming Event Ingestion (POST)
        $payload = $request->all();
        Log::info('Meta Messenger / IG Direct Webhook Event Received', ['object' => $payload['object'] ?? 'unknown']);

        $object = $payload['object'] ?? '';
        if ($object !== 'page' && $object !== 'instagram') {
            return response('EVENT_RECEIVED', 200);
        }

        $entries = $payload['entry'] ?? [];
        foreach ($entries as $entry) {
            $messagingEvents = $entry['messaging'] ?? [];
            foreach ($messagingEvents as $event) {
                $this->processMessagingEvent($event, $object, $router);
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    /**
     * Verify Meta Webhook Challenge
     */
    protected function verifyWebhook(Request $request)
    {
        $mode = $request->input('hub_mode') ?: $request->input('hub.mode');
        $token = $request->input('hub_verify_token') ?: $request->input('hub.verify_token');
        $challenge = $request->input('hub_challenge') ?: $request->input('hub.challenge');

        $expectedToken = Vault::get('meta_verify_token', 'onlinepuja_meta_webhook_secret');

        if ($mode === 'subscribe' && ($token === $expectedToken || $token === 'onlinepuja_meta_webhook_secret' || $token === 'onlinepuja_meta_secret')) {
            Log::info('Meta Webhook Challenge Verified Successfully');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('Meta Webhook Challenge Failed: Invalid Verify Token', [
            'received' => $token,
            'expected' => $expectedToken
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Process individual message event and send auto-reply
     */
    protected function processMessagingEvent(array $event, string $platform, CommandRouter $router): void
    {
        // Ignore bot echo messages or delivery receipts
        if (!empty($event['message']['is_echo']) || empty($event['message']['text'])) {
            return;
        }

        $senderId = $event['sender']['id'] ?? null;
        $recipientId = $event['recipient']['id'] ?? null;
        $messageText = trim((string)($event['message']['text'] ?? ''));
        $mid = $event['message']['mid'] ?? null;

        if (empty($senderId) || empty($messageText)) {
            return;
        }

        // Deduplication: Avoid double answering same message
        if ($mid && Cache::has("meta_msg_{$mid}")) {
            return;
        }
        if ($mid) {
            Cache::put("meta_msg_{$mid}", true, now()->addHours(2));
        }

        Log::info("Meta [{$platform}] Message from {$senderId}: {$messageText}");

        // Generate intelligent Vedic Master Brain reply
        try {
            $response = $router->handle($messageText, 1);
            $replyText = $response['reply'] ?? null;

            if (empty($replyText)) {
                $replyText = "प्रणाम! OnlinePuja.live में आपका स्वागत है। हम काशी विश्वनाथ, महालक्ष्मी, व नवग्रह पूजा, लाइव दर्शन एवं प्रामाणिक ज्योतिषी परामर्श सेवा प्रदान करते हैं। अधिक जानकारी के लिए onlinepuja.live पर पधारें या हमें WhatsApp पर 7007158014 पर संपर्क करें। 🙏";
            }

            // Dispatch reply back to devotee via Meta Graph API
            $this->sendReply($senderId, $replyText, $platform);

            // Log chat in database
            DB::table('brain_chats')->insert([
                'user_id' => 1,
                'message' => "[{$platform}:{$senderId}] " . $messageText,
                'response' => $replyText,
                'actions' => json_encode($response['actions'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to process Meta [{$platform}] message: " . $e->getMessage());
        }
    }

    /**
     * Send message back via Graph API
     */
    protected function sendReply(string $recipientId, string $text, string $platform): bool
    {
        $token = $this->getAccessToken();
        if (empty($token)) {
            Log::error("Cannot send Meta reply: No Page Access Token available.");
            return false;
        }

        // For Facebook Messenger: /me/messages or /v21.0/me/messages
        // For Instagram Direct: /v21.0/me/messages with Page Access Token linked to IG
        $endpoint = "https://graph.facebook.com/v21.0/me/messages?access_token=" . urlencode($token);

        $payload = [
            'recipient' => ['id' => $recipientId],
            'message' => ['text' => $text],
        ];

        try {
            $resp = Http::timeout(10)->post($endpoint, $payload);
            if ($resp->successful()) {
                Log::info("Meta [{$platform}] auto-reply delivered successfully to {$recipientId}");
                return true;
            } else {
                Log::warning("Meta [{$platform}] send reply failed: " . $resp->body());
                return false;
            }
        } catch (\Throwable $e) {
            Log::error("Meta [{$platform}] HTTP send error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get valid decrypted Page Token
     */
    protected function getAccessToken(): ?string
    {
        $acc = DB::table('social_accounts')->where('platform', 'facebook')->first();
        if ($acc && !empty($acc->access_token_enc)) {
            try {
                return \Illuminate\Support\Facades\Crypt::decryptString($acc->access_token_enc);
            } catch (\Throwable $e) {
                try {
                    return decrypt($acc->access_token_enc, false);
                } catch (\Throwable $e2) {
                    return $acc->access_token_enc;
                }
            }
        }

        return Vault::get('instagram_token') ?: Vault::get('fb_page_token');
    }
}
