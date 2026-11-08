<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\AstrologerModel\Astrologer;
use App\Models\UserModel\User;
use App\Models\UserModel\UserReport;
use App\services\FCMService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\services\OneSignalService;

class UserReportController extends Controller
{
    //Add a report
    public function addReport(Request $req)
    {
        try {
            //Get a user id
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            } else {
                $id = Auth::guard('api')->user()->id;
            }

            $data = $req->only(
                'userId',
                'firstName',
                'lastName',
                'contactNo',
                'gender',
                'birthDate',
                'birthTime',
                'birthPlace',
                'occupation',
                'maritalStatus',
                'answerLanguage',
                'comments',
                'reportFile',
            );

            //Validate the data
            $validator = Validator::make($data, [
                'userId' => 'required',
                'firstName' => 'required',
                'contactNo' => 'required',
                'gender' => 'required',
                'birthDate' => 'required',
                'birthTime' => 'required',
                'birthPlace' => 'required',
                'comments' => 'required',
            ]);

            //Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }

            $user_country = User::where('id', $id)->where('countryCode', '+91')->first();
            $inr_usd_conv_rate = DB::table('systemflag')->where('name', 'UsdtoInr')->select('value')->first();
            $convRateVal = ($inr_usd_conv_rate && !empty($inr_usd_conv_rate->value)) ? (float)$inr_usd_conv_rate->value : 83.5;
            $astrologercountry = Astrologer::where('id', $req->astrologerId)->where('countryCode', '+91')->first();

            $reportCharge = Astrologer::where('id', '=', $req->astrologerId)->first();
            if (!$reportCharge) {
                return response()->json(['error' => 'Astrologer not found', 'status' => 404], 404);
            }

            $rateValue = $user_country ? (float)$reportCharge->reportRate : convertusdtoinr($reportCharge->reportRate_usd ?? $reportCharge->reportRate);

            // Check wallet balance
            $userwallet = DB::table('user_wallets')->where('userId', '=', $req->userId)->first();
            $walletBalance = $userwallet ? (float)$userwallet->amount : 0.0;
            if ($rateValue > 0 && $walletBalance < $rateValue) {
                return response()->json(['error' => 'Insufficient wallet balance', 'status' => 400], 400);
            }

            //Create a user report
            $userReport = UserReport::create([
                'userId' => $req->userId,
                'firstName' => $req->firstName,
                'lastName' => $req->lastName,
                'contactNo' => $req->contactNo,
                'gender' => $req->gender,
                'birthDate' => $req->birthDate,
                'birthTime' => $req->birthTime,
                'birthPlace' => $req->birthPlace,
                'occupation' => $req->occupation,
                'maritalStatus' => $req->maritalStatus,
                'answerLanguage' => $req->answerLanguage,
                'partnerName' => $req->partnerName,
                'partnerBirthDate' => $req->partnerBirthDate,
                'partnerBirthTime' => $req->partnerBirthTime,
                'partnerBirthPlace' => $req->partnerBirthPlace,
                'comments' => $req->comments,
                'createdBy' => $id,
                'modifiedBy' => $id,
                'reportType' => $req->reportType,
                'astrologerId' => $req->astrologerId,
                'countryCode' => $req->countryCode,
                'reportRate' => $rateValue,
                'inr_usd_conversion_rate' => $convRateVal,
                'created_at' => DB::raw('NOW()'),
                'updated_at' => DB::raw('NOW()'),
            ]);

            if ($rateValue > 0) {
                $commission = DB::table('commissions')
                    ->where('commissionTypeId', '=', '3')
                    ->where('astrologerId', '=', $req->astrologerId)
                    ->get();
                if ($commission && count($commission) > 0) {
                    $adminCommission = ($commission[0]->commission * $rateValue) / 100;
                } else {
                    $syscommission = DB::table('systemflag')->where('name', 'ReportCommission')->select('value')->get();
                    $adminCommission = ($syscommission && count($syscommission) > 0) ? ($syscommission[0]->value * $rateValue) / 100 : 0;
                }
                $astrologerCommission = $rateValue - $adminCommission;

                // Create order request first to get orderId
                $orderRequest = array(
                    'userId' => $req->userId,
                    'astrologerId' => $req->astrologerId,
                    'orderType' => 'report',
                    'totalPayable' => $rateValue,
                    'orderStatus' => 'Complete',
                    'totalMin' => null,
                    'callId' => null,
                    'inr_usd_conversion_rate' => $convRateVal,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                );
                DB::table('order_request')->insert($orderRequest);
                $orderId = DB::getPdo()->lastInsertId();

                // Deduct from user wallet
                DB::table('user_wallets')->where('userId', '=', $req->userId)->update([
                    'amount' => $walletBalance - $rateValue,
                    'modifiedBy' => $id,
                    'updated_at' => Carbon::now(),
                ]);

                $transaction = array(
                    'userId' => $req->userId,
                    'amount' => $rateValue,
                    'isCredit' => false,
                    "transactionType" => 'Report',
                    "orderId" => $orderId,
                    "astrologerId" => $req->astrologerId,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'inr_usd_conversion_rate' => $convRateVal,
                );
                DB::table('wallettransaction')->insert($transaction);

                $astrologerUser = DB::table('astrologers')->where('id', $req->astrologerId)->select('userId')->first();
                $astrologerUserId = $astrologerUser ? $astrologerUser->userId : null;

                if ($astrologerUserId) {
                    $astrologerWallet = DB::table('user_wallets')->where('userId', '=', $astrologerUserId)->first();
                    $currentAstroWallet = $astrologerWallet ? (float)$astrologerWallet->amount : 0.0;

                    if ($astrologerWallet) {
                        DB::table('user_wallets')->where('userId', $astrologerUserId)->update([
                            'amount' => $currentAstroWallet + $astrologerCommission,
                            'modifiedBy' => $id,
                            'updated_at' => Carbon::now(),
                        ]);
                    } else {
                        DB::table('user_wallets')->insert([
                            'userId' => $astrologerUserId,
                            'amount' => $astrologerCommission,
                            'createdBy' => $id,
                            'modifiedBy' => $id,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                    }

                    $astrologerTransaction = array(
                        'userId' => $astrologerUserId,
                        'amount' => $astrologerCommission,
                        'isCredit' => true,
                        "transactionType" => 'Report',
                        "orderId" => $orderId,
                        "astrologerId" => $req->astrologerId,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                        'inr_usd_conversion_rate' => $convRateVal,
                    );
                    DB::table('wallettransaction')->insert($astrologerTransaction);
                }

                if ($adminCommission && $adminCommission > 0 && $astrologerUserId) {
                    $adminGetCommission = array(
                        'commissionTypeId' => 3,
                        "amount" => $adminCommission,
                        "commissionId" => ($commission && count($commission) > 0) ? $commission[0]->id : null,
                        "orderId" => $orderId,
                        "createdBy" => $astrologerUserId,
                        "modifiedBy" => $astrologerUserId,
                        'inr_usd_conversion_rate' => $convRateVal,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    );
                    DB::table('admin_get_commissions')->insert($adminGetCommission);
                }

                $userDeviceDetail = DB::table('user_device_details')
                    ->join('astrologers', 'astrologers.userId', '=', 'user_device_details.userId')
                    ->where('astrologers.id', '=', $req->astrologerId)
                    ->select('user_device_details.*', 'astrologers.userId as astrologerUserId', 'astrologers.name')
                    ->get();

                $customerUser = DB::table('users')->where('id', '=', $req->userId)->first();
                $customerName = $customerUser ? $customerUser->name : 'User';

                if ($userDeviceDetail && count($userDeviceDetail) > 0) {
                    $oneSignalService = new OneSignalService();
                    $userPlayerIds = $userDeviceDetail->pluck('subscription_id')->merge($userDeviceDetail->pluck('subscription_id_web'))->filter()->values()->toArray();
                    $notification = [
                        'title' => 'Hey ' . $userDeviceDetail[0]->name . ', you received a report request from ' . $customerName,
                        'body' => [
                            "notificationType" => 9,
                            'call_duration' => 100,
                            'description' => 'Hey ' . $userDeviceDetail[0]->name . ', you received a report request from ' . $customerName,
                        ],
                    ];
                    if (!empty($userPlayerIds)) {
                        $oneSignalService->sendNotification($userPlayerIds, $notification);
                    }

                    $notification = array(
                        'userId' => $userDeviceDetail[0]->astrologerUserId,
                        'title' => 'Hey ' . $userDeviceDetail[0]->name . ', you received a report request from ' . $customerName,
                        'description' => 'Hey ' . $userDeviceDetail[0]->name . ', you received a report request from ' . $customerName,
                        'notificationId' => null,
                        'createdBy' => $userDeviceDetail[0]->astrologerUserId,
                        'modifiedBy' => $userDeviceDetail[0]->astrologerUserId,
                        'notification_type' => 9,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    );
                    DB::table('user_notifications')->insert($notification);
                }
            }
            return response()->json([
                'message' => 'User report add sucessfully',
                'recordList' => $userReport,
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

    //Get all report data
    public function getUserReportRequest(Request $req)
    {
        try {
            // if (!Auth::guard('api')->user()) {
            //     return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            // }
            $data = $req->only(
                'astrologerId'
            );
            $validator = Validator::make($data, [
                'astrologerId' => 'required',
            ]);

            //Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }
            $userReport = UserReport::join('users', 'users.id', '=', 'user_reports.userId')
                ->join('report_types', 'report_types.id', '=', 'user_reports.reportType')
                ->where('astrologerId', '=', $req->astrologerId)
                ->whereNull('reportFile')
                ->select('user_reports.*', 'users.name as userName', 'users.profile', 'users.contactNo', 'report_types.reportImage', 'report_types.title as reportType')
                ->orderBy('user_reports.id', 'DESC')
                ->get();

            return response()->json([
                'recordList' => $userReport,
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

    //Update user report
    public function updateUserReport(Request $req, $id)
    {
        try {
            //Get a user id
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }

            $req->validate = ([
                'userId',
                'firstName',
                'lastName',
                'contactNo',
                'gender',
                'birthDate',
                'birthTime',
                'birthPlace',
                'occupation',
                'maritalStatus',
                'answerLanguage',
                'partnerName',
                'partnerBirthDate',
                'partnerBirthTime',
                'partnerBirthPlace',
                'comments',
                'reportFile',
            ]);

            $userReport = UserReport::find($id);
            if ($userReport) {
                $userReport->userId = $req->userId;
                $userReport->firstName = $req->firstName;
                $userReport->lastName = $req->lastName;
                $userReport->contactNo = $req->contactNo;
                $userReport->gender = $req->gender;
                $userReport->birthDate = $req->birthDate;
                $userReport->birthTime = $req->birthTime;
                $userReport->birthPlace = $req->birthPlace;
                $userReport->occupation = $req->occupation;
                $userReport->maritalStatus = $req->maritalStatus;
                $userReport->answerLanguage = $req->answerLanguage;
                $userReport->partnerName = $req->partnerName;
                $userReport->partnerBirthDate = $req->partnerBirthDate;
                $userReport->partnerBirthTime = $req->partnerBirthTime;
                $userReport->partnerBirthPlace = $req->partnerBirthPlace;
                $userReport->comments = $req->comments;
                $userReport->update();




                return response()->json([
                    'message' => 'User report update sucessfully',
                    'recordList' => $userReport,
                    'status' => 200,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'User report is not found',
                    'status' => 404,
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    public function addUserReportFile(Request $req)
    {
        try {
            $data = $req->only(
                'id',
                'reportFile'
            );
            //Validate the data
            $validator = Validator::make($data, [
                'id' => 'required',
                'reportFile' => 'required',
            ]);

            //Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json([
                    'error' => $validator->messages(),
                    'status' => 400,
                ], 400);
            }
            $reportRequest = UserReport::find($req->id);
            if ($reportRequest) {
                if ($req->reportFile) {

                    // If already stored file path
                    if (Str::contains($req->reportFile, 'storage/')) {
                        $path = $req->reportFile;
                    } else {

                        // Directory inside public
                        $directory = public_path('storage/reports');

                        // Create directory if not exists
                        if (!File::exists($directory)) {
                            File::makeDirectory($directory, 0755, true);
                        }

                        $fileName = 'report_' . $req->id . '_' . Carbon::now()->timestamp . '.pdf';
                        $fullPath = $directory . '/' . $fileName;

                        // Remove base64 header if present
                        $base64 = $req->reportFile;
                        if (Str::contains($base64, ',')) {
                            $base64 = explode(',', $base64)[1];
                        }

                        file_put_contents($fullPath, base64_decode($base64));

                        // Public path to store in DB
                        $path = 'public/storage/reports/' . $fileName;
                    }
                } else {
                    $path = null;
                }

                $reportRequest->reportFile = $path;
                $reportRequest->updated_at = Carbon::now()->timestamp;
                $reportRequest->update();

                $userDeviceDetail = DB::table('user_device_details')
                    ->JOIN('users', 'users.id', '=', 'user_device_details.userId')
                    ->WHERE('users.id', '=', $reportRequest->userId)
                    ->SELECT('user_device_details.*')
                    ->get();

                if ($userDeviceDetail && count($userDeviceDetail) > 0) {

                    // One signal FOr notification send
                    $oneSignalService = new OneSignalService();
                    //  $userPlayerIds = $userDeviceDetail->pluck('subscription_id')->all();
                    $userPlayerIds = $userDeviceDetail->pluck('subscription_id')->merge($userDeviceDetail->pluck('subscription_id_web'))->values()->toArray();
                    $notification = [
                        'title' => 'Received Report',
                        'body' => [
                            "notificationType" => 9,
                            'description' => 'Report has been received',
                        ],
                    ];
                    // Send the push notification using the OneSignalService
                    $response = $oneSignalService->sendNotification($userPlayerIds, $notification);

                    $notification = array(
                        'userId' => $reportRequest->userId,
                        'title' => 'Received Report',
                        'description' => 'Your Report has been received',
                        'notificationId' => null,
                        'createdBy' => $reportRequest->userId,
                        'modifiedBy' => $reportRequest->userId,
                        'notification_type' => 9,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    );
                    DB::table('user_notifications')->insert($notification);
                }

                return response()->json([
                    'message' => 'User report update sucessfully',
                    'recordList' => $reportRequest,
                    'status' => 200,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => false,
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }



    public function getUserReportRequestById(Request $req)
    {
        try {
            // if (!Auth::guard('api')->user()) {
            //     return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            // }
            $data = $req->only(
                'id'
            );
            $validator = Validator::make($data, [
                'id' => 'required',
            ]);
            //Send failed response if request is not valid
            if ($validator->fails()) {
                return response()->json(['error' => $validator->messages(), 'status' => 400], 400);
            }
            $userReport = UserReport::join('users', 'users.id', '=', 'user_reports.userId')
                ->join('report_types', 'report_types.id', '=', 'user_reports.reportType')
                ->where('user_reports.id', '=', $req->id)
                ->whereNull('reportFile')
                ->select('user_reports.*', 'users.name as userName', 'users.profile', 'users.contactNo', 'report_types.reportImage', 'report_types.title as reportType')
                ->orderBy('user_reports.id', 'DESC')
                ->get();
            return response()->json([
                'recordList' => $userReport,
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
}
