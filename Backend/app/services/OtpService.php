<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OTP delivery, channel-ordered, free channels first:
 *
 *   1. evolution  — Evolution API on VPS 2 (+91 70071 58014 business WhatsApp). Zero marginal cost.
 *   2. androidsms — a self-hosted capcom6/android-sms-gateway instance (your own
 *      Android phone's SIM as the sender). Zero per-message cost.
 *   3. whatsapp   — Meta's WhatsApp Cloud API directly.
 *   4. sms        — the existing MSG91 Flow send, kept as the paid safety net so
 *      a real user is never locked out if the free channels are mid-setup or
 *      the phone is offline.
 */
class OtpService
{
    protected const SMS_ENDPOINT = 'https://control.msg91.com/api/v5/flow';

    protected const META_GRAPH_VERSION = 'v21.0';

    /**
     * Send an OTP down the configured channel chain, first success wins.
     *
     * @return array{ok:bool,channel:?string,error:string,attempts:array<string,string>}
     */
    public function send(string $contactNo, string $otp, string $countryCode = '91'): array
    {
        $cleanCode = ltrim($countryCode, "+");
        $cleanContact = preg_replace("/[^0-9]/", "", $contactNo);
        if (str_starts_with($cleanContact, $cleanCode) && strlen($cleanContact) > 10) {
            $mobile = $cleanContact;
        } else {
            $mobile = $cleanCode . ltrim($cleanContact, "0");
        }
        $attempts = [];

        foreach ($this->channelChain() as $channel) {
            $result = match ($channel) {
                'evolution' => $this->sendEvolutionWhatsapp($mobile, $otp),
                'androidsms' => $this->sendAndroidSms($mobile, $otp),
                'whatsapp' => $this->sendWhatsapp($mobile, $otp),
                'sms' => $this->sendSms($mobile, $otp),
                default => ['ok' => false, 'error' => "unknown channel '{$channel}'"],
            };

            if ($result['ok']) {
                return ['ok' => true, 'channel' => $channel, 'error' => '', 'attempts' => $attempts];
            }

            $attempts[$channel] = $result['error'];
            Log::warning("OtpService: {$channel} send failed for {$mobile}: {$result['error']}");
        }

        return [
            'ok' => false,
            'channel' => null,
            'error' => $attempts ? implode(' | ', $attempts) : 'No OTP channel configured.',
            'attempts' => $attempts,
        ];
    }

    /**
     * Ordered channels to try, from systemflag `otpChannelChain` (comma list).
     * Defaults to 'evolution,sms' so Evolution WhatsApp is tried first at $0 cost,
     * falling back to paid SMS safety net.
     */
    protected function channelChain(): array
    {
        $raw = $this->flag('otpChannelChain') ?: 'evolution,sms';

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /* ===================================================== evolution === */

    public function evolutionConfigured(): bool
    {
        return true;
    }

    /**
     * Evolution API WhatsApp dispatch on VPS 2.
     * $0 per-message cost via connected business WhatsApp (+91 70071 58014).
     *
     * @return array{ok:bool,error:string}
     */
    protected function sendEvolutionWhatsapp(string $mobile, string $otp): array
    {
        $url = rtrim($this->flag("evolutionApiUrl") ?: "http://127.0.0.1:18080", "/");
        if (str_contains($url, "wa.vmstudio.digital") || empty($url)) {
            $url = "http://127.0.0.1:18080";
        }
        $key = $this->flag('evolutionApiKey') ?: 'bebf8554b69b4eff665465956069890dadeb84547a964ba0ac129e43e98576a2';
        $instance = $this->flag('evolutionInstance') ?: 'onlinepuja';

        $endpoint = "{$url}/message/sendText/{$instance}";
        $text = "🕉️ *OnlinePuja Verification Code*\n\nYour OTP is: *{$otp}*\n\nValid for 10 minutes. Please do not share this code with anyone.";

        $cleanMobile = preg_replace('/[^0-9]/', '', $mobile);

        try {
            $res = Http::withHeaders([
                'apikey' => $key,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($endpoint, [
                'number' => $cleanMobile,
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Evolution API exception: '.$e->getMessage()];
        }

        if ($res->failed()) {
            return ['ok' => false, 'error' => 'HTTP '.$res->status().' :: '.substr($res->body(), 0, 250)];
        }

        $data = $res->json();
        if (isset($data['status']) && is_numeric($data['status']) && $data['status'] >= 400) {
            return ['ok' => false, 'error' => 'Evolution API rejected: '.json_encode($data)];
        }

        return ['ok' => true, 'error' => ''];
    }

    /* ===================================================== androidsms === */

    public function androidSmsConfigured(): bool
    {
        return $this->flag('androidSmsGatewayUrl') !== '';
    }

    protected function sendAndroidSms(string $mobile, string $otp): array
    {
        $base = rtrim($this->flag('androidSmsGatewayUrl'), '/');

        if ($base === '') {
            return ['ok' => false, 'error' => 'androidSmsGatewayUrl not set'];
        }

        $user = $this->flag('androidSmsGatewayUsername');
        $pass = $this->flag('androidSmsGatewayPassword');
        $endpoint = $base.'/message';

        $text = "Your OnlinePuja OTP is {$otp}. Do not share it with anyone.";

        try {
            $req = Http::asJson()->timeout(15);

            if ($user !== '') {
                $req = $req->withBasicAuth($user, $pass);
            }

            $res = $req->post($endpoint, [
                'textMessage' => ['text' => $text],
                'phoneNumbers' => ['+'.$mobile],
            ]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        if ($res->failed()) {
            return ['ok' => false, 'error' => 'HTTP '.$res->status().' :: '.substr($res->body(), 0, 250)];
        }

        return ['ok' => true, 'error' => ''];
    }

    /* ======================================================= whatsapp === */

    public function whatsappConfigured(): bool
    {
        return $this->flag('metaWhatsappPhoneNumberId') !== ''
            && $this->flag('metaWhatsappAccessToken') !== ''
            && $this->flag('metaWhatsappTemplateName') !== '';
    }

    protected function sendWhatsapp(string $mobile, string $otp): array
    {
        if (! $this->whatsappConfigured()) {
            return ['ok' => false, 'error' => 'not configured (phone number id / access token / template missing)'];
        }

        $phoneNumberId = $this->flag('metaWhatsappPhoneNumberId');
        $token = $this->flag('metaWhatsappAccessToken');
        $template = $this->flag('metaWhatsappTemplateName');
        $lang = $this->flag('metaWhatsappTemplateLanguage') ?: 'en_US';

        $endpoint = 'https://graph.facebook.com/'.self::META_GRAPH_VERSION."/{$phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $mobile,
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => $lang],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => (string) $otp],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $res = Http::withToken($token)->asJson()->timeout(20)->post($endpoint, $payload);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        if ($res->failed()) {
            return ['ok' => false, 'error' => 'HTTP '.$res->status().' :: '.substr($res->body(), 0, 300)];
        }

        return ['ok' => true, 'error' => ''];
    }

    /* ============================================================ sms === */

    protected function sendSms(string $mobile, string $otp): array
    {
        $authKey = $this->flag('msg91AuthKey');
        $templateId = $this->flag('msg91SendOtpTemplateId');

        if ($authKey === '' || $templateId === '') {
            return ['ok' => false, 'error' => 'MSG91 configuration missing'];
        }

        $payload = [
            'template_id' => $templateId,
            'short_url' => '0',
            'realTimeResponse' => '1',
            'recipients' => [
                ['mobiles' => $mobile, 'otp' => (string) $otp],
            ],
        ];

        try {
            $res = Http::withHeaders([
                'accept' => 'application/json',
                'authkey' => $authKey,
                'content-type' => 'application/json',
            ])->timeout(30)->post(self::SMS_ENDPOINT, $payload);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'CURL-equivalent error: '.$e->getMessage()];
        }

        $data = $res->json();

        if (($data['type'] ?? null) !== 'success') {
            return ['ok' => false, 'error' => 'Failed to send OTP: '.json_encode($data)];
        }

        return ['ok' => true, 'error' => ''];
    }

    protected function flag(string $name): string
    {
        return (string) (DB::table('systemflag')->where('name', $name)->value('value') ?? '');
    }
}
