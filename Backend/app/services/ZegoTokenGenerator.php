<?php

namespace App\Services;
use ZEGO\ZegoServerAssistant;
use ZEGO\ZegoErrorCodes;

class ZegoTokenGenerator
{

    public static function generateToken04($appId, $userId, $serverSecret, $expireTime = 7200, $payload = '')
    {
        if (empty($userId)) {
            throw new \Exception('userId cannot be empty');
        }

        $time = time();
        $signatureNonce = bin2hex(random_bytes(8));
        $body = [
            'app_id' => $appId,
            'user_id' => $userId,
            'nonce' => $signatureNonce,
            'ctime' => $time,
            'expire' => $time + $expireTime
        ];

        if (!empty($payload)) {
            $body['payload'] = $payload;
        }

        // Generate signature
        //Generate a random hex string of 16 hex digits.

        //Use the AppID and ServerSecret of your project.
        $appId = (int) $appId;
        $serverSecret = $serverSecret;
        $timestamp = time();
        $signature = self::makeSignature($appId, $signatureNonce, $serverSecret, $timestamp);
        $token = ZegoServerAssistant::generateToken04($appId, $userId, $serverSecret, $expireTime, $payload);
        if ($token->code == ZegoErrorCodes::success) {
            return $token->token;
        }
        return null;

    }

    private static function makeSignature($appId, $signatureNonce, $serverSecret, $timestamp)
    {
        $str = $appId . $signatureNonce . $serverSecret . $timestamp;
        $signature = md5($str);
        return $signature;
    }


    public static function generateTokenWithRoom($appId, $userId, $serverSecret, $expireTime = 7200, $roomId)
    {
        $payload = json_encode([
            'room_id' => $roomId,
            'privilege' => [
                1 => 1,
                2 => 1
            ],
        ]);

        return self::generateToken04($appId, $userId, $serverSecret, $expireTime, $payload);
    }
}
