<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FCMService
{
    /**
     * Max parallel cURL requests to run simultaneously.
     * 50 is a safe value — Google allows ~500 req/sec for FCM v1.
     */
    const PARALLEL_BATCH_SIZE = 50;

    /**
     * Cache key for the FCM access token
     */
    const TOKEN_CACHE_KEY = 'fcm_access_token';

    /**
     * Get service account credentials from config (loaded from .env)
     */
    private function getServiceAccountKey(): array
    {
        return config('fcm.service_account');
    }

    /**
     * Get Firebase project ID from config
     */
    private function getProjectId(): string
    {
        return config('fcm.project_id', '');
    }

    /**
     * Send notification to multiple devices using FCM v1 API (PARALLEL using curl_multi)
     *
     * @param \Illuminate\Support\Collection $userDeviceDetail - Collection with 'fcmToken' field
     * @param array $notification - ['title' => '...', 'body' => [...]]
     * @return array - Array of individual FCM responses
     */
    public static function send($userDeviceDetail, $notification)
    {
        $fcmService = new self();
        $projectId = $fcmService->getProjectId();

        if (empty($projectId)) {
            Log::error('FCM send: FIREBASE_PROJECT_ID is not configured in .env');
            return [];
        }

        $accessToken = $fcmService->getAccessToken();

        if (empty($accessToken)) {
            Log::error('FCM send: Failed to get access token. Check Firebase credentials in .env');
            return [];
        }

        $endpoint = 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send';

        // Filter out empty tokens
        $tokens = $userDeviceDetail->pluck('fcmToken')->filter(function ($token) {
            return !empty($token);
        })->values()->all();

        if (empty($tokens)) {
            return [];
        }

        // Build all payloads
        $payloads = [];
        foreach ($tokens as $token) {
            $payloads[] = json_encode([
                'message' => [
                    'token' => $token,
                    'data' => [
                        'title' => $notification['title'] ?? '',
                        'description' => $notification['body']['description'] ?? '',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'body' => json_encode($notification['body']),
                    ],
                    'android' => [
                        'priority' => 'high',
                    ],
                ],
            ]);
        }

        // Send in parallel batches using curl_multi
        $allResponses = $fcmService->sendParallel($endpoint, $accessToken, $payloads);

        return $allResponses;
    }

    /**
     * Send multiple HTTP requests in parallel using curl_multi
     *
     * @param string $endpoint - FCM API endpoint
     * @param string $accessToken - OAuth2 access token
     * @param array $payloads - Array of JSON-encoded payload strings
     * @return array - Array of decoded response arrays
     */
    private function sendParallel(string $endpoint, string $accessToken, array $payloads): array
    {
        $allResponses = [];
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        // Process in parallel batches of PARALLEL_BATCH_SIZE
        $batches = array_chunk($payloads, self::PARALLEL_BATCH_SIZE);

        foreach ($batches as $batchPayloads) {
            $multiHandle = curl_multi_init();
            $curlHandles = [];

            // Create cURL handles for this batch
            foreach ($batchPayloads as $index => $payload) {
                $ch = curl_init($endpoint);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                curl_multi_add_handle($multiHandle, $ch);
                $curlHandles[$index] = $ch;
            }

            // Execute all requests simultaneously
            $running = null;
            do {
                $status = curl_multi_exec($multiHandle, $running);
                if ($running) {
                    // Wait for activity (prevents CPU spinning)
                    curl_multi_select($multiHandle, 1);
                }
            } while ($running > 0 && $status === CURLM_OK);

            // Collect responses
            foreach ($curlHandles as $index => $ch) {
                $response = curl_multi_getcontent($ch);
                $curlError = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($curlError) {
                    Log::error('FCM parallel send curl error: ' . $curlError);
                    $allResponses[] = ['error' => ['message' => 'cURL error: ' . $curlError]];
                } else {
                    $decoded = json_decode($response, true);

                    // if ($httpCode !== 200) {
                    //     Log::warning('FCM send failed', [
                    //         'http_code' => $httpCode,
                    //         'response' => $decoded,
                    //     ]);
                    // }

                    $allResponses[] = $decoded;
                }

                curl_multi_remove_handle($multiHandle, $ch);
                curl_close($ch);
            }

            curl_multi_close($multiHandle);
        }

        return $allResponses;
    }

    /**
     * Get OAuth2 access token — cached for 50 minutes (token valid for 60 min)
     */
    private function getAccessToken()
    {
        // Return cached token if available (avoids redundant Google API calls)
        return Cache::remember(self::TOKEN_CACHE_KEY, 50 * 60, function () {
            return $this->fetchNewAccessToken();
        });
    }

    /**
     * Fetch a fresh access token from Google OAuth2
     */
    private function fetchNewAccessToken()
    {
        $serviceAccount = $this->getServiceAccountKey();

        if (empty($serviceAccount['private_key']) || empty($serviceAccount['client_email'])) {
            Log::error('FCM getAccessToken: Firebase private_key or client_email is missing. Check .env file.');
            return null;
        }

        $url = 'https://oauth2.googleapis.com/token';
        $data = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $this->generateJwtAssertion(),
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Log::error('FCM getAccessToken curl error: ' . $curlError);
            return null;
        }

        $body = json_decode($response, true);

        if (!isset($body['access_token'])) {
            Log::error('FCM getAccessToken failed. Response: ' . $response);
            return null;
        }

        return $body['access_token'];
    }

    /**
     * Generate JWT assertion for OAuth2 token request
     */
    private function generateJwtAssertion()
    {
        $serviceAccount = $this->getServiceAccountKey();
        $now = time();
        $exp = $now + 3600;

        $jwtClaims = [
            'iss' => $serviceAccount['client_email'],
            'sub' => $serviceAccount['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'iat' => $now,
            'exp' => $exp,
        ];

        $jwtHeader = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $base64UrlEncodedHeader = $this->base64UrlEncode(json_encode($jwtHeader));
        $base64UrlEncodedClaims = $this->base64UrlEncode(json_encode($jwtClaims));

        $signatureInput = $base64UrlEncodedHeader . '.' . $base64UrlEncodedClaims;

        // Replace escaped newlines with real newlines (env files store as literal \n)
        $privateKeyStr = str_replace('\n', "\n", $serviceAccount['private_key']);
        $privateKey = openssl_pkey_get_private($privateKeyStr);

        if (!$privateKey) {
            Log::error('FCM generateJwtAssertion: Invalid private key. Check FIREBASE_PRIVATE_KEY in .env');
            return '';
        }

        openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $base64UrlEncodedSignature = $this->base64UrlEncode($signature);

        return $signatureInput . '.' . $base64UrlEncodedSignature;
    }

    private function base64UrlEncode($input)
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }
}
