<?php

namespace App\Http\Controllers\frontend\AiAstrologer;

use App\Http\Controllers\Controller;
use App\Models\AiAstrologerModel\AiAstrologer;
use App\Models\AiAstrologerModel\AiChatHistory;
use App\Models\UserModel\UserWallet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Session\Session;
use Auth;
use DB;
use PDF;
use Response;
use Validator;
if (!defined('LOGINPATH')) define('LOGINPATH', '/admin/login');

class MasterAiChatBotController extends Controller
{
    public $user;
    public $limit = 15;
    public $paginationStart;
    public $path;

    public function index(Request $req)
    {
        try {
            if (Auth::guard('web')->check()) {
                $page = $req->page ? $req->page : 1;
                $paginationStart = ($page - 1) * $this->limit;

                $currencyRow = DB::table('systemflag')->where('name', 'currencySymbol')->first();
                    $currency = $currencyRow ? ['name' => $currencyRow->name, 'value' => $currencyRow->value] : ['name' => 'currencySymbol', 'value' => '₹'];
                $searchString = $req->input('searchString', '');

                $aiAstrologers = AiAstrologer::query()
                    ->when($searchString, function ($query, $searchString) {
                        return $query->where(function ($query) use ($searchString) {
                            $query
                                ->where('name', 'like', '%' . $searchString . '%')
                                ->orWhere('about', 'like', '%' . $searchString . '%');
                        });
                    })
                    ->where('type', 'master')
                    ->orderBy('id', 'DESC')
                    ->get();

                $totalRecords = $aiAstrologers->count();
                // Calculate total pages
                $totalPages = ceil($totalRecords / $this->limit);
                // Adjust page number if it exceeds total pages
                $page = min($page, $totalPages);
                //  $aiAstrologers = $aiAstrologers->skip($paginationStart)->take($this->limit)->get();
                $aiAstrologers = $aiAstrologers->forPage($page, $this->limit);
                // Calculate start and end records for the current page
                $start = ($this->limit * ($page - 1)) + 1;
                $end = min($this->limit * $page, $totalRecords);

                return view('pages.ai-astrologer.master-ai-bot-list', compact('aiAstrologers', 'searchString', 'currency', 'totalPages', 'totalRecords', 'start', 'end', 'page'));
            } else {
                return redirect(LOGINPATH);
                // return redirect('/');
            }
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function create()
    {
        return view('pages.ai-astrologer.master-ai-bot-create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg',
            'chat_charge' => 'nullable|numeric|min:0',
            'chat_charge_usd' => 'nullable|numeric|min:0',
            'system_intruction' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $logoPath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $extension = time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('assets/images/aiastrologer-profile'), $extension);
                $logoPath = 'public/assets/images/aiastrologer-profile/' . $extension;
            }

            $astrologer = AiAstrologer::create([
                'name' => $request->name,
                'image' => $logoPath,
                'chat_charge' => $request->chat_charge,
                'chat_charge_usd' => $request->chat_charge_usd,
                'system_intruction' => $request->system_intruction,
                'type' => 'master',
            ]);
            DB::commit();
            return response()->json(['success' => 'Master AI chat bot added successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    public function edit($slug)
    {
        $aiAstro = AiAstrologer::where('slug', $slug)->first();
        return view('pages.ai-astrologer.master-ai-bot-edit', compact('aiAstro'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'chat_charge' => 'nullable|numeric|min:0',
            'chat_charge_usd' => 'nullable|numeric|min:0',
            'system_intruction' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $astrologer = AiAstrologer::findOrFail($id);

            if ($request->hasFile('image')) {
                if ($astrologer->image) {
                    $oldImagePath = public_path($astrologer->image);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                $file = $request->file('image');
                $extension = time() . '.' . $file->getClientOriginalExtension();
                $imagePath = 'public/assets/images/aiastrologer-profile/' . $extension;
                $file->move(public_path('assets/images/aiastrologer-profile'), $extension);
                $astrologer->image = $imagePath;
            }

            $astrologer->name = $request->name;
            $astrologer->chat_charge = $request->chat_charge;
            $astrologer->chat_charge_usd = $request->chat_charge_usd;
            $astrologer->system_intruction = $request->system_intruction;

            $astrologer->save();

            DB::commit();
            return response()->json(['success' => 'Master AI chat bot updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $astrologer = AiAstrologer::findOrFail($id);

            if ($astrologer->image) {
                $oldImagePath = public_path($astrologer->image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $astrologer->delete();

            return response()->json(['success' => 'Master AI chat bot deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    public function checkUserBalance()
    {
        if (authcheck()) {
            $userId = authcheck()['id'];
            $currencyRow = DB::table('systemflag')->where('name', 'currencySymbol')->first();
                    $currency = $currencyRow ? ['name' => $currencyRow->name, 'value' => $currencyRow->value] : ['name' => 'currencySymbol', 'value' => '₹'];

            $user_balance = UserWallet::where('userId', $userId)->value('amount');
            $astrologer = AiAstrologer::where('type', 'master')->first();

            if ($user_balance < $astrologer->chat_charge) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Balance too low! Please top up your wallet to proceed.'
                ]);
            } else {
                return response()->json([
                    'status' => 'success',
                    'balance' => $astrologer->chat_charge,
                    'message' => 'This chat is charged at ' . $currency['value'] . '' . $astrologer->chat_charge . ' per minute. Ready to continue?'
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'You must be logged in to view this page. Please log in to continue.'
            ]);
        }
    }

    public function masterChatPage()
    {
        if (authcheck()) {
            $userId = authcheck()['id'];
            $currencyRow = DB::table('systemflag')->where('name', 'currencySymbol')->first();
                    $currency = $currencyRow ? ['name' => $currencyRow->name, 'value' => $currencyRow->value] : ['name' => 'currencySymbol', 'value' => '₹'];

            $user_balance = UserWallet::where('userId', $userId)->value('amount');
            $astrologer = AiAstrologer::where('type', 'master')->first();
            $questions = [
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
            return view('pages.ai-astrologer.master-chat-page', compact('user_balance', 'astrologer', 'currency', 'questions'));
        } else {
            return response()->json(['warning' => 'Access Denied: You must be logged in to view this page. Please log in to continue.'], 403);
        }
    }

    public function storeMasterAiChatHistory(Request $request)
    {
        if (authcheck()) {
            $aiAstrologer = AiAstrologer::find($request->astrologer_id);
            $user_country = User::where('id', authcheck()['id'])->where('countryCode', '+91')->first();

            $inr_usd_conv_rate = DB::table('systemflag')->where('name', 'UsdtoInr')->select('value')->first();
            $actualDurationInSeconds = $request->timeDuraction;
            $minute = ceil($actualDurationInSeconds / 60);

            // Store original INR rate before conversion
            $chargeRate = $aiAstrologer->chat_charge;

            if (!$user_country) {
                // Convert INR charge to USD for deduction
                $aiAstrologer->chat_charge = convertinrtousd($aiAstrologer->chat_charge);
            }

            $deductionAmount = $minute * $aiAstrologer->chat_charge;

            $aiChatHistory = AiChatHistory::create([
                'user_id' => authcheck()['id'],
                'ai_astrologer_id' => $request->astrologer_id,
                'chat_duration' => $request->timeDuraction,
                'chat_rate' => $aiAstrologer->chat_charge,
                'is_free' => 0,
                'deduction' => $deductionAmount,
                'created_at' => now(),
                'updated_at' => now(),
                'inr_usd_conversion_rate' => $inr_usd_conv_rate->value,
            ]);

            $orderRequest = [
                'userId' => authcheck()['id'],
                'orderType' => 'aiChat',
                'totalPayable' => $deductionAmount,
                'orderStatus' => 'Complete',
                'aiAstrologerId' => $request->astrologer_id,
                'totalMin' => $minute,
                'inr_usd_conversion_rate' => $inr_usd_conv_rate->value,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            DB::table('order_request')->insert($orderRequest);
            $Orderid = DB::getPdo()->lastInsertId();

            $transaction = [
                'userId' => authcheck()['id'],
                'amount' => $deductionAmount,
                'isCredit' => false,
                'transactionType' => 'aiChat',
                'orderId' => $Orderid,
                'aiAstrologerId' => $request->astrologer_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'inr_usd_conversion_rate' => $inr_usd_conv_rate->value,
            ];
            DB::table('wallettransaction')->insert($transaction);

            $wallet_deduction = UserWallet::where('userId', authcheck()['id'])->first();

            if ($wallet_deduction) {
                // deductionAmount is already in USD (converted above for Indian users)
                // So deduct it directly — no further conversion needed
                $wallet_deduction->amount -= $deductionAmount;
                $wallet_deduction->save();
            } else {
                return response()->json(['error' => 'Wallet not found.'], 404);
            }

            return response()->json(['message' => 'Chat ended successfully!']);
        } else {
            return response()->json(['warning' => 'Access Denied: You must be logged in to view this page. Please log in to continue.'], 403);
        }
    }
}
