<?php

namespace App\Services;

use App\Brain\Vault;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoEmailService
{
    protected string $apiKey;
    protected string $senderEmail;
    protected string $senderName;
    protected ?int $defaultListId;
    protected string $baseUrl = 'https://api.brevo.com/v3';

    public function __construct()
    {
        $this->apiKey = Vault::get('brevo_api_key', config('services.brevo.key', ''));
        $this->senderEmail = Vault::get('brevo_sender_email', config('mail.from.address', 'care@onlinepuja.live'));
        $this->senderName = Vault::get('brevo_sender_name', config('mail.from.name', 'Onlinepuja.live'));
        $listId = Vault::get('brevo_list_id', config('services.brevo.list_id', null));
        $this->defaultListId = $listId ? (int)$listId : 2;
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Send Transactional Email via Brevo API v3.
     */
    public function sendEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlContent,
        array $params = [],
        array $attachments = []
    ): array {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Brevo API key is not configured in Vault.'];
        }

        $payload = [
            'sender' => [
                'name'  => $this->senderName,
                'email' => $this->senderEmail,
            ],
            'to' => [
                [
                    'email' => $toEmail,
                    'name'  => $toName ?: 'Devotee',
                ]
            ],
            'subject'     => $subject,
            'htmlContent' => $htmlContent,
        ];

        if (!empty($params)) {
            $payload['params'] = $params;
        }

        if (!empty($attachments)) {
            $payload['attachment'] = $attachments; // [['url' => '...'], ['content' => 'base64', 'name' => 'receipt.pdf']]
        }

        try {
            $res = Http::withOptions([
                'curl' => [defined('CURLOPT_IPRESOLVE') ? CURLOPT_IPRESOLVE : 113 => defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1]
            ])->withHeaders([
                'api-key'      => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->timeout(12)->post("{$this->baseUrl}/smtp/email", $payload);

            if ($res->successful()) {
                $data = $res->json();
                return [
                    'ok'         => true,
                    'message_id' => $data['messageId'] ?? null,
                    'data'       => $data,
                ];
            }

            Log::error('Brevo API sendEmail error', ['status' => $res->status(), 'body' => $res->body()]);
            return [
                'ok'    => false,
                'code'  => $res->status(),
                'error' => $res->json('message') ?? 'Brevo error ' . $res->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('Brevo API exception: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send email using a predefined Brevo Template ID.
     */
    public function sendTemplate(string $toEmail, string $toName, int $templateId, array $params = []): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Brevo API key is not configured.'];
        }

        $payload = [
            'to' => [
                [
                    'email' => $toEmail,
                    'name'  => $toName ?: 'Devotee',
                ]
            ],
            'templateId' => $templateId,
            'params'     => $params,
        ];

        try {
            $res = Http::withOptions([
                'curl' => [defined('CURLOPT_IPRESOLVE') ? CURLOPT_IPRESOLVE : 113 => defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1]
            ])->withHeaders([
                'api-key'      => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->timeout(12)->post("{$this->baseUrl}/smtp/email", $payload);

            if ($res->successful()) {
                return ['ok' => true, 'message_id' => $res->json('messageId')];
            }

            return ['ok' => false, 'error' => $res->json('message') ?? 'Brevo template send failed'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add or update contact in Brevo list for marketing automation.
     */
    public function syncContact(string $email, array $attributes = [], ?int $listId = null): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Brevo API key not set.'];
        }

        $targetListId = $listId ?? $this->defaultListId;
        $payload = [
            'email'         => $email,
            'updateEnabled' => true,
        ];

        if (!empty($attributes)) {
            $payload['attributes'] = $attributes; // e.g. ['FIRSTNAME' => 'Ramesh', 'SMS' => '+919876543210']
        }

        if ($targetListId) {
            $payload['listIds'] = [$targetListId];
        }

        try {
            $res = Http::withHeaders([
                'api-key'      => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->timeout(12)->post("{$this->baseUrl}/contacts", $payload);

            if ($res->status() === 201 || $res->status() === 204 || $res->successful()) {
                return ['ok' => true, 'email' => $email];
            }

            return ['ok' => false, 'error' => $res->json('message') ?? 'Sync failed ' . $res->status()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Devotional HTML Email Template Generator.
     */
    public function renderDevotionalHtml(string $title, string $messageHtml, ?string $ctaText = null, ?string $ctaUrl = null): string
    {
        $ctaButton = '';
        if ($ctaText && $ctaUrl) {
            $ctaButton = "
                <table border='0' cellpadding='0' cellspacing='0' style='margin: 28px auto;'>
                    <tr>
                        <td align='center' style='border-radius: 9999px; background: linear-gradient(135deg, #d97706 0%, #b91c1c 100%);'>
                            <a href='{$ctaUrl}' target='_blank' style='display: inline-block; padding: 14px 32px; font-family: sans-serif; font-size: 15px; font-weight: bold; color: #ffffff; text-decoration: none; border-radius: 9999px;'>
                                {$ctaText}
                            </a>
                        </td>
                    </tr>
                </table>
            ";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$title}</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #fdfaf6; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
            <table border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #fdfaf6; padding: 30px 15px;'>
                <tr>
                    <td align='center'>
                        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 600px; background-color: #ffffff; border-radius: 16px; border: 1px solid #fee2e2; overflow: hidden; box-shadow: 0 4px 12px rgba(185, 28, 28, 0.05);'>
                            <!-- Vedic Header -->
                            <tr>
                                <td align='center' style='padding: 28px 24px 20px; background: linear-gradient(135deg, #fffbeb 0%, #fef2f2 100%); border-bottom: 2px solid #fef3c7;'>
                                    <div style='font-size: 28px; line-height: 1;'>🕉️</div>
                                    <div style='font-size: 18px; font-weight: 800; color: #991b1b; letter-spacing: 1px; text-transform: uppercase; margin-top: 8px;'>OnlinePuja.live</div>
                                    <div style='font-size: 12px; color: #b45309; margin-top: 2px;'>Sanctified Vedic Ceremonies & Astrology</div>
                                </td>
                            </tr>
                            <!-- Main Body -->
                            <tr>
                                <td style='padding: 32px 28px; color: #1e293b; font-size: 15px; line-height: 1.65;'>
                                    <h1 style='font-size: 20px; font-weight: 700; color: #0f172a; margin: 0 0 16px; text-align: center;'>{$title}</h1>
                                    {$messageHtml}
                                    {$ctaButton}
                                </td>
                            </tr>
                            <!-- Vedic Footer -->
                            <tr>
                                <td align='center' style='padding: 20px; background-color: #fffbeb; border-top: 1px solid #fef3c7; color: #92400e; font-size: 12px; line-height: 1.5;'>
                                    <p style='margin: 0 0 4px;'><strong>Har Har Mahadev · Jai Shri Ram</strong></p>
                                    <p style='margin: 0; color: #b45309;'>Direct Pandit Consultations & Sacred Ghat Pujas · Varanasi & Haridwar</p>
                                    <p style='margin: 8px 0 0; color: #94a3b8; font-size: 11px;'>© " . date('Y') . " OnlinePuja.live. All spiritual rights reserved.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
    }
}
