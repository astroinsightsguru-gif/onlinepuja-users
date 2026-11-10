<?php

namespace App\Social\Contracts;

use App\Social\SocialPostPayload;

/**
 * One implementation per platform. An implementation owns three concerns:
 * the OAuth handshake, publishing a post, and reading back light metrics.
 * Nothing outside app/Social should know Graph API vs YouTube Data API vs
 * Pinterest v5 shapes — that knowledge stays behind this contract.
 */
interface SocialPublisher
{
    /** Machine slug: facebook|instagram|youtube|x|pinterest */
    public function platform(): string;

    /** Human label for the connect screen. */
    public function label(): string;

    /** True once the app-level client id/secret are present in the vault. */
    public function isAppConfigured(): bool;

    /** Where to send the admin to start OAuth. */
    public function authorizeUrl(string $state, string $redirectUri): string;

    /**
     * Exchange the OAuth callback for one or more connectable accounts
     * (a Facebook user may administer several Pages, for instance) and
     * long-lived tokens. Nothing is persisted here — the caller decides
     * which returned accounts to store.
     *
     * $state is the same value handed to authorizeUrl(); a PKCE flow (X)
     * uses it to retrieve the code_verifier it cached alongside that state.
     *
     * @return array{ok:bool,accounts:array<int,array{
     *     external_id:string,label:string,access_token:string,
     *     refresh_token:?string,expires_at:?\Carbon\Carbon,scopes:array,meta:array
     * }>,error:string}
     */
    public function handleCallback(string $code, string $redirectUri, string $state): array;

    /**
     * Refresh a token before it expires. Returns null if this platform's
     * tokens don't expire / can't be refreshed (e.g. a Facebook Page token).
     *
     * @return array{access_token:string,refresh_token:?string,expires_at:?\Carbon\Carbon}|null
     */
    public function refresh(object $account): ?array;

    /**
     * @return array{ok:bool,remote_id:?string,permalink:?string,error:string}
     */
    public function publish(object $account, SocialPostPayload $payload): array;

    /**
     * @return array{ok:bool,reach:?int,likes:?int,comments:?int,shares:?int,error:string}
     */
    public function metrics(object $account, string $remoteId): array;
}
