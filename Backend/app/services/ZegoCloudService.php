<?php

namespace App\Services;

use App\Services\ZegoTokenGenerator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ZegoCloudService
{
    protected $appId;
    protected $serverSecret;
    protected $tokenExpiry;
    protected $baseUrl;

    public function __construct()
    {
        $this->appId = (int) systemflag('zegoAppId');
        $this->serverSecret = systemflag('zegoServerSecret');
        $this->baseUrl = systemflag('zegoApiUrl') ?? 'https://rtc-api.zego.im';
    }

    protected function generateSignature($timestamp, $nonce)
    {
        $signatureParams = [
            'AppId' => $this->appId,
            'SignatureNonce' => $nonce,
            'ServerSecret' => $this->serverSecret,
            'Timestamp' => $timestamp
        ];

        ksort($signatureParams);
        $signString = http_build_query($signatureParams);

        return md5($signString);
    }

    /**
     * Make API request to ZegoCloud
     */
    protected function makeRequest($method, $endpoint, $data = [])
    {
        $timestamp = time();
        $nonce = rand(100000, 999999);
        $signature = $this->generateSignature($timestamp, $nonce);

        $headers = [
            'Content-Type' => 'application/json',
            'AppId' => $this->appId,
            'Timestamp' => $timestamp,
            'SignatureNonce' => $nonce,
            'Signature' => $signature
        ];

        $url = $this->baseUrl . $endpoint;

        try {
            $response = Http::withHeaders($headers)
                ->$method($url, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error('ZegoCloud API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('ZegoCloud API Exception', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate token for user authentication
     */
    public function generateToken($userId, $roomId = '', $expireTime = 86400)
    {
        if ($roomId) {
            return ZegoTokenGenerator::generateTokenWithRoom(
                $this->appId,
                $userId,
                $this->serverSecret,
                $expireTime,
                $roomId
            );
        }

        return ZegoTokenGenerator::generateToken04(
            $this->appId,
            $userId,
            $this->serverSecret,
            $expireTime,
        );
    }

    /**
     * Get App ID
     */
    public function getAppId()
    {
        return $this->appId;
    }

    public function startCall($callId, $astrologerId, $userId)
    {
        // Create a unique room name
        $roomId = 'call_' . $callId . '_' . time();

        // Generate tokens for both user and astrologer
        $userToken = $this->generateToken($userId, $roomId);
        $astroToken = $this->generateToken($astrologerId, $roomId);

        // Return room info and tokens
        return [
            'roomId' => $roomId,
            'userToken' => $userToken,
            'astroToken' => $astroToken,
            'appId' => $this->getAppId()
        ];
    }

    // ==================== ROOM MANAGEMENT APIs ====================

    /**
     * Query room list
     */
    public function queryRoomList($pageIndex = 1, $pageSize = 100)
    {
        return $this->makeRequest('get', '/room/query', [
            'page_index' => $pageIndex,
            'page_size' => $pageSize
        ]);
    }

    /**
     * Query room details by room ID
     */
    public function getRoomInfo($roomId)
    {
        return $this->makeRequest('get', '/room/info', [
            'room_id' => $roomId
        ]);
    }

    /**
     * Get users in room
     */
    public function getRoomUsers($roomId)
    {
        return $this->makeRequest('get', '/room/user/list', [
            'room_id' => $roomId
        ]);
    }

    /**
     * Remove user from room
     */
    public function kickoutUser($roomId, $userId)
    {
        return $this->makeRequest('post', '/room/user/kickout', [
            'room_id' => $roomId,
            'user_id' => $userId
        ]);
    }

    /**
     * Disconnect all users from room
     */
    public function disconnectRoom($roomId)
    {
        return $this->makeRequest('post', '/room/disconnect', [
            'room_id' => $roomId
        ]);
    }

    // ==================== STREAM MANAGEMENT APIs ====================

    /**
     * Query stream list in room
     */
    public function queryStreamList($roomId)
    {
        return $this->makeRequest('get', '/stream/query', [
            'room_id' => $roomId
        ]);
    }

    /**
     * Stop stream publishing
     */
    public function stopPublishing($roomId, $streamId)
    {
        return $this->makeRequest('post', '/stream/stop', [
            'room_id' => $roomId,
            'stream_id' => $streamId
        ]);
    }

    /**
     * Mute/Unmute user's audio
     */
    public function muteUserAudio($roomId, $userId, $mute = true)
    {
        return $this->makeRequest('post', '/room/user/mute', [
            'room_id' => $roomId,
            'user_id' => $userId,
            'mute_audio' => $mute
        ]);
    }

    /**
     * Mute/Unmute user's video
     */
    public function muteUserVideo($roomId, $userId, $mute = true)
    {
        return $this->makeRequest('post', '/room/user/mute', [
            'room_id' => $roomId,
            'user_id' => $userId,
            'mute_video' => $mute
        ]);
    }

    // ==================== RECORDING APIs ====================

    /**
     * Start recording
     */
    public function startRecording($roomId, $streamId, $config = [])
    {
        $defaultConfig = [
            'room_id' => $roomId,
            'stream_id' => $streamId,
            'record_mode' => 1,  // 1: Audio+Video, 2: Audio only, 3: Video only
            'storage_type' => 0,  // 0: ZEGO storage, 1: Custom storage
        ];

        $recordConfig = array_merge($defaultConfig, $config);

        return $this->makeRequest('post', '/record/start', $recordConfig);
    }

    /**
     * Stop recording
     */
    public function stopRecording($taskId)
    {
        return $this->makeRequest('post', '/record/stop', [
            'task_id' => $taskId
        ]);
    }

    /**
     * Query recording status
     */
    public function queryRecording($taskId)
    {
        return $this->makeRequest('get', '/record/query', [
            'task_id' => $taskId
        ]);
    }

    // ==================== MIXING STREAM APIs ====================

    /**
     * Start mixing streams
     */
    public function startMixing($roomId, $outputStreamId, $inputStreams, $config = [])
    {
        $mixConfig = [
            'room_id' => $roomId,
            'output_stream_id' => $outputStreamId,
            'input_stream_list' => $inputStreams,
            'output_config' => $config['output_config'] ?? [
                'video_bitrate' => 1500,
                'video_fps' => 15,
                'video_width' => 640,
                'video_height' => 480
            ]
        ];

        return $this->makeRequest('post', '/mix/start', $mixConfig);
    }

    /**
     * Stop mixing streams
     */
    public function stopMixing($taskId)
    {
        return $this->makeRequest('post', '/mix/stop', [
            'task_id' => $taskId
        ]);
    }

    // ==================== USER MANAGEMENT APIs ====================

    /**
     * Get user online status
     */
    public function getUserStatus($userIds)
    {
        return $this->makeRequest('post', '/user/status', [
            'user_id_list' => is_array($userIds) ? $userIds : [$userIds]
        ]);
    }

    /**
     * Set user attributes
     */
    public function setUserAttributes($userId, $attributes)
    {
        return $this->makeRequest('post', '/user/attribute/set', [
            'user_id' => $userId,
            'attributes' => $attributes
        ]);
    }

    /**
     * Get user attributes
     */
    public function getUserAttributes($userId)
    {
        return $this->makeRequest('get', '/user/attribute/get', [
            'user_id' => $userId
        ]);
    }

    // ==================== CALLBACK APIs ====================

    /**
     * Set callback configuration
     */
    public function setCallbackConfig($callbackUrl, $events = [])
    {
        $defaultEvents = [
            'room_created',
            'room_destroyed',
            'user_joined',
            'user_left',
            'stream_created',
            'stream_destroyed'
        ];

        return $this->makeRequest('post', '/callback/config', [
            'callback_url' => $callbackUrl,
            'events' => empty($events) ? $defaultEvents : $events
        ]);
    }

    // ==================== ANALYTICS APIs ====================

    /**
     * Get room statistics
     */
    public function getRoomStatistics($roomId, $startTime, $endTime)
    {
        return $this->makeRequest('get', '/analytics/room', [
            'room_id' => $roomId,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);
    }

    /**
     * Get stream quality statistics
     */
    public function getStreamQuality($roomId, $streamId, $startTime, $endTime)
    {
        return $this->makeRequest('get', '/analytics/stream', [
            'room_id' => $roomId,
            'stream_id' => $streamId,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);
    }

    // ==================== WHITEBOARD APIs ====================

    /**
     * Create whiteboard
     */
    public function createWhiteboard($roomId, $name = '')
    {
        return $this->makeRequest('post', '/whiteboard/create', [
            'room_id' => $roomId,
            'name' => $name ?: 'Whiteboard_' . time()
        ]);
    }

    /**
     * Destroy whiteboard
     */
    public function destroyWhiteboard($whiteboardId)
    {
        return $this->makeRequest('post', '/whiteboard/destroy', [
            'whiteboard_id' => $whiteboardId
        ]);
    }

    // ==================== CLOUD PROXY APIs ====================

    /**
     * Enable/Disable cloud proxy for user
     */
    public function setCloudProxy($userId, $enable = true)
    {
        return $this->makeRequest('post', '/cloudproxy/set', [
            'user_id' => $userId,
            'enable' => $enable
        ]);
    }

    // ==================== CDN APIs ====================

    /**
     * Add CDN URL for stream
     */
    public function addCdnUrl($roomId, $streamId, $cdnUrl)
    {
        return $this->makeRequest('post', '/cdn/add', [
            'room_id' => $roomId,
            'stream_id' => $streamId,
            'cdn_url' => $cdnUrl
        ]);
    }

    /**
     * Remove CDN URL
     */
    public function removeCdnUrl($roomId, $streamId, $cdnUrl)
    {
        return $this->makeRequest('post', '/cdn/remove', [
            'room_id' => $roomId,
            'stream_id' => $streamId,
            'cdn_url' => $cdnUrl
        ]);
    }

    // ==================== BILLING APIs ====================

    /**
     * Get usage statistics for billing
     */
    public function getUsageStatistics($startDate, $endDate)
    {
        return $this->makeRequest('get', '/billing/usage', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }
}
