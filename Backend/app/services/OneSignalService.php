<?php
namespace App\Services;

use App\Models\AdminModel\SystemFlag;
use Illuminate\Support\Facades\Http;

class OneSignalService
{
    protected $appId;
    protected $apiKey;
    protected $androidId;

    public function __construct()
    {
        $systemflag = SystemFlag::whereIn('name', ['OneSignalAppId', 'OneSignalRestApiKey', 'OneSignalAndroidChannelId'])->get()->pluck('value', 'name');
        $this->appId = $systemflag['OneSignalAppId'] ?? '';
        $this->apiKey = $systemflag['OneSignalRestApiKey'] ?? '';
        $this->androidId = $systemflag['OneSignalAndroidChannelId'] ?? '';
    }

public function sendNotification(array $userIds, array $notification)
{
    $url = 'https://onesignal.com/api/v1/notifications';

    $headers = [
        'Authorization' => 'Basic ' . $this->apiKey,
        'Content-Type' => 'application/json',
    ];

    $isCustom = isset($notification['priority']) && $notification['priority'] === 'custom';
    $isSilent = $notification['content_available'] ?? false;

    // dd($notification['content_available']);

    // Base payload
    $payload = [
        'app_id' => $this->appId,
        'data' => [
            'title' => $notification['title'] ?? '',
            'description' => $notification['body']['description'] ?? '',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'body' => json_encode($notification['body'] ?? []),
        ],
        'android' => [
            'priority' => $isCustom ? 10 : 5, // 5 = Normal (silent), 10 = High (with sound)
            'sound' => $isCustom && !$isSilent ? 'app_sound' : null, // Sound only for non-silent notifications
        ],
        'ios_attachments' => $notification['attachments'] ?? null,
        'ios_badgeType' => 'Increase',
        'ios_badgeCount' => 1,
        'ios_sound' => $isCustom && !$isSilent ? 'app_sound.wav' : null, // Sound only for non-silent notifications
    ];

    if (!empty($userIds)) {
        $payload['include_player_ids'] = $userIds;
    } else {
        $payload['included_segments'] = ['Total Subscriptions'];
    }

    if (!empty($notification['url'])) {
        $payload['url'] = $notification['url'];
    } elseif (!empty($notification['body']['url'])) {
        $payload['url'] = $notification['body']['url'];
    }

    if ($isSilent) {

        // Remove parameters that conflict with silent notifications
        unset($payload['headings'], $payload['contents']);
        $payload['content_available'] = true; // Ensure silent notification behavior
    } else if(!$isSilent) {
        // Add headings and contents for regular notifications
        $payload['headings'] = [
            'en' => $notification['title'],
        ];
        $payload['contents'] = [
            'en' => $notification['body']['description'],
        ];
    }

    // Include `android_channel_id` only if non-empty and not silent
    if ($isCustom && !$isSilent && !empty($this->androidId)) {
        $payload['android_channel_id'] = $this->androidId;
    }

    // Send the request to OneSignal
    $response = Http::withHeaders($headers)->post($url, $payload);

    return $response->json();
}









}
