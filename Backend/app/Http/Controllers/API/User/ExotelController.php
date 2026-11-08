<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Models\ExotelReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ExotelController extends Controller
{
    public function connectCall(Request $request)
        {
            $apiKey = DB::table('systemflag')->where('name','ExotelKey')->pluck('value')->first();
            $apiToken = DB::table('systemflag')->where('name','ExotelToken')->pluck('value')->first();
            $subdomain = DB::table('systemflag')->where('name','ExotelSubdomain')->pluck('value')->first();
            $sid = DB::table('systemflag')->where('name','ExotelSid')->pluck('value')->first();
            $callerId = DB::table('systemflag')->where('name','ExotelCallerId')->pluck('value')->first();

            $callrequest=DB::table('callrequest')->where('id',$request->callId)->first();

            $userNo=DB::table('users')->where('id',$callrequest->userId)->pluck('contactNo')->first();
            $astrologerNo=DB::table('astrologers')->where('id',$callrequest->astrologerId)->pluck('contactNo')->first();
            


            

            $url = "https://{$apiKey}:{$apiToken}@{$subdomain}/v1/Accounts/{$sid}/Calls/connect.json";


            $data = [
                'From' => $astrologerNo,
                'To' => $userNo,
                'CallerId' => $callerId,
                'StatusCallback' => env('APP_URL') . '/api/StatusCallback/'.$request->callId.'',
                'StatusCallbackContentType' => 'application/json',
                'StatusCallbackEvents[0]' => 'terminal',
                'Record' => 'true',
                'TimeLimit'=>$callrequest->call_duration,
            ];


            $response = Http::asForm()->post($url, $data);
       


            if ($response->successful()) {
                $callData = $response->json()['Call'];

                ExotelReport::create([
                    'userId' => $callrequest->userId,
                    'astrologerId' => $callrequest->astrologerId,
                    'sid' => $callData['Sid'],
                    'call_from' => $callData['From'],
                    'call_to' => $callData['To'],
                    'callerId' => $callData['PhoneNumberSid'],
                    'start_time' => $callData['StartTime'],
                    'end_time' => $callData['EndTime'],
                    'status' => $callData['Status'],
                    'status_url' => $callData['Uri'],
                    'recording_url' => $callData['RecordingUrl'],
                    'full_report' => json_encode($callData),
                ]);

                return $response;
            } else {
                return response()->json(['error' => 'Failed to connect call'], 500);
            }
        }

        public function StatusCallback(Request $request,$callId=0)
            {


                $callData = $request->all();


                ExotelReport::updateOrCreate(
                    ['sid' => $callData['CallSid']],
                    [
                        'sid' => $callData['CallSid'],
                        'call_from' => $callData['From'],
                        'call_to' => $callData['To'],
                        'callerId' => $callData['PhoneNumberSid'],
                        'start_time' => $callData['StartTime'],
                        'end_time' => $callData['EndTime'],
                        'status' => $callData['Status'],
                        'status_url' => $callData['RecordingUrl'],
                        'recording_url' => $callData['RecordingUrl'],
                        'duration' => $callData['ConversationDuration'],
                    ]
                );

               if ($callId > 0 && $callData['Status'] == 'completed') {
                    app(\App\Http\Controllers\API\User\CallRequestController::class)->endCall(new Request([
                        'callId' => $callId,
                        'totalMin' => $callData['ConversationDuration'],
                    ]));
                }
                if ($callId > 0 && $callData['Status'] == 'failed') {
                    app(\App\Http\Controllers\API\User\CallRequestController::class)->reject(new Request([
                        'callId' => $callId,
                    ]));
                }


                return response()->json(['message' => 'Request data saved successfully'], 200);
            }

}
