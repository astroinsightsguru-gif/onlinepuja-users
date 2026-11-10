<?php

namespace App\Social\Concerns;

use App\Social\PublisherManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Shared OAuth-connect mechanics for the social layer. Both the admin screen
 * (business accounts) and the astrologer panel (their own accounts) run the
 * same handshake — mint a state, bounce to the platform, match the state on
 * callback, exchange the code, save (or offer a Page picker). The host
 * controller supplies the owner, the redirect URI and where to return.
 */
trait HandlesSocialOAuth
{
    abstract protected function oauthManager(): PublisherManager;

    /** Absolute callback URL registered in the platform's developer console. */
    abstract protected function oauthRedirectUri(string $platform): string;

    /** Route name to redirect back to after the flow (success or error). */
    abstract protected function oauthReturnRoute(): string;

    /** ['admin_id' => ?int, 'astrologer_id' => ?int] — the owner of what gets connected. */
    abstract protected function oauthOwner(): array;

    protected function startSocialConnect(string $platform)
    {
        $publisher = $this->oauthManager()->publisher($platform);

        if (! $publisher) {
            return back()->with('error', "Unknown platform '{$platform}'.");
        }

        if (! $publisher->isAppConfigured()) {
            return back()->with('error', ucfirst($platform).' is not available yet — the app credentials have not been set up.');
        }

        $state = Str::random(40);
        Cache::put("social_oauth_state_{$state}", array_merge(
            ['platform' => $platform],
            $this->oauthOwner(),
        ), now()->addMinutes(10));

        return redirect()->away($publisher->authorizeUrl($state, $this->oauthRedirectUri($platform)));
    }

    protected function finishSocialCallback(string $platform, Request $request)
    {
        $return = redirect()->route($this->oauthReturnRoute());
        $publisher = $this->oauthManager()->publisher($platform);

        if (! $publisher) {
            return $return->with('error', "Unknown platform '{$platform}'.");
        }

        if ($request->filled('error')) {
            return $return->with('error', ucfirst($platform).' connection was cancelled: '
                .$request->input('error_description', $request->input('error')));
        }

        $state = (string) $request->input('state');
        $stateData = $state ? Cache::pull("social_oauth_state_{$state}") : null;

        if (! $stateData || $stateData['platform'] !== $platform) {
            return $return->with('error', 'This connection link expired or was already used — try connecting again.');
        }

        // The callback must belong to the same owner that started the flow.
        if (($stateData['astrologer_id'] ?? null) !== ($this->oauthOwner()['astrologer_id'] ?? null)) {
            return $return->with('error', 'This connection link belongs to a different session — try again.');
        }

        $code = (string) $request->input('code');

        if ($code === '') {
            return $return->with('error', 'No authorization code was returned.');
        }

        $result = $publisher->handleCallback($code, $this->oauthRedirectUri($platform), $state);

        if (! $result['ok']) {
            return $return->with('error', ucfirst($platform).': '.$result['error']);
        }

        // Several connectable Pages — let the owner choose rather than saving all.
        if (count($result['accounts']) > 1) {
            $pickKey = 'social_oauth_pick_'.Str::random(24);
            Cache::put($pickKey, array_merge(
                ['platform' => $platform, 'accounts' => $result['accounts']],
                $this->oauthOwner(),
            ), now()->addMinutes(10));

            return $return->with('pick_accounts', [
                'key' => $pickKey, 'platform' => $platform, 'accounts' => $result['accounts'],
            ]);
        }

        $saved = $this->oauthManager()->saveAccount(
            $platform, $result['accounts'][0],
            $stateData['admin_id'] ?? null, $stateData['astrologer_id'] ?? null,
        );

        return $saved['ok']
            ? $return->with('success', ucfirst($platform).' connected: '.$result['accounts'][0]['label'])
            : $return->with('error', $saved['error']);
    }

    protected function finishAccountPick(Request $request)
    {
        $request->validate(['key' => 'required|string', 'selected' => 'array']);
        $return = redirect()->route($this->oauthReturnRoute());

        $cached = Cache::pull($request->input('key'));

        if (! $cached) {
            return $return->with('error', 'That selection expired — connect again.');
        }

        if (($cached['astrologer_id'] ?? null) !== ($this->oauthOwner()['astrologer_id'] ?? null)) {
            return $return->with('error', 'That selection belongs to a different session — connect again.');
        }

        $selected = array_map('intval', (array) $request->input('selected', []));
        $saved = 0;
        $errors = [];

        foreach ($cached['accounts'] as $i => $account) {
            if (! in_array($i, $selected, true)) {
                continue;
            }

            $res = $this->oauthManager()->saveAccount(
                $cached['platform'], $account,
                $cached['admin_id'] ?? null, $cached['astrologer_id'] ?? null,
            );

            $res['ok'] ? $saved++ : $errors[] = $res['error'];
        }

        if ($saved === 0 && $errors) {
            return $return->with('error', implode(' ', array_unique($errors)));
        }

        return $return->with('success', $saved.' '.ucfirst($cached['platform']).' account(s) connected.'
            .($errors ? ' Some were skipped: '.implode(' ', array_unique($errors)) : ''));
    }
}
