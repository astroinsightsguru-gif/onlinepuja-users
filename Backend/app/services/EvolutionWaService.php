<?php

namespace App\Services;

use App\Brain\Vault;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionWaService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $instance;

    public function __construct()
    {
        $defaultUrl = 'http://127.0.0.1:18080';
        $this->apiUrl = rtrim(Vault::get('evolution_api_url', config('services.evolution.url', $defaultUrl)), '/');
        $this->apiKey = Vault::get('evolution_api_key', config('services.evolution.key', 'bebf8554b69b4eff665465956069890dadeb84547a964ba0ac129e43e98576a2'));
        $this->instance = Vault::get('evolution_instance', config('services.evolution.instance', 'onlinepuja'));
    }

    /**
     * Create an HTTP client configured with IPv4 resolve and Evolution API headers.
     */
    protected function client(int $timeout = 10)
    {
        return Http::withOptions([
            'curl' => [
                (defined('CURLOPT_IPRESOLVE') ? CURLOPT_IPRESOLVE : 113) => (defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1),
            ],
        ])->withHeaders([
            'apikey'       => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout($timeout);
    }

    /**
     * Normalize Indian or international phone numbers for WhatsApp.
     */
    public function cleanNumber(string $rawNumber): string
    {
        $clean = preg_replace('/[^0-9]/', '', $rawNumber);
        if (strlen($clean) === 10) {
            $clean = '91' . $clean; // Default to India (+91)
        }
        return $clean;
    }

    /**
     * Send Plain or Formatted Text Message.
     */
    public function sendText(string $mobile, string $text): array
    {
        // Pre-flight connection check to prevent 12-second cURL timeout on unlinked instances
        $conn = $this->getConnectionState();
        if (!empty($conn['ok']) && ($conn['state'] ?? '') !== 'open') {
            $st = $conn['state'] ?? 'disconnected';
            return [
                'ok'    => false,
                'state' => $st,
                'error' => "WhatsApp instance is '{$st}'. Please click 'Scan WhatsApp QR' to pair your device first.",
            ];
        }

        $number = $this->cleanNumber($mobile);
        $endpoint = "{$this->apiUrl}/message/sendText/{$this->instance}";

        try {
            $res = $this->client(12)->post($endpoint, [
                'number' => $number,
                'text'   => $text,
            ]);

            if ($res->successful()) {
                return ['ok' => true, 'data' => $res->json()];
            }

            Log::error('Evolution API sendText failed', ['status' => $res->status(), 'body' => $res->body()]);
            return ['ok' => false, 'error' => 'HTTP ' . $res->status() . ' :: ' . substr($res->body(), 0, 200)];
        } catch (\Throwable $e) {
            Log::error('Evolution API sendText exception: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send Media Message (Video Reels MP4, Images, Audio, PDF).
     */
    public function sendMedia(
        string $mobile,
        string $mediaUrl,
        string $caption = '',
        string $mediaType = 'video',
        ?string $fileName = null
    ): array {
        if (empty($mediaUrl)) {
            return $this->sendText($mobile, $caption);
        }

        $number = $this->cleanNumber($mobile);
        $endpoint = "{$this->apiUrl}/message/sendMedia/{$this->instance}";

        $mediaPayload = $mediaUrl;

        // If local file exists, send as base64 for 100% reliable delivery
        if (str_starts_with($mediaUrl, 'https://onlinepuja.live/')) {
            $localRel = str_replace('https://onlinepuja.live/', '', $mediaUrl);
            $localPath = public_path($localRel);
            if (!file_exists($localPath) && str_starts_with($localRel, 'storage/')) {
                $localPath = storage_path('app/public/' . substr($localRel, 8));
            }
            if (file_exists($localPath) && filesize($localPath) < 15 * 1024 * 1024) {
                // Evolution API v2 expects raw base64 string WITHOUT data: URI scheme
                $mediaPayload = base64_encode(file_get_contents($localPath));
            }
        }

        $payload = [
            'number'    => $number,
            'mediatype' => $mediaType, // 'image', 'video', 'document', 'audio'
            'media'     => $mediaPayload,
            'caption'   => $caption,
        ];

        if ($fileName) {
            $payload['fileName'] = $fileName;
        }

        try {
            $res = $this->client(30)->post($endpoint, $payload);

            if ($res->successful()) {
                return ['ok' => true, 'data' => $res->json()];
            }

            Log::error('Evolution API sendMedia failed, falling back to text', ['status' => $res->status(), 'body' => $res->body()]);
            // Fallback to high-contrast text with media link
            $textFallback = $caption . (!empty($mediaUrl) ? "\n\n🎬 Watch Sacred Video Reel:\n{$mediaUrl}" : '');
            return $this->sendText($mobile, $textFallback);
        } catch (\Throwable $e) {
            Log::error('Evolution API sendMedia exception: ' . $e->getMessage() . ', falling back to text');
            $textFallback = $caption . (!empty($mediaUrl) ? "\n\n🎬 Watch Sacred Video Reel:\n{$mediaUrl}" : '');
            return $this->sendText($mobile, $textFallback);
        }
    }

    /**
     * Send Interactive CTA Button Menu.
     * $buttons format: [['id' => 'book_puja', 'text' => '🙏 Book Puja'], ['id' => 'astrologer', 'text' => '🔮 Talk to Pandit']]
     */
    public function sendButtons(
        string $mobile,
        string $title,
        string $description,
        array $buttons,
        ?string $footer = 'OnlinePuja.live · Sanctified Vedic Services'
    ): array {
        $number = $this->cleanNumber($mobile);
        $endpoint = "{$this->apiUrl}/message/sendButtons/{$this->instance}";

        $formattedButtons = [];
        foreach ($buttons as $i => $btn) {
            $formattedButtons[] = [
                'buttonId'   => $btn['id'] ?? ('btn_' . $i),
                'buttonText' => ['displayText' => $btn['text'] ?? 'Click'],
                'type'       => 1,
            ];
        }

        $payload = [
            'number'      => $number,
            'title'       => $title,
            'description' => $description,
            'footer'      => $footer,
            'buttons'     => $formattedButtons,
        ];

        try {
            $res = $this->client(12)->post($endpoint, $payload);

            if ($res->successful()) {
                return ['ok' => true, 'data' => $res->json()];
            }

            // Fallback to text with numbering if buttons fail on recipient client
            $textFallback = "{$title}\n\n{$description}\n\n";
            foreach ($buttons as $idx => $b) {
                $textFallback .= ($idx + 1) . ". " . ($b['text'] ?? '') . "\n";
            }
            if ($footer) {
                $textFallback .= "\n_{$footer}_";
            }
            return $this->sendText($mobile, $textFallback);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Current Connection State (open, close, connecting).
     */
    public function getConnectionState(): array
    {
        $endpoint = "{$this->apiUrl}/instance/connectionState/{$this->instance}";
        try {
            $res = $this->client(6)->get($endpoint);
            if ($res->successful()) {
                $data = $res->json();
                $state = $data['instance']['state'] ?? 'unknown';
                return ['ok' => true, 'state' => $state, 'raw' => $data];
            }
            return ['ok' => false, 'state' => 'error', 'status' => $res->status()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'state' => 'offline', 'error' => $e->getMessage()];
        }
    }

    /**
     * Request a fresh QR Code or Pairing Code for WhatsApp login.
     */
    public function requestQrCode(): array
    {
        $endpoint = "{$this->apiUrl}/instance/connect/{$this->instance}";
        try {
            $res = $this->client(10)->get($endpoint);
            if ($res->successful()) {
                $data = $res->json();
                return [
                    'ok'          => true,
                    'base64'      => $data['base64'] ?? null,
                    'pairingCode' => $data['pairingCode'] ?? null,
                    'code'        => $data['code'] ?? null,
                ];
            }
            return ['ok' => false, 'error' => 'HTTP ' . $res->status()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Request an 8-character Pairing Code for Linking WhatsApp via Phone Number.
     */
    public function requestPairingCode(string $phone = '917007158014'): array
    {
        $clean = $this->cleanNumber($phone);
        $endpoint = "{$this->apiUrl}/instance/connect/{$this->instance}?number={$clean}";
        try {
            $res = $this->client(15)->get($endpoint);
            if ($res->successful()) {
                $data = $res->json();
                $pairing = $data['pairingCode'] ?? null;
                if (!empty($pairing) && strlen($pairing) === 8) {
                    $pairing = substr($pairing, 0, 4) . '-' . substr($pairing, 4);
                }
                return [
                    'ok'          => true,
                    'pairingCode' => $pairing,
                    'base64'      => $data['base64'] ?? null,
                    'code'        => $data['code'] ?? null,
                ];
            }
            return ['ok' => false, 'error' => 'HTTP ' . $res->status() . ' : ' . $res->body()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Restart instance socket.
     */
    public function restartInstance(): array
    {
        $endpoint = "{$this->apiUrl}/instance/restart/{$this->instance}";
        try {
            $res = $this->client(15)->post($endpoint);
            return ['ok' => $res->successful(), 'data' => $res->json()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Logout disconnected session to generate a clean fresh QR.
     */
    public function logoutInstance(): array
    {
        $endpoint = "{$this->apiUrl}/instance/logout/{$this->instance}";
        try {
            $res = $this->client(15)->delete($endpoint);
            return ['ok' => $res->successful(), 'data' => $res->json()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
