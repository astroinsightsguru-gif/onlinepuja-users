<?php

use App\Models\AdminModel\SystemFlag;
use App\Models\AstrologerModel\Astrologer;
use App\Models\UserModel\CallRequest;
use App\Models\UserModel\OrderAddress;
use App\Models\UserModel\User;
use App\Models\UserModel\UserWallet;
use App\Models\Puja;
use App\Models\PujaOrder;
use App\Models\Pujapackage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Session\Session;
use Tymon\JWTAuth\Facades\JWTAuth;

/*
 * Get Config data
 * @return array.
 * --------------------------------------------------------
 */

if (!function_exists('authcheck')) {
    function authcheck()
    {
        static $resolved = false;
        static $cachedUser = false;
        if ($resolved) {
            return $cachedUser;
        }

        try {
            $session = new Session();
            $token = $session->get('token');

            if ($token) {
                JWTAuth::setToken($token);
                $user = JWTAuth::authenticate();
                $cachedUser = isset($user) ? $user : false;
            }
        } catch (\Throwable $e) {
            $cachedUser = false;
        }

        $resolved = true;
        return $cachedUser;
    }
}

function astroauthcheck()
{
    static $resolved = false;
    static $cachedAstro = false;
    if ($resolved) {
        return $cachedAstro;
    }

    try {
        $session = new Session();
        $token = $session->get('astrotoken');

        if ($token) {
            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();

            $astrologer = DB::table('astrologers')
                ->where('astrologers.userId', '=', $user->id)
                ->select('astrologers.id as astrologerId')
                ->first();

            if (!empty($astrologer)) {
                $user->astrologerId = $astrologer->astrologerId;
            } else {
                $user->astrologerId = 0;
            }

            $cachedAstro = isset($user) ? $user : false;
        }
    } catch (\Throwable $e) {
        $cachedAstro = false;
    }

    $resolved = true;
    return $cachedAstro;
}

if (!function_exists('getConfig')) {
    function getConfig($item = null)
    {
        // print_r(PAY_PAGE_CONFIG);die;
        // try {
        // if (!@include_once(PAY_PAGE_CONFIG)) {
        //     throw new Exception('file does not exist');
        // } else {

        $paypagePayment = config('paypage-payment');

        $payments = $paypagePayment['techAppConfig']['payments']['gateway_configuration'];

        $paymentdata = DB::table('systemflag')
            ->join('flaggroup', 'flaggroup.id', '=', 'systemflag.flagGroupId')
            ->select('systemflag.*', 'flaggroup.flagGroupName', 'flaggroup.isActive as flaggroupisactive')
            ->where('flaggroup.parentFlagGroupId', 2)
            ->get();

        foreach ($paymentdata as $datas) {
            $provider = $datas->flagGroupName;
            $flaggroupisactive = $datas->flagGroupName;
            if (!isset($paypagePayment['techAppConfig']['payments']['gateway_configuration'][$provider])) {
                $paypagePayment['techAppConfig']['payments']['gateway_configuration'][$provider] = [];
            }

            // Assign the value to the appropriate key
            $paypagePayment['techAppConfig']['payments']['gateway_configuration'][$provider][$datas->name] = $datas->value;

            // Check if the current row contains currency, currency symbol, test mode, and isActive
            if ($datas->name === 'currency' . $datas->flagGroupId) {
                $paypagePayment['techAppConfig']['payments']['gateway_configuration'][$provider]['currency'] = $datas->value;
            } elseif ($datas->name === 'currencySymbol' . $datas->flagGroupId) {
                $paypagePayment['techAppConfig']['payments']['gateway_configuration'][$provider]['currencySymbol'] = $datas->value;
            } elseif ($datas->name === 'testMode' . $datas->flagGroupId) {
                $paypagePayment['techAppConfig']['payments']['gateway_configuration'][$provider]['testMode'] = filter_var($datas->value, FILTER_VALIDATE_BOOLEAN);
            } elseif ($datas->flaggroupisactive == 1) {
                $paypagePayment['techAppConfig']['payments']['gateway_configuration'][$provider]['enable'] = true;
            } elseif ($datas->flaggroupisactive == 0) {
                $paypagePayment['techAppConfig']['payments']['gateway_configuration'][$provider]['enable'] = false;
            }
        }

        return $paypagePayment;

        // }
        // } catch (\Exception $e) {
        //     throw new \Exception("PAY_PAGE_CONFIG - Missing config path constant", 1);
        // }
    }
}

/*
 * Get the technical items from tech items
 *
 *
 * @return mixed
 * --------------------------------------------------------
 */

if (!function_exists('configItem')) {
    function configItem()
    {
        $getConfig = getConfig();
        $getItem = $getConfig['techAppConfig'];
        return $getItem;
    }
}

/*
 * Get the technical items from tech items
 *
 *
 * @return mixed
 * --------------------------------------------------------
 */

if (!function_exists('configItemData')) {
    function configItemData($key, $default = null)
    {
        $getConfig = getConfig();
        $data = $getConfig['techAppConfig'];
        return getArrayItem($data, $key, $default);
    }
}

/*
 * Get the technical items from tech items
 *
 *
 * @return mixed
 * --------------------------------------------------------
 */

if (!function_exists('getPublicConfigItem')) {
    function getPublicConfigItem()
    {
        $getConfig = getConfig();
        $getItem = $getConfig['techAppConfig']['payments']['gateway_configuration'];

        foreach ($getItem as $itemKey => $item) {
            if (!empty($item['privateItems'])) {
                foreach ($item['privateItems'] as $privateItem) {
                    if (isset($getItem[$itemKey][$privateItem])) {
                        unset($getItem[$itemKey][$privateItem]);
                        unset($getItem[$itemKey]['privateItems']);
                    }
                }
            }
        }
        $configItem['payments']['gateway_configuration'] = $getItem;
        return $configItem;
    }
}

/*
 * Get the paytm merchant
 *
 * @param string   $paymentData
 *
 * @return mixed
 * --------------------------------------------------------
 */

if (!function_exists('getPaytmMerchantForm')) {
    function getPaytmMerchantForm($paymentData)
    {
        ob_start();
        echo view('payment.paytm-merchant-form', ['paymentData' => $paymentData]);
        $html_content = ob_get_contents();
        ob_end_clean();
        return $html_content;
    }
}

/*
 * Get the payU merchant
 *
 * @param string   $paymentData
 *
 * @return mixed
 * --------------------------------------------------------
 */
if (!function_exists('getPayUmoneyMerchantForm')) {
    function getPayUmoneyMerchantForm($paymentData)
    {
        ob_start();
        echo view('payment.payu-merchant-form', ['paymentData' => $paymentData]);
        $html_content = ob_get_contents();
        ob_end_clean();
        return $html_content;
    }
}

/*
 * Get App Url
 *
 * @param string   $paymentData
 *
 * @return mixed
 * --------------------------------------------------------
 */

if (!function_exists('getAppUrl')) {
    function getAppUrl($item = null, $path = '')
    {
        $configData = getConfig();
        $basePath = $configData['techAppConfig']['base_url'];

        if (!empty($item)) {
            return $basePath . $path . $item;
        } else {
            return $basePath . $path;
        }
    }
}

/**
 * Redirect using post
 *
 * @param  string // https://www.codexworld.com/how-to/get-user-ip-address-php/
 * @param  array postData data to post
 * --------------------------------------------------------
 */
if (!function_exists('getUserIpAddr')) {
    function getUserIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            // ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}

/*
 * Get the technical items from tech items
 *
 *
 * @return mixed
 * --------------------------------------------------------
 */

if (!function_exists('getArrayItem')) {
    function getArrayItem($array, $key, $default = null)
    {
        // @assert $key is a non-empty string
        // @assert $array is a loopable array
        // @otherwise return $default value
        if (!is_string($key) || empty($key) || !count($array)) {
            return $default;
        }

        // @assert $key contains a dot notated string
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);

            foreach ($keys as $innerKey) {
                // @assert $array[$innerKey] is available to continue
                // @otherwise return $default value
                if (!array_key_exists($innerKey, $array)) {
                    return $default;
                }

                $array = $array[$innerKey];
            }

            return $array;
        }

        // @fallback returning value of $key in $array or $default value
        return array_key_exists($key, $array) ? $array[$key] : $default;
    }
}

/**
 * Debugging function for debugging javascript side.
 *
 * @param  N numbers of params can be sent
 * --------------------------------------------------------
 */
if (!function_exists('__dd')) {
    function __dd()
    {
        $args = func_get_args();

        if (empty($args)) {
            throw new Exception('__dd() No arguments are passed!!');
        }

        $backtrace = debug_backtrace();

        if (isset($backtrace[0])) {
            $args['debug_backtrace'] = str_replace(__DIR__, '', $backtrace[0]['file']) . ':' . $backtrace[0]['line'];
        }

        echo '';
        // Editors Supported: "phpstorm", "vscode", "vscode-insiders","sublime", "atom"
        $editor = 'vscode';
        echo '</pre><br/><a style="background: lightcoral;font-family: monospace;padding: 4px 8px;border-radius: 4px;font-size: 12px;color: white;text-decoration: none;" href="' . $editor . '://file' . $backtrace[0]['file'] . ':' . $backtrace[0]['line'] . '">Open in Editor</a><br/><br/>';

        echo '<pre>';

        array_map(function ($argument) {
            print_r($argument, false);
            echo '<br/><br/>';
        }, $args);
        echo '</pre>';
        exit();
    }
}

/*
 * Debugging function for debugging javascript as well as PHP side, work as likely print_r but accepts unlimited parameters
 *
 * @param  N numbers of params can be sent
 * @return void
 * --------------------------------------------------------
 */

if (!function_exists('__pr')) {
    function __pr()
    {
        $args = func_get_args();

        if (empty($args)) {
            throw new Exception('__pr() No arguments are passed!!');
        }

        $backtrace = debug_backtrace();

        // print_r($backtrace);
        // exit();

        echo '';
        // Editors Supported: "phpstorm", "vscode", "vscode-insiders","sublime", "atom"
        $editor = 'vscode';
        echo '</pre><br/><a style="background: lightcoral;font-family: monospace;padding: 4px 8px;border-radius: 4px;font-size: 12px;color: white;text-decoration: none;" href="' . $editor . '://file' . $backtrace[0]['file'] . ':' . $backtrace[0]['line'] . '">' . $backtrace[0]['file'] . ':' . $backtrace[0]['line'] . '</a><br/><br/>';

        echo '<pre>';

        return array_map(function ($argument) {
            print_r($argument, false);
            echo '<br/><br/>';
        }, $args);
    }
}

if (!function_exists('__logDebug')) {
    /**
     * Log helper
     * Writes data in php log file
     * --------------------------------------------------------
     */
    function __logDebug()
    {
        $args = func_get_args();
        $backtrace = debug_backtrace();
        $log_file_path = dirname(__DIR__, 1) . '/php.log';
        $log_message = date('Y-m-d H:i:s') . "\n";
        array_map(function ($argument) use (&$log_message, $args) {
            $log_message .= print_r($argument, true) . "\n";
        }, $args);

        $log_message .= 'vscode://file/' . $backtrace[0]['file'] . ':' . $backtrace[0]['line'] . "\n";
        return file_put_contents($log_file_path, $log_message, FILE_APPEND);
    }
}

if (!function_exists('lw_current_func')) {
    function lw_current_func($item)
    {
        if (is_object($item)) {
            if (function_exists('get_mangled_object_vars')) {
                return current(get_mangled_object_vars($item));
            }
        }
        return current($item);
    }
}

function PlacePujaOrder($req, $user_id)
{
    // Initialize request data as an array
    $req = collect($req)->toArray();
    // dd($req);
    // Fetch necessary details from the database
    $pujaDetails = Puja::findOrFail($req['pujaId']);
    $pujaPackage = null;
    if (!empty($req['packageId'])) {
        $pujaPackage = Pujapackage::findOrFail($req['packageId']);
    }

    $orderAddress = OrderAddress::findOrFail($req['orderAddressId']);

    // $user_country=User::where('id',$user_id)->where('country','India')->first(); // commented
    $user_country = User::where('id', $user_id)->where('countryCode', '+91')->first();  // added
    $inr_usd_conv_rate = DB::table('systemflag')->where('name', 'UsdtoInr')->select('value')->first();

    $pujarefComm = DB::table('systemflag')->where('name', 'pujaRefCommission')->first();
    $pujarefComminrs = ($pujarefComm->value * $req['totalPayable']) / 100;

    if (isset($req['puja_recommend_id'])) {
        $puja_reccommend = DB::table('puja_recommends')->where('id', $req['puja_recommend_id'])->where('isPurchased', '0')->first();
    } else {
        $puja_reccommend = DB::table('puja_recommends')->where('userId', $user_id)->where('puja_id', $req['pujaId'])->where('recommDateTime', '>=', Carbon::now()->subDay())->where('isPurchased', '0')->latest()->first();
    }

    // dd($puja_reccommend);
    if ($puja_reccommend) {
        $astrologerId = $puja_reccommend->astrologerId;
        //  $astrologercountry=Astrologer::where('id', $astrologerId)->where('country','India')->first();   // commented
        $astrologercountry = Astrologer::where('id', $astrologerId)->where('countryCode', '+91')->first();  // added
        $astrologerUserId = DB::table('astrologers')
            ->where('id', '=', $astrologerId)
            ->selectRaw('userId,name')
            ->get();
        $astrologerWallet = DB::table('user_wallets')
            ->where('userId', '=', $astrologerUserId[0]->userId)
            ->get();

        $astrologerWalletData = array(
            // 'amount' => $astrologerWallet && count($astrologerWallet) > 0 ? $astrologerWallet[0]->amount + ($astrologercountry ? ($pujarefComminrs * $inr_usd_conv_rate->value) : $pujarefComminrs) : ($astrologercountry ? ($pujarefComminrs * $inr_usd_conv_rate->value) : $pujarefComminrs),
            'amount' => $astrologerWallet && count($astrologerWallet) > 0 ? $astrologerWallet[0]->amount + $pujarefComminrs : $pujarefComminrs,
            'userId' => $astrologerUserId[0]->userId,
            'createdBy' => $astrologerUserId[0]->userId,
            'modifiedBy' => $astrologerUserId[0]->userId,
        );

        if ($astrologerWallet && count($astrologerWallet) > 0) {
            DB::Table('user_wallets')
                ->where('userId', '=', $astrologerUserId[0]->userId)
                ->update($astrologerWalletData);
        } else {
            DB::Table('user_wallets')->insert($astrologerWalletData);
        }

        $astrologerWalletTransaction = array(
            'amount' => $pujarefComminrs,
            'userId' => $astrologerUserId[0]->userId,
            'createdBy' => $astrologerUserId[0]->userId,
            'modifiedBy' => $astrologerUserId[0]->userId,
            'isCredit' => true,
            'transactionType' => 'PujaRefCommission',
            'puja_recommend_id' => $puja_reccommend->id,
            'astrologerId' => $astrologerId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'inr_usd_conversion_rate' => $inr_usd_conv_rate->value,
        );
        DB::table('wallettransaction')->insert($astrologerWalletTransaction);

        DB::table('puja_recommends')
            ->where('id', $puja_reccommend->id)
            ->update(['isPurchased' => '1']);
    }

    $existingPuja = DB::table('user_pujarequest_by_astrologers')
        ->where('astrologerId', $req['astrologer_id'])
        ->where('userId', $user_id)
        ->where('puja_id', $req['pujaId'])
        ->first();

    if ($existingPuja) {
        DB::table('user_pujarequest_by_astrologers')
            ->where('id', $existingPuja->id)  // assuming there's an 'id' column
            ->delete();
    }

    // Prepare order data as an array
    $orderData = [
        'user_id' => $user_id,
        'astrologer_id' => $req['astrologer_id'],
        'puja_id' => $req['pujaId'],
        'puja_name' => $pujaDetails->puja_title,
        'package_id' => isset($req['packageId']) ? $req['packageId'] : 0,
        'package_name' => $pujaPackage ? $pujaPackage->title : null,
        'package_person' => $pujaPackage ? $pujaPackage->person : null,
        'puja_start_datetime' => $pujaDetails->puja_start_datetime,
        'puja_end_datetime' => $pujaDetails->puja_end_datetime,
        'address_id' => $req['orderAddressId'],
        'address_name' => $orderAddress->name,
        'address_number' => $orderAddress->phoneNumber,
        'address_flatno' => $orderAddress->flatNo,
        'address_locality' => $orderAddress->locality,
        'address_city' => $orderAddress->city,
        'address_state' => $orderAddress->state,
        'address_country' => $orderAddress->country,
        'address_pincode' => $orderAddress->pincode,
        'address_landmark' => $orderAddress->landmark,
        'order_price' => $req['payableAmount'],
        // 'order_gst_amount' => $req['gstPercent'],
        'order_total_price' => $req['totalPayable'],
        'payment_type' => $req['payment_type'],
        'puja_order_status' => 'placed',
        'inr_usd_conversion_rate' => $inr_usd_conv_rate->value,
        'puja_duration' => $pujaDetails->puja_duration,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now()
    ];

    // Create order and return the result
    $order = PujaOrder::create($orderData);

    $userDeviceDetail = DB::table('user_device_details as device')
        ->JOIN('astrologers', 'astrologers.userId', '=', 'device.userId')
        ->WHERE('astrologers.id', '=', $req['astrologer_id'])
        ->SELECT('device.*', 'astrologers.userId as astrologerUserId', 'astrologers.name')
        ->get();

    if ($userDeviceDetail && count($userDeviceDetail) > 0) {
        // One signal FOr notification send
        $oneSignalService = new \App\services\OneSignalService();
        //    $userPlayerIds = $userDeviceDetail->pluck('subscription_id')->all();
        $userPlayerIds = $userDeviceDetail->pluck('subscription_id')->merge($userDeviceDetail->pluck('subscription_id_web'))->values()->toArray();
        $notification = [
            'title' => 'Hey ' . $userDeviceDetail[0]->name . ', puja has been assigned to you',
            'body' => ['description' => 'Hey ' . $userDeviceDetail[0]->name . ', puja has been assigned to you'],
        ];
        // Send the push notification using the OneSignalService
        $response = $oneSignalService->sendNotification($userPlayerIds, $notification);

        $notification = array(
            'userId' => $userDeviceDetail[0]->astrologerUserId,
            'title' => 'Hey ' . $userDeviceDetail[0]->name . ', puja has been assigned to you',
            // 'description' => 'It seems like you have missed/rejected your chat from ' . $astrologer[0]->name . ' .You may initiate it again from the app.',
            'description' => 'Hey ' . $userDeviceDetail[0]->name . ', puja has been assigned to you',
            'notificationId' => null,
            'createdBy' => $userDeviceDetail[0]->astrologerUserId,
            'modifiedBy' => $userDeviceDetail[0]->astrologerUserId,
            'notification_type' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        );
        DB::table('user_notifications')->insert($notification);
    }

    return $order;
}

// function for convert the value in encrypt mode
function encrypt_to($value = NULL, $type = NULL)
{
    $new_value = trim($value);
    $value = encryptvalue($new_value);
    return trim($value);
}

// function for convert the value in decrypt mode
function decrypt_to($value = NULL, $type = NULL)
{
    if (empty($value))
        return NULL;

    $new_value = decryptvalue($value);
    return trim($new_value);
}

// function for encrypt the passing paramter
function encryptvalue($string = NULL)
{
    $key = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    $result = '';
    for ($i = 0; $i < strlen($string); $i++) {
        $char = substr($string, $i, 1);
        $keychar = substr($key, ($i % strlen($key)) - 1, 1);
        $char = chr(ord($char) + ord($keychar));
        $result .= $char;
    }
    return urlencode(base64_encode($result));
}

// function for decrypt the passing paramter
function decryptvalue($string = NULL)
{
    $key = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    $result = '';
    $string = base64_decode(urldecode($string));
    for ($i = 0; $i < strlen($string); $i++) {
        $char = substr($string, $i, 1);
        $keychar = substr($key, ($i % strlen($key)) - 1, 1);
        $char = chr(ord($char) - ord($keychar));
        $result .= $char;
    }
    return $result;
}

/*
 * function convertinrtousd($amount= NULL)
 * {
 *     $conversion=SystemFlag::where('name','UsdtoInr')->first();
 *     if (!empty($conversion)) {
 *         $inr_to_usd = $conversion->value;
 *         $usd_amount = $amount / $inr_to_usd;
 *         return $usd_amount;
 *     }
 * }
 */

// updated by bhushan borse on 03 june 2025 -----
function convertinrtousd($amount = NULL, $conversion = NULL)
{
    if (is_null($conversion) || $conversion <= 0) {
        $conversion = SystemFlag::where('name', 'UsdtoInr')->first();
        if (!empty($conversion)) {
            $inr_to_usd = $conversion->value;
            $usd_amount = $amount / $inr_to_usd;
            return $usd_amount;
        }
    } else {
        $usd_amount = $amount / $conversion;
        return $usd_amount;
    }
}

// -----

/*
 * function convertusdtoinr($amount= NULL,$conversion=0)
 * {
 *     $conversion=SystemFlag::where('name','UsdtoInr')->first();
 *     if (!empty($conversion)) {
 *         $inr_to_usd = $conversion->value;
 *         $inr_amount = $amount * $inr_to_usd;
 *         return $inr_amount;
 *     }
 * }
 */

// updated by bhushan borse on 03 june 2025 -----
function convertusdtoinr($amount = NULL, $conversion = 0)
{
    if (is_null($conversion) || $conversion <= 0) {
        $conversion = SystemFlag::where('name', 'UsdtoInr')->first();
        if (!empty($conversion)) {
            $inr_to_usd = $conversion->value;
            $inr_amount = $amount * $inr_to_usd;
            return $inr_amount;
        }
    } else {
        $inr_amount = $amount * $conversion;
        return $inr_amount;
    }
}

function numberToCharacterString($number)
{
    $numberStr = strval($number);
    $result = '';
    for ($i = 0; $i < strlen($numberStr); $i++) {
        $digit = intval($numberStr[$i]);
        if ($digit >= 0 && $digit <= 26) {
            // Assuming you want to convert 1 to 'A', 2 to 'B', and so on
            $result .= chr(ord('A') + $digit);
        }
    }
    return $result;
}

function systemflag($name)
{
    static $memoryFlags = null;
    if ($memoryFlags === null) {
        $memoryFlags = cache()->remember('all_system_flags_kv', 86400, function () {
            try {
                return DB::table('systemflag')->pluck('value', 'name')->toArray();
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    if (isset($memoryFlags[$name])) {
        return $memoryFlags[$name];
    }

    return false;
}

function hmsGenerateManagementToken(): string
{
    $appAccessKey = systemflag('hmsAccessKey');
    $appSecret = systemflag('hmsSecretKey');

    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];

    $currentTime = time();
    $payload = [
        'access_key' => $appAccessKey,
        'type' => 'management',
        'version' => 2,
        'iat' => $currentTime,
        'nbf' => $currentTime,
        'exp' => $currentTime + 86400,  // 24 hours expiry
        'jti' => Str::uuid()->toString()  // Required JWT ID
    ];

    $headerEncoded = hmsbase64url_encode(json_encode($header));
    $payloadEncoded = hmsbase64url_encode(json_encode($payload));

    $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $appSecret, true);
    $signatureEncoded = hmsbase64url_encode($signature);

    return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
}

function hmsbase64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

if (!function_exists('flagGroup')) {
    function flagGroup($name)
    {
        $value = DB::table('flaggroup')->where('flagGroupName', $name)->value('isActive');
        return $value == 1;
    }
}

if (!function_exists('getCallMethod')) {
    function getCallMethod()
    {
        $callMethodsArr = [
            'agora' => flagGroup('Agora'),
            'zegocloud' => flagGroup('Zegocloud'),
            'hms' => flagGroup('100MS'),
        ];

        $filtered = collect($callMethodsArr)->filter(fn($value) => $value === true);

        // Check if we have any active methods
        if ($filtered->isEmpty()) {
            throw new \Exception('No active call methods available');
        }

        // If only one method is active, return it directly
        if ($filtered->count() === 1) {
            return strtolower($filtered->keys()->first());
        }

        // If multiple methods are active, try to avoid the last used one
        $lastCallMethod = CallRequest::orderBy('id', 'desc')->value('call_method');

        if ($lastCallMethod && $filtered->has($lastCallMethod)) {
            $filtered = $filtered->forget($lastCallMethod);
        }

        $call_method = strtolower($filtered->keys()->random());

        return $call_method;
    }
}

if (!function_exists('convertinrtocoin')) {
    function convertinrtocoin($amount = NULL, $conversion = NULL)
    {
        if (is_null($conversion) || $conversion <= 0) {
            $conversion = SystemFlag::where('name', 'InrToCoin')->select('value')->first();
            if (!empty($conversion)) {
                $inr_to_coin = $conversion->value;
                $coin = $amount * $inr_to_coin;
                return round($coin);
            }
        }
    }
}
if (!function_exists('convertusdtocoin')) {
    function convertusdtocoin($amount = NULL, $conversion = NULL)
    {
        if (is_null($conversion) || $conversion <= 0) {
            $conversion = SystemFlag::where('name', 'UsdToCoin')->select('value')->first();
            if (!empty($conversion)) {
                $inr_to_coin = $conversion->value;
                $coin = $amount * $inr_to_coin;
                return round($coin);
            }
        }
    }
}

if (!function_exists('getProfessionTitle')) {
    /**
     * Get profession title from cache or database
     *
     * @return string
     */
    function getProfessionTitle()
    {
        return cache()->remember('system_flag_professionTitle', 3600, function () {
            if (Schema::hasTable('systemflag')) {
                $result = DB::table('systemflag')
                    ->where('name', 'professionTitle')
                    ->select('value')
                    ->first();
                return $result ? $result->value : 'Partner';
            }
            return 'Partner';
        });
    }
}

if (!function_exists('getAppName')) {
    /**
     * Get app name from cache or database
     *
     * @return string
     */
    function getAppName()
    {
        return cache()->remember('system_flag_AppName', 3600, function () {
            if (Schema::hasTable('systemflag')) {
                $result = DB::table('systemflag')
                    ->where('name', 'AppName')
                    ->select('value')
                    ->first();
                return $result ? $result->value : 'Online Puja';
            }
            return 'Online Puja';
        });
    }
}
