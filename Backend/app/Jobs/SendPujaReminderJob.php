<?php

// app/Jobs/SendPujaReminderJob.php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PujaOrder;
use App\services\OneSignalService;
use Illuminate\Support\Facades\DB;

class SendPujaReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $puja;

    public function __construct(PujaOrder $puja)
    {
        $this->puja = $puja;
    }

    public function handle()
    {
        // Notification for devotee
        $user = !empty($this->puja->user_id)
            ? DB::table('users')->where('id', $this->puja->user_id)->first()
            : null;

        // Notification for astrologer
        $astrologer = !empty($this->puja->astrologer_id)
            ? DB::table('astrologers')->where('id', $this->puja->astrologer_id)->first()
            : null;

        $astrologerName = !empty($astrologer->name) ? $astrologer->name : 'Vedic Pandit / Acharya';
        $devoteeName = !empty($user->name) ? $user->name : 'Devotee';
        $startTime = !empty($this->puja->puja_start_datetime) ? $this->puja->puja_start_datetime : 'soon';

        if (!empty($this->puja->user_id)) {
            $this->sendNotification(
                $this->puja->user_id,
                "Your sacred Puja is starting soon! 🕉️",
                "Your scheduled puja is preparing to begin with {$astrologerName} at {$startTime}. Please be prepared with your family."
            );
        }

        if ($astrologer && !empty($astrologer->userId)) {
            $this->sendNotification(
                $astrologer->userId,
                "Puja reminder for your upcoming session",
                "You have a puja scheduled with {$devoteeName} at {$startTime}."
            );
        }
    }

    protected function sendNotification($userId, $title, $message)
    {
        $userDeviceDetail = DB::table('user_device_details')
            ->where('userId', '=', $userId)
            ->select('subscription_id', 'subscription_id_web')
            ->first();

        if (!empty($userDeviceDetail)) {
            $oneSignalService = new OneSignalService();
            $userDeviceDetails = array_values((array)$userDeviceDetail);
            
            $notificationData = [
                'title' => $title,
                'body' => ['description' => $message, "notificationType" => 30],
            ];

            $oneSignalService->sendNotification($userDeviceDetails, $notificationData);

            DB::table('user_notifications')->insert([
                'userId' => $userId,
                'title' => $title,
                'description' => $message,
                'createdBy' => 0, // System-generated
                'modifiedBy' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
