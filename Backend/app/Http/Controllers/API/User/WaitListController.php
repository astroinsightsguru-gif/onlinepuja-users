<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\UserModel\WaitList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\services\FCMService;
use App\services\OneSignalService;

class WaitListController extends Controller
{
    public function addWaitList(Request $req)
    {
        try {


            $waitList = WaitList::create([
                'userName' => $req->userName,
                'profile' => $req->profile,
                'time' => $req->time,
                'channelName' => $req->channelName,
                'userId' => $req->userId,
                'requestType' => $req->requestType,
                'userFcmToken' => $req->userFcmToken,
                'status' => $req->status,
                'astrologerId' => $req->astrologerId,
            ]);
            $userDeviceDetail = DB::table('user_device_details')
                ->join('astrologers', 'astrologers.userId', '=', 'user_device_details.userId')
                ->where('astrologers.id', '=', $req->astrologerId)
                ->select('user_device_details.*','astrologers.userId as astrologerUserId', 'astrologers.name')
                ->get();

            $customerUser = DB::table('users')->where('id', '=', $req->userId)->first();
            $customerName = $customerUser ? $customerUser->name : ($req->userName ?? 'A Devotee');
            $astrologerName = ($userDeviceDetail && count($userDeviceDetail) > 0) ? $userDeviceDetail[0]->name : 'Astrologer';

            if ($userDeviceDetail && count($userDeviceDetail) > 0) {
                $oneSignalService = new OneSignalService();
                $userPlayerIds = $userDeviceDetail->pluck('subscription_id')->merge($userDeviceDetail->pluck('subscription_id_web'))->filter()->values()->toArray();

                if (!empty($userPlayerIds)) {
                    if ($req->requestType == 'Chat') {
                        $notification = [
                            'title' => 'Receive Chat',
                            'body' => [
                                "notificationType" => 10,
                                'call_duration' => 100,
                                'description' => 'Hey ' . $astrologerName . ', You received a chat request from ' . $customerName,
                                'icon' => 'public/notification-icon/chat.png',
                            ],
                        ];
                        $oneSignalService->sendNotification($userPlayerIds, $notification);
                    } elseif ($req->requestType == 'Audio') {
                        $notification = [
                            'title' => 'Receive Audio Call',
                            'body' => [
                                "notificationType" => 11,
                                'call_duration' => 100,
                                'description' => 'Hey ' . $astrologerName . ', You received a audio call request from ' . $customerName,
                                'icon' => 'public/notification-icon/telephone-call.png',
                            ],
                        ];
                        $oneSignalService->sendNotification($userPlayerIds, $notification);
                    } elseif ($req->requestType == 'Video') {
                        $notification = [
                            'title' => 'Receive Video Call',
                            'body' => [
                                'call_duration' => 100,
                                "notificationType" => 12,
                                'description' => 'Hey ' . $astrologerName . ', You received a video call request from ' . $customerName,
                                'icon' => 'public/notification-icon/video-camera.png',
                            ],
                        ];
                        $oneSignalService->sendNotification($userPlayerIds, $notification);
                    }
                }
            }
            return response()->json([
                'message' => 'Add to waitlist successfully',
                'recordList' => $waitList,
                'status' => 200,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function getWaitList(Request $req)
    {
        try {

            $waitList = DB::table('waitlist')
                ->where('channelName', '=', $req->channelName)
                ->leftJoin('user_device_details','user_device_details.userId','waitlist.userId')
                ->select('waitlist.*','user_device_details.subscription_id')
                ->get();
            return response()->json([
                'message' => 'Get waitlist successfully',
                'recordList' => $waitList,
                'status' => 200,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function deleteFromWaitList(Request $req)
    {
        try {

            $waitList = WaitList::find($req->id);
            if ($waitList) {
                $waitList->delete();
            }
            return response()->json([
                'message' => 'Delete waitlist successfully',
                'status' => 200,
            ], 200);

        } catch (\Exception$e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function editWaitList(Request $req)
    {
        try {
            $waitList = WaitList::find($req->id);
            if ($waitList) {
                $waitList->status = $req->status;
                $waitList->update();
            }
            return response()->json([
                'message' => 'Get waitlist successfully',
                'recordList' => $waitList,
                'status' => 200,
            ], 200);
        } catch (\Exception$e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}
