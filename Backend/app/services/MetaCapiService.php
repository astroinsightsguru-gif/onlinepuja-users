<?php

namespace App\Services;

use App\Brain\Vault;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCapiService
{
    /**
     * Dispatch server-to-server event to Meta Graph Conversions API.
     *
     * @param string $eventName e.g. 'Purchase', 'InitiateCheckout', 'Lead'
     * @param array $userData ['email' => '...', 'phone' => '...', 'ip' => '...', 'user_agent' => '...']
     * @param array $customData ['value' => 1500, 'currency' => 'INR', 'content_name' => '...']
     * @param string|null $eventId Deduplication event_id matching client-side fbq eventID
     */
    public function sendEvent(string $eventName, array $userData = [], array $customData = [], ?string $eventId = null): array
    {
        $pixelId = Vault::get('meta_pixel_id') ?: (Vault::get('fb_app_id') ?: '1738490804042986');
        $token = Vault::get('meta_capi_token') ?: env('META_CAPI_TOKEN');

        if (empty($pixelId) || empty($token)) {
            return [
                'ok' => false,
                'skipped' => true,
                'message' => 'Meta CAPI skipped: pixel_id or meta_capi_token not set.'
            ];
        }

        $formattedUser = [
            'client_ip_address' => $userData['ip'] ?? request()->ip(),
            'client_user_agent' => $userData['user_agent'] ?? request()->userAgent(),
        ];

        if (!empty($userData['email'])) {
            $formattedUser['em'] = [hash('sha256', strtolower(trim($userData['email'])))];
        }

        if (!empty($userData['phone'])) {
            $cleanPhone = preg_replace('/[^0-9]/', '', (string)$userData['phone']);
            if (strlen($cleanPhone) === 10) {
                $cleanPhone = '91' . $cleanPhone;
            }
            $formattedUser['ph'] = [hash('sha256', $cleanPhone)];
        }

        $payload = [
            'data' => [
                [
                    'event_name' => $eventName,
                    'event_time' => time(),
                    'event_id' => $eventId ?: ('op_' . time() . '_' . rand(1000, 9999)),
                    'event_source_url' => request()->fullUrl(),
                    'action_source' => 'website',
                    'user_data' => $formattedUser,
                    'custom_data' => array_merge([
                        'currency' => 'INR',
                    ], $customData),
                ]
            ]
        ];

        try {
            $endpoint = "https://graph.facebook.com/v19.0/{$pixelId}/events?access_token={$token}";
            $response = Http::timeout(5)->post($endpoint, $payload);

            if ($response->successful()) {
                Log::info("Meta CAPI [{$eventName}] dispatched successfully", ['resp' => $response->json()]);
                return ['ok' => true, 'data' => $response->json()];
            }

            Log::warning("Meta CAPI [{$eventName}] error", ['status' => $response->status(), 'body' => $response->body()]);
            return ['ok' => false, 'error' => $response->body()];
        } catch (\Throwable $e) {
            Log::error("Meta CAPI [{$eventName}] exception: " . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
