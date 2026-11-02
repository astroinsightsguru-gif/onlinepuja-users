<?php

namespace App\Social\Publishers\Concerns;

use App\Brain\Vault;
use Illuminate\Support\Facades\Http;

/**
 * Shared Graph API mechanics for Facebook and Instagram — both run through
 * the same Meta app and the same OAuth dialog; Instagram publishing is only
 * reachable via the access token of the Facebook Page it's linked to.
 */
trait UsesFacebookGraph
{
    protected function fbApiVersion(): string
    {
        return (Vault::socialCredentials()['fb_api_version'] ?? null) ?: 'v21.0';
    }

    protected function fbBase(): string
    {
        return 'https://graph.facebook.com/'.$this->fbApiVersion();
    }

    protected function fbAppId(): string
    {
        return Vault::socialCredentials()['fb_app_id'] ?? '';
    }

    protected function fbAppSecret(): string
    {
        return Vault::socialCredentials()['fb_app_secret'] ?? '';
    }

    /**
     * Publishing alone is not enough: the read scopes below are what let
     * `social:collect` fill `outcomes`, which is the only thing the Learner
     * learns from. Meta silently drops any scope the app has not been granted
     * — verified 2026-09-04, when a reconnect returned six of the eight asked
     * for — so `metrics()` degrades per-field instead of failing, and this list
     * is safe to keep ahead of App Review. Adding a scope only takes effect on
     * the next OAuth grant, so both accounts must be reconnected once after the
     * app is approved for the missing ones.
     *
     * Still ungranted as of 2026-09-04 (each needs Advanced Access):
     *   read_insights, instagram_manage_insights -> post/media reach
     *   pages_read_user_content                  -> FB reactions + comments
     */
    protected function fbScopes(): string
    {
        return implode(',', [
            'pages_show_list',
            'pages_read_engagement',
            'pages_read_user_content',      // Page post reactions + comments
            'pages_manage_posts',
            'business_management',
            'read_insights',                // Page post reach/impressions
            'instagram_basic',
            'instagram_content_publish',
            'instagram_manage_insights',    // IG media reach/likes/comments
        ]);
    }

    protected function fbAuthorizeUrl(string $state, string $redirectUri): string
    {
        $q = http_build_query([
            'client_id' => $this->fbAppId(),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => $this->fbScopes(),
            'response_type' => 'code',
        ]);

        return 'https://www.facebook.com/'.$this->fbApiVersion()."/dialog/oauth?{$q}";
    }

    /**
     * code -> short-lived user token -> long-lived (~60 day) user token
     * -> the Pages that user manages, each carrying its own long-lived
     * Page token (Page tokens don't expire while the app has the permission
     * and the user token backing them is still valid).
     *
     * @return array{ok:bool,pages:array,error:string}
     */
    protected function fbResolvePages(string $code, string $redirectUri): array
    {
        $fail = fn (string $e) => ['ok' => false, 'pages' => [], 'error' => $e];

        try {
            $short = Http::get("{$this->fbBase()}/oauth/access_token", [
                'client_id' => $this->fbAppId(),
                'client_secret' => $this->fbAppSecret(),
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ]);
        } catch (\Throwable $e) {
            return $fail('Code exchange failed: '.$e->getMessage());
        }

        if ($short->failed() || ! $short->json('access_token')) {
            return $fail('Code exchange rejected: '.substr($short->body(), 0, 300));
        }

        try {
            $long = Http::get("{$this->fbBase()}/oauth/access_token", [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->fbAppId(),
                'client_secret' => $this->fbAppSecret(),
                'fb_exchange_token' => $short->json('access_token'),
            ]);
        } catch (\Throwable $e) {
            return $fail('Long-lived token exchange failed: '.$e->getMessage());
        }

        $userToken = $long->json('access_token') ?: $short->json('access_token');

        try {
            $pages = Http::get("{$this->fbBase()}/me/accounts", [
                'fields' => 'id,name,access_token,instagram_business_account{id,username,profile_picture_url}',
                'access_token' => $userToken,
            ]);
        } catch (\Throwable $e) {
            return $fail('Listing Pages failed: '.$e->getMessage());
        }

        if ($pages->failed()) {
            return $fail('Listing Pages rejected: '.substr($pages->body(), 0, 300));
        }

        return ['ok' => true, 'pages' => (array) $pages->json('data', []), 'error' => ''];
    }

    /**
     * Graph errors all arrive typed "OAuthException" regardless of cause, so
     * the numeric code is the only thing that separates a dead token from a
     * container that simply isn't ready yet. Keep it in the message — the
     * queue reads these strings to decide whether an account is broken.
     */
    /**
     * A GET that never throws: every Graph read in this package wants the same
     * "did it work, what came back, why not" shape rather than an exception.
     *
     * @return array{ok:bool,json:array,error:string,code:int|null}
     */
    protected function fbGet(string $url, array $query): array
    {
        try {
            $res = \Illuminate\Support\Facades\Http::get($url, $query);
        } catch (\Throwable $e) {
            return ['ok' => false, 'json' => [], 'error' => $e->getMessage(), 'code' => null];
        }

        if ($res->failed()) {
            return ['ok' => false, 'json' => [], 'error' => $this->fbGraphError($res), 'code' => $this->fbErrorCode($res)];
        }

        return ['ok' => true, 'json' => $res->json() ?? [], 'error' => '', 'code' => null];
    }

    /**
     * The numeric Graph error code, which is the only reliable way to tell a
     * dead token (190) from a missing permission (10) from a bad argument
     * (100) — the `type` is "OAuthException" for all three.
     */
    protected function fbErrorCode(\Illuminate\Http\Client\Response $res): ?int
    {
        $code = $res->json('error.code');

        return is_numeric($code) ? (int) $code : null;
    }

    protected function fbGraphError(\Illuminate\Http\Client\Response $res): string
    {
        $err = $res->json('error');

        if (is_array($err)) {
            $code = $err['code'] ?? null;
            $sub = $err['error_subcode'] ?? null;
            $tag = $code === null ? '' : ' (code '.$code.($sub ? '/'.$sub : '').')';

            return ($err['type'] ?? 'Error').$tag.': '.($err['message'] ?? $res->body());
        }

        return 'HTTP '.$res->status().' :: '.substr($res->body(), 0, 250);
    }
}
