<?php

namespace App\Http\Controllers\API\AiAstrologer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AiAstrologerModel\AiAstrologer;
use App\Models\AiAstrologerModel\AiChatHistory;
use App\Models\UserModel\UserWallet;
use App\Models\Skill;
use App\Models\AstrologerCategory;
use Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Auth;
use DB;
use Symfony\Component\HttpFoundation\Session\Session;

class ApiAiAstrologerController extends Controller
{
    public function aiAstrologerList(){
        try {
            $userId = '';
            $user = authcheck();
            if ($user) {
                $userId = $user['id'] ?? $user->id ?? '';
            }
            $currencyRow = DB::table('systemflag')->where('name', 'currencySymbol')->first();
            $currency = $currencyRow ? ['name' => $currencyRow->name, 'value' => $currencyRow->value] : ['name' => 'currencySymbol', 'value' => '₹'];
                
                $aiAstrologers                      = AiAstrologer::whereNull('type')
                ->orderBy('id','DESC')
                ->get()
                ->map(function($astro) {
                    $astro->primary_skills_names    = $astro->primarySkills()->pluck('name'); 
                    $astro->all_skills_names        = $astro->allSkills()->pluck('name'); 
                    $astro->categories_names        = $astro->astrologerCategories()->pluck('name'); 
                    return $astro;
                });

                $isFreeChat = DB::table('systemflag')->where('name', 'FirstFreeChat')->select('value')->first(); // 1
                $isFreeAvailable=true;
                if ($isFreeChat->value == 1) {
                    if ($userId) {
                        $isChatRequest = DB::table('ai_chat_histories')->where('user_id', $userId)->first();
                        if ($isChatRequest) {
                            $isFreeAvailable = false;
                        } else {
                            $isFreeAvailable = true;
                        }
                    }
                } else {
                    $isFreeAvailable = false;
                }
                $wallet_amount = DB::table('user_wallets')->where('userId', $userId)->value('amount');
                
                return response()->json([
                    'recordList'        => $aiAstrologers,
                    'currency'          =>$currency,
                    'isFreeAvailable'   =>$isFreeAvailable,
                    'wallet_amount'     =>$wallet_amount,
                    'status' => 200,
                ], 200);
                
            } catch (Exception $e) {
                return response()->json([
                    'error' => true,
                    'message' => $e->getMessage(),
                    'status' => 500,
                ], 500);
            }
        }
        
        public function aiChatButton(Request $request){
            if (!Auth::guard('api')->user()) {
                return response()->json(['error' => 'Unauthorized', 'status' => 401], 401);
            }
            
            $astrologerId               = $request->astrologerId;
            $charge                     = $request->charge;
            $chat_duration              = $request->chat_duration;
            
            $userName                   = authcheck()['name'];
            $userId                     = authcheck()['id'];
            
            $firstFreerecharge          = DB::table('systemflag')->where('name', 'FirstFreeChatRecharge')->select('value')->first();
            $minAmount                  = DB::table('systemflag')->where('name', 'MinAmountFreeChatCall')->select('value')->first();
            $wallets                    = DB::table('user_wallets')->where('userId', $userId)->first();
            $walletAmount               = $wallets ? (float) $wallets->amount : 0; 
            
            $isChatRequest              = DB::table('ai_chat_histories')->where('user_id', $userId)->first();
            
            if($isChatRequest == null){
                return response()->json([
                    'message'           => 'Please wait for a second!',
                    'userId'            =>$userId,
                    'astrologerId'      =>$astrologerId,
                    'charge'            =>$charge,
                    'chat_duration'     =>$chat_duration,
                    'userName'          =>$userName,
                    'status'            => 200,
                ], 200);
            }else{
                $charge                 = $request->charge;
                $chatDuration           = $request->chat_duration/60;
                $needAmount             =$charge * $chatDuration;
                
                if ($needAmount > $walletAmount) {
                    return response()->json([
                        'message'         => 'Insufficient balance. Please recharge your wallet.',
                        'needAmount'    =>(int)$needAmount,
                        'userBalance'   =>$walletAmount,
                        'status'        => 400,
                    ], 400);
                }else{
                    return response()->json([
                        'message'       => 'Please wait for a second!',
                        'userId'        =>$userId,
                        'astrologerId'  =>$astrologerId,
                        'charge'        =>$charge,
                        'chat_duration' =>$chat_duration,
                        'userName'      =>$userName,
                        'status'        => 200,
                    ], 200);
                }
            }
            return response()->json([
                'message' => 'Something went wrong',
                'status'  => 500,
                ], 500);
        }
        
        
        public function aiChattingPage(Request $request)
        {       
            $astrologerId               = $request->query('astrologerId');
            $charge                     = $request->query('charge');
            $chatDuration               = $request->query('chat_duration')/60;
            $astrologer                 = AiAstrologer::where('id', $astrologerId)->first();
            return response()->json([
                'astrologerId'          =>$astrologerId,
                'charge'                =>$charge,
                'chatDuration'          =>$chatDuration,
                'recordList'            =>$astrologer,
                'status'                => 200,
            ], 200);
        }
        
        public function storeAiChatHistory(Request $request){
            
            $aiAstrologer               = AiAstrologer::find($request->astrologer_id);
            
            // $user_country=User::where('id',authcheck()['id'])->where('country','India')->first();   // commented
            $user_country=User::where('id',authcheck()['id'])->where('countryCode','+91')->first();   // added
            $inr_usd_conv_rate = DB::table('systemflag')->where('name','UsdtoInr')->select('value')->first();
            $actualDurationInSeconds    = $request->actualDuration;
            // if($user_country){
            //     $aiAstrologer->chat_charge=convertinrtousd($aiAstrologer->chat_charge);
            // }
            $aiAstrologer->chat_charge = $user_country ? $aiAstrologer->chat_charge : convertusdtoinr($aiAstrologer->chat_charge);
            
            $actualDurationInSeconds    = $request->actualDuration;
            $deduction                  = ceil($actualDurationInSeconds / 60); 
            $deductionAmount            = $deduction * $aiAstrologer->chat_charge; 
            $isFree                     = DB::table('ai_chat_histories')->where('user_id', authcheck()['id'])->first();
            
            if(!empty($isFree)){
                $aiChatHistory = AiChatHistory::create([
                    'user_id'           => authcheck()['id'],
                    'ai_astrologer_id'  => $request->astrologer_id,
                    'chat_duration'     => $request->actualDuration,
                    'chat_min'          => $request->chatDuration,
                    'chat_rate'         => $aiAstrologer->chat_charge,
                    'deduction'         => $deductionAmount,
                    'is_free'           => 0,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                    'inr_usd_conversion_rate'=>$inr_usd_conv_rate->value,
                    
                ]);
                $wallet_deduction = UserWallet::where('userId', authcheck()['id'])->first();
                if ($wallet_deduction) {
                    // $wallet_deduction->amount -= ($user_country) ? ($deductionAmount * $inr_usd_conv_rate->value) : $deductionAmount;
                    $wallet_deduction->amount -= $deductionAmount;
                    $wallet_deduction->save();
                } else {
                    return response()->json([
                        'message'       => 'Wallet not found.',
                        'status'        => 404,
                        ], 404);
                }
                
                // $wallet_deduction = UserWallet::where('userId', authcheck()['id'])->first();
                // if ($wallet_deduction) {
                //     $wallet_deduction->amount -= $deductionAmount;
                //     $wallet_deduction->save(); 
                // } else {
                //     return response()->json(['error' => 'Wallet not found.'], 404);
                // }
            }else{
                $aiChatHistory = AiChatHistory::create([
                    'user_id'           => authcheck()['id'],
                    'ai_astrologer_id'  => $request->astrologer_id,
                    'chat_duration'     => $request->actualDuration,
                    // 'chat_min'       => $request->chatMin,
                    'chat_rate'         => $aiAstrologer->chat_charge,
                    'is_free'           => 0,
                    // 'deduction'      => $deduction,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
                
                $wallet_deduction = UserWallet::where('userId', authcheck()['id'])->first();
                if ($wallet_deduction) {
                    // $wallet_deduction->amount -= ($user_country) ? ($deductionAmount * $inr_usd_conv_rate->value) : $deductionAmount;
                    $wallet_deduction->amount -= $deductionAmount; 
                    $wallet_deduction->save(); 
                } else {
                    return response()->json([
                        'message'       => 'Wallet not found.',
                        'status'        => 404,
                        ], 404);
                }
            }
            
            return response()->json([
                'message'               => 'Chat ended successfully.',
                'data'                  => $aiChatHistory,
                'walletAmount'          => $wallet_deduction->amount,
                'status' => 200,
            ], 200);
        }

        public function getPrescriptions(Request $request)
        {
            try {
                $userId = $request->input('user_id') ?? $request->input('userId');
                $astrologerId = $request->input('astrologer_id') ?? $request->input('astrologerId');

                if (!$userId && !$astrologerId) {
                    $user = Auth::guard('api')->user();
                    if ($user) {
                        $userId = $user->id;
                    }
                }

                $query = DB::table('astrologer_prescriptions')
                    ->leftJoin('astrologers', 'astrologers.id', '=', 'astrologer_prescriptions.astrologer_id')
                    ->leftJoin('pujas', 'pujas.id', '=', 'astrologer_prescriptions.puja_id')
                    ->select(
                        'astrologer_prescriptions.*',
                        'astrologer_prescriptions.title as remedy_title',
                        'astrologer_prescriptions.prescribed_gemstone as gemstone',
                        'astrologer_prescriptions.prescribed_mantra as mantra',
                        'astrologers.name as astrologer_name',
                        'astrologers.profileImage as astrologer_profile_image',
                        'pujas.puja_title',
                        'pujas.puja_place',
                        'pujas.puja_duration'
                    );

                if ($userId) {
                    $query->where('astrologer_prescriptions.user_id', $userId);
                }
                if ($astrologerId) {
                    $query->where('astrologer_prescriptions.astrologer_id', $astrologerId);
                }

                $list = $query->orderBy('astrologer_prescriptions.id', 'DESC')->get();

                return response()->json([
                    'status' => 200,
                    'recordList' => $list,
                ], 200);
            } catch (\Throwable $e) {
                return response()->json([
                    'status' => 500,
                    'message' => $e->getMessage(),
                    'recordList' => [],
                ], 500);
            }
        }

        public function getPrescriptionPujas(Request $request)
        {
            try {
                $pujas = DB::table('pujas')
                    ->where('puja_status', 1)
                    ->select('id', 'puja_title', 'puja_place', 'puja_duration', 'puja_images')
                    ->orderBy('puja_title', 'ASC')
                    ->get();

                return response()->json([
                    'status' => 200,
                    'recordList' => $pujas,
                ], 200);
            } catch (\Throwable $e) {
                return response()->json([
                    'status' => 500,
                    'message' => $e->getMessage(),
                    'recordList' => [],
                ], 500);
            }
        }

        public function prescribeRemedy(Request $request)
        {
            try {
                $validator = Validator::make($request->all(), [
                    'astrologer_id' => 'required',
                    'user_id' => 'required',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => 400,
                        'error' => $validator->errors(),
                    ], 400);
                }

                $id = DB::table('astrologer_prescriptions')->insertGetId([
                    'astrologer_id' => $request->input('astrologer_id'),
                    'user_id' => $request->input('user_id'),
                    'call_id' => $request->input('call_id'),
                    'puja_id' => $request->input('puja_id'),
                    'remedy_type' => $request->input('remedy_type', 'puja'),
                    'title' => $request->input('title', 'Vedic Remedy Prescription'),
                    'reason' => $request->input('reason'),
                    'prescribed_gemstone' => $request->input('prescribed_gemstone'),
                    'prescribed_mantra' => $request->input('prescribed_mantra'),
                    'suggested_amount' => $request->input('suggested_amount'),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $record = DB::table('astrologer_prescriptions')->where('id', $id)->first();

                return response()->json([
                    'status' => 200,
                    'message' => 'Prescription created successfully',
                    'recordList' => $record,
                ], 200);
            } catch (\Throwable $e) {
                return response()->json([
                    'status' => 500,
                    'message' => $e->getMessage(),
                ], 500);
            }
        }

        public function getAiTwinStatus(Request $request)
        {
            try {
                $astrologerId = $request->input('astrologer_id');
                if (!$astrologerId) {
                    return response()->json(['status' => 400, 'message' => 'astrologer_id required'], 400);
                }

                $astro = DB::table('astrologers')->where('id', $astrologerId)->first();
                if (!$astro) {
                    return response()->json(['status' => 404, 'message' => 'Astrologer not found'], 404);
                }

                $stats = [
                    'ai_twin_enabled' => (bool)($astro->ai_twin_enabled ?? 0),
                    'ai_twin_name' => $astro->ai_twin_name,
                    'ai_twin_tone' => $astro->ai_twin_tone,
                    'ai_twin_greeting' => $astro->ai_twin_greeting,
                    'ai_twin_prompt' => $astro->ai_twin_prompt,
                    'ai_twin_system' => $astro->ai_twin_system,
                    'ai_twin_charge' => (float)($astro->ai_twin_charge ?? 5.0),
                    'ai_twin_auto_offline' => (bool)($astro->ai_twin_auto_offline ?? 1),
                ];

                return response()->json([
                    'status' => 200,
                    'recordList' => $stats,
                ], 200);
            } catch (\Throwable $e) {
                return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
            }
        }

        public function setupAiTwin(Request $request)
        {
            try {
                $astrologerId = $request->input('astrologer_id');
                if (!$astrologerId) {
                    return response()->json(['status' => 400, 'message' => 'astrologer_id required'], 400);
                }

                $updateData = [];
                foreach (['ai_twin_enabled', 'ai_twin_name', 'ai_twin_tone', 'ai_twin_greeting', 'ai_twin_prompt', 'ai_twin_system', 'ai_twin_charge', 'ai_twin_auto_offline'] as $f) {
                    if ($request->has($f)) {
                        $updateData[$f] = $request->input($f);
                    }
                }
                if (!empty($updateData)) {
                    $updateData['updated_at'] = now();
                    DB::table('astrologers')->where('id', $astrologerId)->update($updateData);
                }

                $astro = DB::table('astrologers')->where('id', $astrologerId)->first();

                return response()->json([
                    'status' => 200,
                    'message' => 'AI Twin updated successfully',
                    'recordList' => [
                        'ai_twin_enabled' => (bool)($astro->ai_twin_enabled ?? 0),
                        'ai_twin_name' => $astro->ai_twin_name,
                        'ai_twin_tone' => $astro->ai_twin_tone,
                        'ai_twin_charge' => (float)($astro->ai_twin_charge ?? 5.0),
                    ],
                ], 200);
            } catch (\Throwable $e) {
                return response()->json(['status' => 500, 'message' => $e->getMessage()], 500);
            }
        }
    }
    