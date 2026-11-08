<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\Puja;
use App\Models\Pujapackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\services\FCMService;
use App\services\OneSignalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PujaRecommendController extends Controller
{
    public function addPujaRecommend(Request $req)
    {
        try {

            $data = $req->only(
                'puja_id',
                'package_id',
                'userId',
                'astrologerId',
            );

            //Validate the data
            $validator = Validator::make($data, [
                'puja_id' => 'required',
                // 'package_id' => 'required',
                'userId' => 'required',
                'astrologerId' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }
            $puja=Puja::where('id',$req->puja_id)->first();
            $package=Pujapackage::where('id',$req->package_id)->first();
            // dd($package);
            if(!$puja){
                return response()->json([
                    'message' => 'Puja Not Found',
                    'status' => 400,
                ], 400);
            }

            $pujaRecommId = DB::table('puja_recommends')->updateOrInsert(
                [
                    'puja_id' => $req->puja_id,
                    'package_id' => $req->package_id,
                    'userId' => $req->userId,
                    'astrologerId' => $req->astrologerId,
                ],
                [
                    'recommDateTime' => Carbon::now(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );


            // Fetch the full inserted record

            $astrologer = DB::Table('astrologers')
            ->leftjoin('user_device_details', 'user_device_details.userId', 'astrologers.userId')
            ->where('astrologers.id', '=', $req->astrologerId)
            ->select('astrologers.name', 'astrologers.profileImage', 'user_device_details.fcmToken')
            ->get();

            $userDeviceDetail = DB::table('user_device_details')
            ->JOIN('users', 'users.id', '=', 'user_device_details.userId')
            ->WHERE('user_device_details.userId', '=', $req->userId)
            ->SELECT('user_device_details.*','users.name')
            ->get();


            if ($userDeviceDetail && count($userDeviceDetail) > 0) {


                 // One signal FOr notification send
                 $oneSignalService = new OneSignalService();
                //  $userPlayerIds = $userDeviceDetail->pluck('subscription_id')->all();
                $userPlayerIds = $userDeviceDetail->pluck('subscription_id')->merge($userDeviceDetail->pluck('subscription_id_web'))->values()->toArray();
                 $notification = [
                    'title' => 'Puja Recommendation',
                        'body' => [
                                'description' => 'Hey '.$userDeviceDetail[0]->name.', You received a Puja Recommendation from ' . $astrologer[0]->name,
                        ],
                 ];
                 // Send the push notification using the OneSignalService
                 $response = $oneSignalService->sendNotification($userPlayerIds, $notification);
                 $notification = array(
                    'userId' => $userDeviceDetail[0]->userId,
                    'title' => 'Puja Recommendation',
                    'description' => 'Hey '.$userDeviceDetail[0]->name.', You received a Puja Recommendation from ' . $astrologer[0]->name,
                    'notificationId' => null,
                    'createdBy' => $userDeviceDetail[0]->userId,
                    'modifiedBy' => $userDeviceDetail[0]->userId,
                    'notification_type' => 50,
                     'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                );
                DB::table('user_notifications')->insert($notification);
            }

                return response()->json([
                    'message' => 'Added in puja recommend successfully',
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


    public function getPujaRecommend(Request $req)
    {
        try {

            $data = $req->only(
                'userId',
            );

            //Validate the data
            $validator = Validator::make($data, [
                'userId' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            $currentDatetime = \Carbon\Carbon::now();
            $Pujalist = Puja::where('puja_start_datetime', '>', $currentDatetime)
            ->where('puja_status', 1)
            ->join('puja_recommends', 'puja_recommends.puja_id', '=', 'pujas.id')
            ->join('astrologers', 'astrologers.id', '=', 'puja_recommends.astrologerId')
            ->where('puja_recommends.userId', '=', $req->userId)
            ->where('puja_recommends.recommDateTime', '>=', Carbon::now()->subDay())
            ->select('pujas.*', 'puja_recommends.recommDateTime', 'puja_recommends.package_id as puja_package_id','puja_recommends.id as recommend_id', 'astrologers.name as astrologerName','puja_recommends.isPurchased') // Select recommended package_id
            ->get()
            ->map(function ($puja) {
                $puja->packages = $puja->package();
                return $puja;
            });
        
        

            return response()->json([
                'message' => 'Get Puja successfully',
                'recordList' => $Pujalist,
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
