<?php

namespace App\Social\Publishers;

use App\Brain\Vault;
use App\Social\Contracts\SocialPublisher;
use App\Social\SocialPostPayload;
use Illuminate\Support\Facades\Http;

class TelegramPublisher implements SocialPublisher
{
    public function platform(): string
    {
        return 'telegram';
    }

    public function label(): string
    {
        return 'Telegram Channel / Group';
    }

    public function isAppConfigured(): bool
    {
        $token = Vault::get('telegram_bot_token') ?: config('services.telegram.bot_token', '');
        return !empty($token);
    }

    public function authorizeUrl(string $state, string $redirectUri): string
    {
        return route('growth-os.social');
    }

    public function handleCallback(string $code, string $redirectUri, string $state): array
    {
        return ['ok' => true, 'accounts' => [], 'error' => ''];
    }

    public function refresh(object $account): ?array
    {
        return null;
    }

    public function publish(object $account, SocialPostPayload $payload): array
    {
        $token = $account->access_token ?: Vault::get('telegram_bot_token');
        $chatId = $account->external_id;

        if (!$token || !$chatId) {
            return [
                'ok' => false,
                'remote_id' => null,
                'permalink' => null,
                'error' => 'Telegram bot token or Channel Chat ID is missing.'
            ];
        }

        $text = $payload->fullText(4000);
        if ($payload->linkUrl && !str_contains($text, $payload->linkUrl)) {
            $text .= "\n\n🔗 " . $payload->linkUrl;
        }

        try {
            if ($payload->mediaUrl && !$payload->isVideo) {
                $caption = mb_substr($text, 0, 1024);
                $res = Http::timeout(30)->post("https://api.telegram.org/bot{$token}/sendPhoto", [
                    'chat_id' => $chatId,
                    'photo' => $payload->mediaUrl,
                    'caption' => $caption,
                ]);
            } elseif ($payload->mediaUrl && $payload->isVideo) {
                $caption = mb_substr($text, 0, 1024);
                $res = Http::timeout(60)->post("https://api.telegram.org/bot{$token}/sendVideo", [
                    'chat_id' => $chatId,
                    'video' => $payload->mediaUrl,
                    'caption' => $caption,
                ]);
            } else {
                $res = Http::timeout(20)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                ]);
            }

            if ($res->successful() && $res->json('ok')) {
                $msgId = (string) $res->json('result.message_id');
                $channelName = ltrim($chatId, '@');
                $link = str_starts_with($chatId, '@') ? "https://t.me/{$channelName}/{$msgId}" : null;

                return [
                    'ok' => true,
                    'remote_id' => $msgId,
                    'permalink' => $link,
                    'error' => null,
                ];
            }

            return [
                'ok' => false,
                'remote_id' => null,
                'permalink' => null,
                'error' => $res->json('description') ?: 'Telegram API error: ' . $res->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'remote_id' => null,
                'permalink' => null,
                'error' => 'Telegram request failed: ' . $e->getMessage(),
            ];
        }
    }

    public function metrics(object $account, string $remoteId): array
    {
        return [
            'ok' => true,
            'reach' => null,
            'likes' => null,
            'comments' => null,
            'shares' => null,
            'error' => null,
        ];
    }
}
