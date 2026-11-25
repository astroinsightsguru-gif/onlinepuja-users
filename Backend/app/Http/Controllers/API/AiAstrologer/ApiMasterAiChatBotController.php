<?php

namespace App\Http\Controllers\API\AiAstrologer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\services\OpenAIService;
use Auth;

use App\Models\AiAstrologerModel\AiAstrologer;
use App\Models\AiAstrologerModel\AiChatHistory;
use App\Models\UserModel\UserWallet;
use Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use DB;
use Symfony\Component\HttpFoundation\Session\Session;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PDF;
use Response;

class ApiMasterAiChatBotController extends Controller
{
    protected $openAIService;
    
    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }
    
    public function masterAiResponse(Request $request)
    {
        $message = trim((string) $request->input('message'));
        if (empty($message)) {
            return response()->json(['error' => 'Message is required', 'status' => 400], 400);
        }

        $astrologerId = (int) (
            $request->input('astrologerId') ??
            $request->input('astrologer_id') ??
            $request->input('ai_astrologer_id') ??
            1
        );

        $user = Auth::guard('api')->user();
        
        try {
            $response = null;
            if ($this->openAIService) {
                try {
                    $response = $this->openAIService->askChatGPT($message, $astrologerId);
                } catch (\Throwable $oe) {
                    \Log::warning('OpenAIService failed in masterAiResponse, falling back to AiEngineService: ' . $oe->getMessage());
                }
            }

            if (empty($response) || !is_string($response)) {
                $aiEngine = app(\App\Services\AiEngineService::class);
                $context = $user ? [
                    'name' => $user->name,
                    'birthDate' => $user->birthDate,
                    'birthPlace' => $user->birthPlace,
                ] : null;

                $humanAstro = \Illuminate\Support\Facades\DB::table('astrologers')->where('id', $astrologerId)->first();
                if ($humanAstro) {
                    $astroName = $humanAstro->name ?? 'आचार्य';
                    $tone = $humanAstro->ai_twin_tone ?? 'empathetic';
                    $prompt = $humanAstro->ai_twin_prompt ?? '';
                    $skills = $humanAstro->allSkills ?? $humanAstro->primarySkill ?? 'Vedic Astrology, Kundli';

                    $sys = "You are the official Vedic AI Digital Twin of Acharya {$astroName} on OnlinePuja.live (Specialties: {$skills}). Tone: {$tone}. Provide compassionate, authentic Jyotish guidance matching Acharya Ji's custom philosophy and rules: {$prompt}. Speak in natural Hindi or English matching the devotee's language.";
                } else {
                    $sys = "You are a revered Vedic Astrologer & spiritual counselor for OnlinePuja.live. Provide compassionate, authentic Jyotish guidance, remedies, and puja recommendations in natural Hindi or English matching the user's query.";
                }
                $response = $aiEngine->generateText($message, $sys, $context);
            }
            
            return response()->json([
                'message' => $response,
                'recordList' => [
                    'message' => $response,
                    'astrologerId' => $astrologerId,
                ],
                'status' => 200,
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('masterAiResponse error: ' . $e->getMessage());
            return response()->json([
                'message' => 'प्रणाम! ग्रह गोचर का विश्लेषण प्रगति पर है। कृपया कुछ क्षण बाद पुनः प्रयास करें।',
                'status' => 200,
            ], 200);
        }
    }
    
    
    
    public function checkUserBalanceApi(){
        
        if (Auth::guard('api')->user()) {
            $userId=Auth::guard('api')->user()->id;
            $currencyRow = DB::table('systemflag')->where('name', 'currencySymbol')->first();
            $currency = $currencyRow ? ['name' => $currencyRow->name, 'value' => $currencyRow->value] : ['name' => 'currencySymbol', 'value' => '₹'];
                
                $user_balance   = UserWallet::where('userId', $userId)->value('amount');
                $astrologer     = AiAstrologer::where('type','master')->first();
                
                $userDetails    = User::where('id', $userId)->first();
                
                if ($userDetails->name === null || $userDetails->birthDate === null || $userDetails->birthPlace === null) {
                    return response()->json([
                        'message' => 'Please update your profile.',
                        'status' => 400,
                        'statusName' => 'updateProfile',
                        'recordList' => $userDetails
                    ], 400);
                }else{
                    if(!$astrologer || $astrologer->chat_charge == null){
                        return response()->json([
                            'message'   => 'This chat is free.',
                            'status' => 200,
                            'statusName' => 'chatFree'
                        ],200);
                    }else{
                        if ($user_balance < $astrologer->chat_charge) {
                            return response()->json([
                                'message'   => 'Balance too low! Please top up your wallet to proceed.',
                                'status' => 400,
                                 'statusName' => 'lowBalance'
                            ],400);
                        } else {
                            return response()->json([
                                'balance'   => $astrologer->chat_charge,
                                'message'   => 'This chat is charged at ' . $currency['value'] . '' . $astrologer->chat_charge . ' per minute. Ready to continue?',
                                'status' => 200,
                                 'statusName' => 'readyForChat'
        
                            ],200);
                        }
                    }
                }
            } else {
                return response()->json([
                    'status'    => '403',
                    'message'   => 'You must be logged in to view this page. Please log in to continue.',
                    'statusName' => 'unauthorized'
                ],403);
            }
        }
        
        public function masterChatPageApi()
        {
           if (Auth::guard('api')->user()) {
                $userId         = Auth::guard('api')->user()->id;
                $currencyRow = DB::table('systemflag')->where('name', 'currencySymbol')->first();
            $currency = $currencyRow ? ['name' => $currencyRow->name, 'value' => $currencyRow->value] : ['name' => 'currencySymbol', 'value' => '₹'];
                    
                    $user_balance   = UserWallet::where('userId', $userId)->value('amount');
                    $astrologer     = AiAstrologer::where('type','master')->first();
                    $questions      = [
                        'मेरे राशि चिह्न के अनुसार मेरी सबसे बड़ी ताकतें और कमजोरियाँ क्या हैं?',
                        'मेरे राशि चिह्न के अनुसार मेरे व्यक्तित्व में कौन-सी अनोखी विशेषताएँ होती हैं?',
                        'मेरे लिए सबसे अनुकूल करियर कौन-सा रहेगा?',
                        'मेरे राशि चिह्न के अनुसार मुझे अपने प्रेम संबंधों में किन चीजों पर ध्यान देना चाहिए?',
                        'मेरी राशि के अनुसार मेरे लिए कौन-सी स्वास्थ्य आदतें लाभदायक होंगी?',
                        'किस तरह की आध्यात्मिक या मानसिक प्रथाएँ मेरे राशि चिह्न के अनुसार मेरे लिए फायदेमंद होंगी?',
                        'आने वाले समय में मेरे राशि चिह्न के लिए कौन-से महत्वपूर्ण बदलाव या अवसर हो सकते हैं?',
                        'आने वाले ग्रह गोचर मेरे जीवन में कौन-से बदलाव ला सकते हैं?',
                        'मेरे राशि चिह्न के अनुसार मैं अपने व्यक्तिगत विकास में कैसे सुधार कर सकता हूँ?',
                        'मुझे अपने करियर में सफलता पाने के लिए किन कदमों को उठाना चाहिए?',
                        'मेरे राशि चिह्न के हिसाब से क्या मेरी विवाह या परिवार के जीवन में कोई बड़ा बदलाव हो सकता है?',
                        'मेरे जन्मकुंडली के अनुसार मुझे कौन-सी विशेष ग्रह स्थिति का सामना करना पड़ सकता है?',
                        'मेरी जन्म कुंडली में ग्रहों की स्थिति के आधार पर मुझे किस प्रकार के जीवनसाथी के साथ तालमेल रहेगा?',
                        'क्या मेरी जन्मकुंडली में कोई विशेष राजयोग या लाभ योग है?',
                        'क्या मेरे जीवन में किसी ग्रह की शांति के लिए उपायों की आवश्यकता है?',
                        'मेरी जन्म कुंडली के अनुसार, मुझे किन ग्रहों के मंत्र जाप या तंत्र प्रयोग करने चाहिए?',
                        'मेरे ग्रहों की स्थिति के आधार पर मुझे कौन-सी पूजा या अनुष्ठान करने चाहिए?',
                        'मेरी कुंडली के अनुसार, मेरे लिए कौन-सी राशि का जीवनसाथी सबसे उपयुक्त रहेगा?',
                        'क्या मेरी कुंडली में कोई विवाह दोष (Manglik Dosha) है, और इसका समाधान क्या है?',
                        'क्या मेरी जन्मकुंडली में संतान सुख के लिए शुभ योग हैं?',
                        'मेरे ग्रहों के अनुसार विवाह के लिए कोई विशेष तारीख या समय कौन सा शुभ रहेगा?'
                    ];
                    return response()->json([
                        'user_balance'  => $user_balance,
                        'recordList'    => $astrologer,
                        'currency'      => $currency,
                        'status' => 200,
                    ], 200); 
                } else {
                    return response()->json([
                        'message' => 'Access Denied: You must be logged in to view this page. Please log in to continue.',
                        'status' => 403,
                        'statusName' => 'unauthorized'
                    ], 403); 
                }
            }
            
            public function storeMasterAiChatHistoryApi(Request $request){
                if (Auth::guard('api')->user()) {
                    $userId                     = Auth::guard('api')->user()->id;
                    $aiAstrologer               = AiAstrologer::find($request->astrologer_id);
                    // $user_country               = User::where('id', $userId)->where('country','India')->first();    // commented
                    $user_country               = User::where('id', $userId)->where('countryCode','+91')->first();    // added
                    
                    $inr_usd_conv_rate = DB::table('systemflag')->where('name','UsdtoInr')->select('value')->first();
                    $actualDurationInSeconds    = $request->chat_duration;
                    $minute                     = ceil($actualDurationInSeconds / 60);
                    // if($user_country){
                    //     $aiAstrologer->chat_charge=convertinrtousd($aiAstrologer->chat_charge);
                    // }
                    
                    $aiAstrologer->chat_charge = $user_country ? $aiAstrologer->chat_charge : convertusdtoinr($aiAstrologer->chat_charge);
                    $deductionAmount            = $minute * $aiAstrologer->chat_charge;
                    
                    // is free first time
                     $isFree                    = DB::table('ai_chat_histories')->where('user_id',$userId)->first();
                     
                     if(!empty($isFree)){
                    $aiChatHistory              = AiChatHistory::create([
                        'user_id'               => $userId,
                        'ai_astrologer_id'      => $request->astrologer_id,
                        'chat_duration'         => $request->chat_duration,
                        'chat_rate'             => $aiAstrologer->chat_charge,
                        'is_free'               => 0,
                        'deduction'             => $deductionAmount,
                        'created_at'            => now(),
                        'updated_at'            => now(),
                        'inr_usd_conversion_rate'=>$inr_usd_conv_rate->value,
                    ]);
                    
                    // 
                    $orderRequest = array(
                            'userId'                    => $userId,
                            'orderType'                 => 'aiChat',
                            'totalPayable'              => $deductionAmount,
                            'orderStatus'               => 'Complete',
                            "aiAstrologerId"            => $request->astrologer_id,
                            'totalMin'                  => round($request->chat_duration / 60),
                            'inr_usd_conversion_rate'   =>$inr_usd_conv_rate->value,

                        );
                        DB::Table('order_request')->insert($orderRequest);
                        $Orderid        = DB::getPdo()->lastInsertId();
                        $transaction    = array(
                            'userId'                    => $userId,
                            'amount'                    => $deductionAmount,
                            'isCredit'                  => false,
                            "transactionType"           => 'aiChat',
                            "orderId"                   => $Orderid,
                            "aiAstrologerId"            => $request->astrologer_id,
                            'created_at'                => Carbon::now(),
                            'updated_at'                => Carbon::now(),
                            'inr_usd_conversion_rate'   =>$inr_usd_conv_rate->value,
                        );
                        DB::table('wallettransaction')->insert($transaction);
                        // 
                    
                    $wallet_deduction = UserWallet::where('userId', $userId)->first();
                    if ($wallet_deduction) {
                        // $wallet_deduction->amount -= ($user_country) ? ($deductionAmount * $inr_usd_conv_rate->value) : $deductionAmount;
                        $wallet_deduction->amount -= $deductionAmount;
                        $wallet_deduction->save();
                    } else {
                        return response()->json([
                            'message' => 'Wallet not found.',
                            'status' => 404,
                        ], 404);
                    }
                    
                    // 
                }else{
                        $aiChatHistory = AiChatHistory::create([
                            'user_id'           => $userId,
                            'ai_astrologer_id'  => $request->astrologer_id,
                            'chat_duration'     => $request->chat_duration,
                            // 'chat_min'       => $request->chatMin,
                            'chat_rate'         => $aiAstrologer->chat_charge,
                            'is_free'           => 0,
                            // 'deduction'      => $deduction,
                            'created_at'        => now(),
                            'updated_at'        => now(),
                            'inr_usd_conversion_rate'=>$inr_usd_conv_rate->value,
                        ]);


                        $orderRequest = array(
                            'userId'                    => $userId,
                            'orderType'                 => 'aiChat',
                            'totalPayable'              => $deductionAmount,
                            'orderStatus'               => 'Complete',
                            "aiAstrologerId"            => $request->astrologer_id,
                            'totalMin'                  => round($request->actualDuration / 60),
                            'inr_usd_conversion_rate'   =>$inr_usd_conv_rate->value,

                        );
                        DB::Table('order_request')->insert($orderRequest);
                        $Orderid = DB::getPdo()->lastInsertId();
                        $transaction = array(
                            'userId'                    => $userId,
                            'amount'                    => $deductionAmount,
                            'isCredit'                  => false,
                            "transactionType"           => 'aiChat',
                            "orderId"                   => $Orderid,
                            "aiAstrologerId"            => $request->astrologer_id,
                            'created_at'                => Carbon::now(),
                            'updated_at'                => Carbon::now(),
                            'inr_usd_conversion_rate'   =>$inr_usd_conv_rate->value,
                        );
                        DB::table('wallettransaction')->insert($transaction);

                        $wallet_deduction = UserWallet::where('userId', $userId)->first();
                        if ($wallet_deduction) {
                            // $wallet_deduction->amount -= ($user_country) ? ($deductionAmount * $inr_usd_conv_rate->value) : $deductionAmount;
                            $wallet_deduction->amount -= $deductionAmount;
                            $wallet_deduction->save();
                        } else {
                            return response()->json(['error' => 'Wallet not found.'], 404);
                        }
                    }
                    // 
                    return response()->json([
                        'message' => 'Chat ended successfully!',
                        'status' => 200,
                        ],200);
                } else {
                    return response()->json([
                        'message' => 'Access Denied: You must be logged in to view this page. Please log in to continue.',
                        'status' => 403,
                        'statusName' => 'unauthorized'

                        ], 403);
                }
            }
        }
        