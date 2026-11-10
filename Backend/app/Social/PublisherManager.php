<?php

namespace App\Social;

use App\Social\Contracts\SocialPublisher;
use App\Social\Publishers\FacebookPagePublisher;
use App\Social\Publishers\InstagramPublisher;
use App\Social\Publishers\PinterestPublisher;
use App\Social\Publishers\XPublisher;
use App\Social\Publishers\YouTubeShortsPublisher;
use App\Social\Publishers\TelegramPublisher;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Registry of platform publishers plus the account store (encrypt/decrypt,
 * upsert-from-OAuth) and the publish dispatcher that resolves a social_posts
 * row into a publisher call, refreshing the token first if it's stale.
 */
class PublisherManager
{
    /** How many times a post may be attempted before it is failed for good. */
    public const MAX_ATTEMPTS = 3;

    /** @return array<string, SocialPublisher> */
    public function publishers(): array
    {
        return [
            'facebook' => new FacebookPagePublisher(),
            'instagram' => new InstagramPublisher(),
            'youtube' => new YouTubeShortsPublisher(),
            'x' => new XPublisher(),
            'pinterest' => new PinterestPublisher(),
            'telegram' => new TelegramPublisher(),
        ];
    }

    public function publisher(string $platform): ?SocialPublisher
    {
        return $this->publishers()[$platform] ?? null;
    }

    /**
     * Accounts with tokens decrypted, ready to hand to a publisher.
     *
     * @param  int|null  $astrologerId  only this astrologer's accounts
     * @param  bool  $businessOnly  only the business's own accounts (astrologer_id NULL)
     */
    public function connectedAccounts(?string $platform = null, ?int $astrologerId = null, bool $businessOnly = false): \Illuminate\Support\Collection
    {
        $q = DB::table('social_accounts')->orderBy('platform')->orderBy('label');

        if ($platform) {
            $q->where('platform', $platform);
        }

        if ($astrologerId !== null) {
            $q->where('astrologer_id', $astrologerId);
        } elseif ($businessOnly) {
            $q->whereNull('astrologer_id');
        }

        return $q->get()->map(function ($row) {
            $row->access_token = $this->decrypt($row->access_token_enc);
            $row->refresh_token = $row->refresh_token_enc ? $this->decrypt($row->refresh_token_enc) : null;
            $row->meta = $row->meta ? json_decode($row->meta, true) : [];
            $row->scopes = $row->scopes ? json_decode($row->scopes, true) : [];

            if (!empty($row->token_expires_at)) {
                try {
                    $row->token_expires_at = \Carbon\Carbon::parse($row->token_expires_at);
                } catch (\Throwable $e) {
                    $row->token_expires_at = null;
                }
            } else {
                $row->token_expires_at = null;
            }

            return $row;
        });
    }

    public function account(int $id): ?object
    {
        $row = DB::table('social_accounts')->where('id', $id)->first();

        if (! $row) {
            return null;
        }

        $row->access_token = $this->decrypt($row->access_token_enc);
        $row->refresh_token = $row->refresh_token_enc ? $this->decrypt($row->refresh_token_enc) : null;
        $row->meta = $row->meta ? json_decode($row->meta, true) : [];

        if (!empty($row->token_expires_at)) {
            try {
                $row->token_expires_at = \Carbon\Carbon::parse($row->token_expires_at);
            } catch (\Throwable $e) {
                $row->token_expires_at = null;
            }
        } else {
            $row->token_expires_at = null;
        }

        return $row;
    }

    /**
     * Persist one account returned by a publisher's handleCallback(). Upserts
     * on (platform, external_id) so reconnecting refreshes the token instead
     * of duplicating the row.
     *
     * $astrologerId identifies the owner (null = the business). A Page/channel
     * already connected under a different owner is rejected rather than stolen.
     *
     * @return array{ok:bool,id:?int,error:string}
     */
    public function saveAccount(string $platform, array $account, ?int $adminId, ?int $astrologerId = null): array
    {
        $existing = DB::table('social_accounts')
            ->where('platform', $platform)->where('external_id', $account['external_id'])
            ->first();

        if ($existing && (int) ($existing->astrologer_id ?? 0) !== (int) ($astrologerId ?? 0)) {
            return ['ok' => false, 'id' => null,
                'error' => 'That '.ucfirst($platform).' account is already connected under a different Online Puja profile.'];
        }

        // updateOrInsert()'s bool return reflects affected-row count, which
        // MySQL can report as 0 on a no-op update — not whether the row
        // exists. The row always exists afterward, so always look it up.
        DB::table('social_accounts')->updateOrInsert(
            ['platform' => $platform, 'external_id' => $account['external_id']],
            [
                'label' => $account['label'],
                'access_token_enc' => Crypt::encryptString($account['access_token']),
                'refresh_token_enc' => $account['refresh_token'] ? Crypt::encryptString($account['refresh_token']) : null,
                'token_expires_at' => $account['expires_at'] ?? null,
                'scopes' => json_encode($account['scopes'] ?? []),
                'status' => 'connected',
                'last_error' => null,
                'meta' => json_encode($account['meta'] ?? []),
                'connected_by' => $adminId,
                'astrologer_id' => $astrologerId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $id = (int) DB::table('social_accounts')
            ->where('platform', $platform)->where('external_id', $account['external_id'])
            ->value('id');

        return ['ok' => true, 'id' => $id, 'error' => ''];
    }

    /**
     * Remove an account. When $astrologerId is given the delete only lands if
     * the row belongs to that astrologer — a guard for the front-end panel.
     */
    public function disconnect(int $accountId, ?int $astrologerId = null): void
    {
        $q = DB::table('social_accounts')->where('id', $accountId);

        if ($astrologerId !== null) {
            $q->where('astrologer_id', $astrologerId);
        }

        $q->delete();
    }

    /**
     * Publish one social_posts row: resolves its account and media, refreshes
     * the token if it's within 10 minutes of expiry, calls the publisher, and
     * writes the result back onto the row. Never throws — every failure mode
     * lands in the row's status/error columns so the queue stays legible.
     */
    public function publish(object $post): array
    {
        $publisher = $this->publisher($post->platform);

        if (! $publisher) {
            return $this->markFailed($post->id, "No publisher registered for '{$post->platform}'.");
        }

        $account = $this->account($post->social_account_id);

        if (! $account) {
            return $this->markFailed($post->id, 'The connected account for this post no longer exists.');
        }

        if ($account->status !== 'connected') {
            return $this->markFailed($post->id, "Account is {$account->status} — reconnect it before this can publish.");
        }

        if ($account->token_expires_at && now()->addMinutes(10)->greaterThan($account->token_expires_at)) {
            $fresh = $publisher->refresh($account);

            if ($fresh) {
                $this->applyRefresh($account->id, $fresh);
                $account->access_token = $fresh['access_token'];
                $account->refresh_token = $fresh['refresh_token'] ?? $account->refresh_token;
            } elseif ($account->token_expires_at->isPast()) {
                DB::table('social_accounts')->where('id', $account->id)
                    ->update(['status' => 'expired', 'last_error' => 'Token expired and could not be refreshed.', 'updated_at' => now()]);

                return $this->markFailed($post->id, 'Account token expired — reconnect it.');
            }
        }

        $payload = $this->buildPayload($post);
        $result = $publisher->publish($account, $payload);

        if (! $result['ok']) {
            // A publish failure that smells like a dead token gets the account
            // flagged too, so the queue doesn't keep retrying against it blind.
            // Match narrowly: Graph types EVERY error "OAuthException", so
            // matching on the type alone once condemned a healthy Instagram
            // account over a container that just wasn't ingested yet.
            if ($this->looksLikeDeadToken($result['error'])) {
                DB::table('social_accounts')->where('id', $account->id)
                    ->update(['status' => 'error', 'last_error' => $result['error'], 'updated_at' => now()]);
            }

            return $this->markFailed($post->id, $result['error']);
        }

        DB::table('social_posts')->where('id', $post->id)->update([
            'status' => 'published',
            'remote_id' => $result['remote_id'],
            'permalink' => $result['permalink'],
            'error' => null,
            'published_at' => now(),
            'updated_at' => now(),
        ]);

        return ['ok' => true, 'remote_id' => $result['remote_id'], 'permalink' => $result['permalink'], 'error' => ''];
    }

    protected function buildPayload(object $post): SocialPostPayload
    {
        $media = $post->media_asset_id ? DB::table('media_assets')->where('id', $post->media_asset_id)->first() : null;
        $mediaUrl = null;
        $mediaPath = null;
        $mediaMime = null;
        $isVideo = false;

        if ($media) {
            $mediaMime = $media->mime;
            $isVideo = str_starts_with($media->mime, 'video/');
            $mediaUrl = (new \App\Media\ImageStudio(app(\App\AI\ProviderManager::class)))->publicUrl($media->path);

            // StorageHelper writes local files to public_path("storage/…") and
            // stores that as "public/storage/…", so the on-disk file lives
            // under public/, NOT storage/app/. Resolving it the other way
            // always yielded null — which meant YouTube, the one publisher
            // that needs real bytes, could never find its file.
            // An external disk (S3/Spaces) stores an absolute URL instead;
            // there is no local path in that case, which is correct.
            $local = str_starts_with($media->path, 'public/')
                ? base_path($media->path)
                : null;
            $mediaPath = ($local && is_file($local)) ? $local : null;
        }

        return new SocialPostPayload(
            caption: $post->caption,
            hashtags: $post->hashtags,
            mediaUrl: $mediaUrl,
            mediaPath: $mediaPath,
            mediaMime: $mediaMime,
            isVideo: $isVideo,
            linkUrl: $post->link_url,
        );
    }

    /**
     * Only the errors that really mean "this token/permission is gone".
     * Graph codes: 190 invalid/expired token, 102 session, 10 + 200-299
     * permission denied, 458-467 re-auth needed.
     */
    protected function looksLikeDeadToken(string $error): bool
    {
        if (preg_match('/\(code (\d+)/', $error, $m)) {
            $code = (int) $m[1];

            return $code === 190 || $code === 102 || $code === 10
                || ($code >= 200 && $code <= 299)
                || ($code >= 458 && $code <= 467);
        }

        // No code to go on (a non-Graph publisher, or a bare HTTP error) —
        // fall back to wording that only an auth failure produces.
        return (bool) preg_match(
            '/invalid.{0,3}(access.{0,3})?token|token (has )?expired|session (has )?expired|re-?authenticate|HTTP 401/i',
            $error
        );
    }

    protected function markFailed(int $postId, string $error): array
    {
        $attempts = (int) DB::table('social_posts')->where('id', $postId)->value('attempts') + 1;

        // Most publish failures are transient (container not ingested, Graph
        // timeout). Put the post back in the queue until it has burned through
        // its attempts, so one bad minute doesn't drop a post permanently.
        DB::table('social_posts')->where('id', $postId)->update([
            'status' => $attempts < self::MAX_ATTEMPTS ? 'queued' : 'failed',
            'error' => $error,
            'attempts' => $attempts,
            'updated_at' => now(),
        ]);

        return ['ok' => false, 'remote_id' => null, 'permalink' => null, 'error' => $error];
    }

    protected function applyRefresh(int $accountId, array $fresh): void
    {
        DB::table('social_accounts')->where('id', $accountId)->update([
            'access_token_enc' => Crypt::encryptString($fresh['access_token']),
            'refresh_token_enc' => $fresh['refresh_token'] ? Crypt::encryptString($fresh['refresh_token']) : null,
            'token_expires_at' => $fresh['expires_at'],
            'status' => 'connected',
            'last_error' => null,
            'updated_at' => now(),
        ]);
    }

    protected function decrypt(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return '';
        }
    }
}
