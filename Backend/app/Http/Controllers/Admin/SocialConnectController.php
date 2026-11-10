<?php

namespace App\Http\Controllers\Admin;

use App\Social\Concerns\HandlesSocialOAuth;
use App\Social\PublisherManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * OAuth connect flow for the business's own social accounts. One redirect URI
 * per platform, registered once in each developer console:
 *   https://onlinepuja.live/admin/social/callback/{platform}
 *
 * The astrologer panel has its own controller for astrologer-owned accounts;
 * the shared handshake lives in HandlesSocialOAuth.
 */
class SocialConnectController
{
    use HandlesSocialOAuth;

    public function __construct(protected PublisherManager $manager)
    {
    }

    public function connect(string $platform)
    {
        return $this->startSocialConnect($platform);
    }

    public function callback(string $platform, Request $request)
    {
        return $this->finishSocialCallback($platform, $request);
    }

    public function confirmAccounts(Request $request)
    {
        return $this->finishAccountPick($request);
    }

    public function disconnect(int $accountId)
    {
        $this->manager->disconnect($accountId);

        return back()->with('success', 'Account disconnected.');
    }

    protected function oauthManager(): PublisherManager
    {
        return $this->manager;
    }

    protected function oauthRedirectUri(string $platform): string
    {
        return route('social.callback', $platform);
    }

    protected function oauthReturnRoute(): string
    {
        return 'growth-os.social';
    }

    protected function oauthOwner(): array
    {
        return ['admin_id' => Auth::id(), 'astrologer_id' => null];
    }
}
