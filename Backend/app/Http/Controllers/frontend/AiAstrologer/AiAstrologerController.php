<?php

namespace App\Http\Controllers\frontend\AiAstrologer;

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

use App\Models\User;
use App\Models\UserModel\UserRole;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PDF;
use Response;
if (!defined('LOGINPATH')) define('LOGINPATH', '/admin/login');

class AiAstrologerController extends Controller
{
    public $user;
    public $limit = 15;
    public $paginationStart;
    public $path;


    // public function index(Request $req){

    //     try {
    //         if (Auth::guard('web')->check()) {
    //             $aiAstrologers                      = AiAstrologer::all()->map(function($astro) {
    //                 $astro->primary_skills_names    = $astro->primarySkills()->pluck('name');
    //                 $astro->all_skills_names        = $astro->allSkills()->pluck('name');
    //                 $astro->categories_names        = $astro->astrologerCategories()->pluck('name');
    //                 return $astro;
    //             });
    //             return view('pages.ai-astrologer.ai-astrologer-list', compact('aiAstrologers'));
    //         } else {
    //             return redirect('/');
    //         }
    //     } catch (Exception $e) {
    //         return dd($e->getMessage());
    //     }
    // }
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
                            $query->where('name', 'like', '%' . $searchString . '%')
                            ->orWhere('about', 'like', '%' . $searchString . '%');
                        });
                    })
                    ->whereNull('type')
                    ->orderBy('id','DESC')
                    ->get()
                    ->map(function ($astro) {
                        $astro->primary_skills_names = $astro->primarySkills()->pluck('name');
                        $astro->all_skills_names = $astro->allSkills()->pluck('name');
                        $astro->categories_names = $astro->astrologerCategories()->pluck('name');
                        return $astro;
                    });

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

                    return view('pages.ai-astrologer.ai-astrologer-list', compact('aiAstrologers', 'searchString','currency', 'totalPages', 'totalRecords', 'start', 'end', 'page'));
                } else {
                    return redirect('/');
                }
            } catch (Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        public function create(){
            $skills             =Skill::all();
            $astrologerCategory = AstrologerCategory::all();
            return view('pages.ai-astrologer.ai-astrologer-create', compact('skills','astrologerCategory'));
        }

        public function store(Request $request){

            $validator = Validator::make($request->all(), [
                'name'                  => 'required|string|max:255',
                'about'                 => 'required|string',
                'image'                 => 'required|image|mimes:jpeg,png,jpg',
                'astrologerCategoryId'  => 'required',
                'primary_skill'         => 'required',
                'all_skills'            => 'required',
                'chat_charge'           => 'required|numeric|min:0',
                'experience'            => 'required|numeric|min:0',
                'system_intruction'     => 'required',
            ]);

            // if ($validator->fails()) {
            //     return response()->json(['error' => $validator->errors()->all()], 422);
            // }
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }


            DB::beginTransaction();

            try {

                $logoPath = null;
                if ($request->hasFile('image')) {
                    $file                   = $request->file('image');
                    $extension              = time() . '.' . $file->getClientOriginalExtension();
                    $file                   ->move(public_path('assets/images/aiastrologer-profile'), $extension);
                    $logoPath               = 'public/assets/images/aiastrologer-profile/' . $extension;
                }
                $astrologerCategoryId       = $request->input('astrologerCategoryId', []);
                $primary_skill              = $request->input('primary_skill', []);
                $all_skills                 = $request->input('all_skills', []);

                $astrologer                 = AiAstrologer::create([
                    'name'                  => $request->name,
                    'about'                 => $request->about,
                    'image'                 => $logoPath,
                    'astrologerCategoryId'  => implode(',', $astrologerCategoryId),
                    'primary_skill'         => implode(',', $primary_skill),
                    'all_skills'            => implode(',', $all_skills),
                    'chat_charge'           => $request->chat_charge,
                    'chat_charge_usd'       => $request->chat_charge_usd,
                    'experience'            => $request->experience,
                    'system_intruction'     => $request->system_intruction,
                ]);
                DB::commit();
                return response()->json(['success' => 'Astrologer added successfully.']);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
            }
        }

        public function edit($slug){
            $skills             =Skill::all();
            $astrologerCategory = AstrologerCategory::all();
            $aiAstro            = AiAstrologer::where('slug',$slug)->first();
            return view('pages.ai-astrologer.ai-astrologer-edit', compact('skills','astrologerCategory','aiAstro'));
        }

        public function update(Request $request, $id) {
            $validator = Validator::make($request->all(), [
                'name'                  => 'required|string|max:255',
                'about'                 => 'required|string',
                'image'                 => 'nullable|image|mimes:jpeg,png,jpg',
                'astrologerCategoryId'  => 'required|array',
                'primary_skill'         => 'required|array',
                'all_skills'            => 'required|array',
                'chat_charge'           => 'required|numeric|min:0',
                'chat_charge_usd'       => 'required|numeric|min:0',
                'experience'            => 'required|numeric|min:0',
                'system_intruction'     => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            // if ($validator->fails()) {
            //     return response()->json(['error' => $validator->errors()->all()], 422);
            // }

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

                $astrologer->name                   = $request->name;
                $astrologer->about                  = $request->about;
                $astrologer->astrologerCategoryId   = implode(',', $request->astrologerCategoryId);
                $astrologer->primary_skill          = implode(',', $request->primary_skill);
                $astrologer->all_skills             = implode(',', $request->all_skills);
                $astrologer->chat_charge            = $request->chat_charge;
                $astrologer->chat_charge_usd            = $request->chat_charge_usd;
                $astrologer->experience             = $request->experience;
                $astrologer->system_intruction      = $request->system_intruction;

                $astrologer->save();

                DB::commit();
                return response()->json(['success' => 'Astrologer updated successfully.']);
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

                return response()->json(['success' => 'Astrologer deleted successfully.']);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
            }
        }

        public function aiAstrologerList(){
            try {
                $userId='';
                if(authcheck()){
                    $userId=authcheck()['id'];
                }
                $currencyRow = DB::table('systemflag')->where('name', 'currencySymbol')->first();
                    $currency = $currencyRow ? ['name' => $currencyRow->name, 'value' => $currencyRow->value] : ['name' => 'currencySymbol', 'value' => '₹'];


                    // if (authcheck()) {
                    $aiAstrologers = AiAstrologer::orderBy('id', 'desc')
                    ->whereNull('type')
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
                    return view('pages.ai-astrologer.ai-astrologer-chat-list', compact('aiAstrologers','currency','isFreeAvailable','wallet_amount'));
                    // } else {
                    //     return response()->json(['warning' => 'Access Denied: You must be logged in to view this page. Please log in to continue.'], 403);

                    // }
                } catch (Exception $e) {
                    return redirect()->back()->with('error', $e->getMessage());
                }
            }

            public function aiChatButton(Request $request){
                $astrologerId   = $request->astrologerId;
                $charge         = $request->charge;
                $chat_duration  = $request->chat_duration;

                $userName       = authcheck()['name'];
                $userId         = authcheck()['id'];

                $firstFreerecharge  = DB::table('systemflag')->where('name', 'FirstFreeChatRecharge')->select('value')->first();
                $minAmount          = DB::table('systemflag')->where('name', 'MinAmountFreeChatCall')->select('value')->first();
                $wallets            = DB::table('user_wallets')->where('userId', $userId)->first();
                $walletAmount       = $wallets ? (float) $wallets->amount : 0;

                $isChatRequest      = DB::table('ai_chat_histories')->where('user_id', $userId)->first();

                if($isChatRequest == null){
                    return response()->json([
                        'success'       => 'Please wait for a second!',
                        'userId'        =>$userId,
                        'astrologerId'  =>$astrologerId,
                        'charge'        =>$charge,
                        'chat_duration' =>$chat_duration,
                        'userName'      =>$userName,
                        'status'        => 200,
                    ], 200);
                }else{
                    $charge         = $request->charge;
                    $chatDuration   = $request->chat_duration/60;
                    $needAmount     = $charge * $chatDuration;

                    if ($needAmount > $walletAmount) {
                        return response()->json([
                            'warning'       => 'Balance too low! Please top up your wallet to proceed.',
                            'needAmount'    =>(int)$needAmount,
                            'userBalance'   =>$walletAmount,
                            'status'        => 400,
                        ], 200);
                    }else{
                        return response()->json([
                            'success'       => 'Please wait for a second!',
                            'userId'        =>$userId,
                            'astrologerId'  =>$astrologerId,
                            'charge'        =>$charge,
                            'chat_duration' =>$chat_duration,
                            'userName'      =>$userName,
                            'status'        => 200,
                        ], 200);
                    }
                }
                return response()->json(['error' => 'Something went wrong'], 500);
            }


            public function aiChattingPage(Request $request)
            {
                if (authcheck()) {

                    $astrologerId   = $request->query('astrologerId');
                    $charge         = $request->query('charge');
                    $chatDuration   = $request->query('chat_duration')/60;
                    $astrologer     = AiAstrologer::where('id', $astrologerId)->first();

                    return view('pages.ai-astrologer.ai-astrologer-chat-page', compact('astrologerId', 'charge', 'chatDuration', 'astrologer'));
                } else {
                    return response()->json(['warning' => 'Access Denied: You must be logged in to view this page. Please log in to continue.'], 403);

                }
            }

            public function storeAiChatHistory(Request $request){
                if (authcheck()) {
                    $aiAstrologer               = AiAstrologer::find($request->astrologer_id);

                    $user_country=User::where('id',authcheck()['id'])->where('country','India')->first();
                    $inr_usd_conv_rate = DB::table('systemflag')->where('name','UsdtoInr')->select('value')->first();
                    $actualDurationInSeconds    = $request->actualDuration;
                    if($user_country){
                        $aiAstrologer->chat_charge=convertinrtousd($aiAstrologer->chat_charge);
                    }

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


                        $orderRequest = array(
                            'userId' => authcheck()['id'],
                            'orderType' => 'chat',
                            'totalPayable' => $deductionAmount,
                            'orderStatus' => 'Complete',
                            "aiAstrologerId" => $request->astrologer_id,
                            'totalMin' => round($request->actualDuration / 60),
                            'inr_usd_conversion_rate'=>$inr_usd_conv_rate->value,

                        );
                        DB::Table('order_request')->insert($orderRequest);
                        $Orderid = DB::getPdo()->lastInsertId();
                        $transaction = array(
                            'userId' => authcheck()['id'],
                            'amount' => $deduction,
                            'isCredit' => false,
                            "transactionType" => 'Chat',
                            "orderId" => $Orderid,
                            "aiAstrologerId" => $request->astrologer_id,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                            'inr_usd_conversion_rate'=>$inr_usd_conv_rate->value,
                        );
                        DB::table('wallettransaction')->insert($transaction);

                        $wallet_deduction = UserWallet::where('userId', authcheck()['id'])->first();
                        if ($wallet_deduction) {
                            $wallet_deduction->amount -= ($user_country) ? ($deductionAmount * $inr_usd_conv_rate->value) : $deductionAmount;
                            $wallet_deduction->save();
                        } else {
                            return response()->json(['error' => 'Wallet not found.'], 404);
                        }





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
                            'inr_usd_conversion_rate'=>$inr_usd_conv_rate->value,
                        ]);


                        $orderRequest = array(
                            'userId' => authcheck()['id'],
                            'orderType' => 'chat',
                            'totalPayable' => $deductionAmount,
                            'orderStatus' => 'Complete',
                            "aiAstrologerId" => $request->astrologer_id,
                            'totalMin' => round($request->actualDuration / 60),
                            'inr_usd_conversion_rate'=>$inr_usd_conv_rate->value,

                        );
                        DB::Table('order_request')->insert($orderRequest);
                        $Orderid = DB::getPdo()->lastInsertId();
                        $transaction = array(
                            'userId' => authcheck()['id'],
                            'amount' => $deductionAmount,
                            'isCredit' => false,
                            "transactionType" => 'Chat',
                            "orderId" => $Orderid,
                            "aiAstrologerId" => $request->astrologer_id,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                            'inr_usd_conversion_rate'=>$inr_usd_conv_rate->value,
                        );
                        DB::table('wallettransaction')->insert($transaction);

                        $wallet_deduction = UserWallet::where('userId', authcheck()['id'])->first();
                        if ($wallet_deduction) {
                            $wallet_deduction->amount -= ($user_country) ? ($deductionAmount * $inr_usd_conv_rate->value) : $deductionAmount;
                            $wallet_deduction->save();
                        } else {
                            return response()->json(['error' => 'Wallet not found.'], 404);
                        }
                    }
                    return response()->json(['message' => 'Chat ended successfully!']);
                } else {
                    return response()->json(['warning' => 'Access Denied: You must be logged in to view this page. Please log in to continue.'], 403);
                }
            }

        }
