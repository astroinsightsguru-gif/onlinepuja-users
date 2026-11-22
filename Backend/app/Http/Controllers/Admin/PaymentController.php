<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserModel\Payment;
use App\Models\UserModel\UserWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Session\Session;
// for payment Response
use App\Components\Payment\CoingateResponse;
use App\Components\Payment\InstamojoResponse;
use App\Components\Payment\IyzicoResponse;
use App\Components\Payment\MercadopagoResponse;
use App\Components\Payment\MollieResponse;
use App\Components\Payment\PagseguroResponse;
use App\Components\Payment\PaypalIpnResponse;
use App\Components\Payment\PaystackResponse;
use App\Components\Payment\PaytmResponse;
use App\Components\Payment\PayUmoneyResponse;
use App\Components\Payment\RavepayResponse;
use App\Components\Payment\RazorpayResponse;
use App\Components\Payment\StripeResponse;
// use App\Models\Course;
// use App\Models\CourseOrder;
use App\Models\UserModel\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Exception;

class PaymentController extends Controller
{
    public function payment(Request $req)
    {
        $user = Payment::where('payment.id', $req->payid)
            ->join('users', 'users.id', 'payment.userId')
            ->select('payment.*', 'payment.amount as rechargeAmount', 'users.id as userId', 'users.name', 'users.email', 'users.contactNo', 'users.countryCode')
            ->orderBy('payment.id', 'DESC')
            ->where('payment.paymentStatus', 'pending')
            ->first();
        // Session::put('payid',$req->payid);
        // Using the Session facade to set a session variable
        $session = new Session();
        $session->set('pay_id', $req->payid);

        if ($user) {
            return view('payment.payment', compact('user'));
        } else {
            return back()->with('error', 'Invalid Payment Id');
        }
    }

    public function paymentprocess()
    {
        return view('payment.payment-process');
    }

    public function paymentsresponse(Request $request)
    {
        $requestData = $_REQUEST;
        $payload = @file_get_contents('php://input');

        // Get Config Data
        $configData = configItem();

        if (isset($requestData['payment_source']) && $requestData['payment_source'] == 'payu') {
            $requestData['order_id'] = $requestData['txnid'];
            $payUmoneyResponse = new PayUmoneyResponse();
            $payUmoneyResponseData = $payUmoneyResponse->getPayUmoneyPaymentResponse($requestData);

            [$requestData['udf5'], $pay_id] = explode('`', $requestData['udf5']);
            // dd($payUmoneyResponseData);
            $session = new Session();
            $session->set('pay_id', $pay_id);

            if ($payUmoneyResponseData['status'] == 'success') {
                $paymentResponseData = [
                    'status' => true,
                    'order_id' => $payUmoneyResponseData['raw_Data'],
                    'rawData' => $payUmoneyResponseData['raw_Data'],
                    'data' => $this->preparePaymentData($payUmoneyResponseData['order_id'], $payUmoneyResponseData['amount'], $payUmoneyResponseData['txn_id'], 'payumoney')
                ];
            } elseif ($payUmoneyResponseData['status'] == 'failed') {
                $paymentResponseData = [
                    'status' => false,
                    'order_id' => '',
                    'rawData' => $payUmoneyResponseData['raw_Data'],
                    'data' => $this->preparePaymentData($payUmoneyResponseData['order_id'], $payUmoneyResponseData['amount'], $payUmoneyResponseData['txn_id'], 'payumoney')
                ];
            }

            return redirect()->to($this->payment_Response($paymentResponseData));
        }

        // Get Request Data when payment success or failed
        // $requestData = $_REQUEST;
        // $payload = @file_get_contents('php://input');
        if (isset($requestData['paymentOption']) && $requestData['paymentOption'] == 'phonepe') {
            // dd($requestData,$_POST);
            $session = new Session();
            $session->set('pay_id', $requestData['pay_id']);
            $session->set('token', $requestData['logtoken']);

            // Get PhonePe credentials from database
            $phonepeMerchantId = DB::table('systemflag')
                ->where('name', 'phonepeMerchantId')
                ->select('value')
                ->first();

            $phonepeClientId = DB::table('systemflag')
                ->where('name', 'phonepeMerchantUserId')
                ->select('value')
                ->first();

            $phonepeClientSecret = DB::table('systemflag')
                ->where('name', 'phonepeSaltKey')
                ->select('value')
                ->first();

            $clientId = $phonepeClientId->value;  // Your client ID
            $clientSecret = $phonepeClientSecret->value;
            $merchantId = $phonepeMerchantId->value;

            // Step 1: Get Access Token
            $authUrl = 'https://api.phonepe.com/apis/identity-manager/v1/oauth/token';  // Sandbox: https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token

            $authResponse = Http::asForm()->withHeaders([
                'Accept' => 'application/json'
            ])->post($authUrl, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
                'client_version' => '1'
            ]);

            if (!$authResponse->successful()) {
                $errorResponse = $authResponse->json();
                error_log('PhonePe Auth Error: ' . json_encode($errorResponse));
                echo 'Failed to authenticate with PhonePe. Error: ' . ($errorResponse['message'] ?? 'Unknown error');
                return;
            }

            $authData = $authResponse->json();
            $accessToken = $authData['access_token'] ?? null;

            if (!$accessToken) {
                echo 'Failed to get access token from PhonePe';
                return;
            }

            // Step 2: Check Payment Status using V2 Checkout API
            $orderId = $session->get('phonepe_merchant_order_id');

            if (!$orderId) {
                echo 'No transaction ID provided';
                return;
            }

            // PhonePe V2 Status Check URL
            $phonePeStatusUrl = "https://api.phonepe.com/apis/pg/checkout/v2/order/{$orderId}/status";
            // Sandbox: https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/order/{$orderId}/status?details=false

            $statusResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'O-Bearer ' . $accessToken
            ])->get($phonePeStatusUrl);
            //   dd($statusResponse->json());
            if ($statusResponse->successful()) {
                $api_response = $statusResponse->json();

                // Check payment status - response structure may vary in V2
                if (isset($api_response['state']) && $api_response['state'] == 'COMPLETED') {
                    //  dd($api_response);
                    $paymentResponseData = [
                        'status' => true,
                        'data' => $this->preparePaymentData(
                            $api_response['orderId'] ?? $orderId,
                            ($api_response['amount'] ?? 0) / 100,
                            $api_response['paymentDetails'][0]['transactionId'] ?? $orderId,
                            'phonepe'
                        )
                    ];

                    return redirect()->to($this->payment_Response($paymentResponseData));
                } else {
                    // Handle pending/failed transactions
                    $status = $api_response['state'] ?? 'UNKNOWN';

                    $paymentResponseData = [
                        'status' => false,
                        'rawData' => $requestData,
                        'data' => $this->preparePaymentData($api_response['orderId'], $api_response['amount'], $api_response['orderId'], 'phonepe')
                    ];
                    return redirect()->to($this->payment_Response($paymentResponseData));
                }
            } else {
                $errorMsg = $statusResponse->body();
                error_log('PhonePe Status Check Error: ' . $errorMsg);
                echo 'Failed to check payment status. Status: ' . $statusResponse->status();
            }
        }

        if ($requestData['paymentOption'] == 'coingate') {
            if ($requestData['status'] == 'success') {
                // Create payment success response data.
                $paymentResponseData = [
                    'status' => true
                ];
                // Send data to payment response.
                return redirect()->to($this->payment_Response($paymentResponseData));
            } else if ($requestData['status'] == 'cancel') {
                // Create payment failed response data.
                $paymentResponseData = [
                    'status' => false
                ];
                // Send data to payment response function
                return redirect()->to($this->payment_Response($paymentResponseData));
            }
        } else if ($requestData['paymentOption'] == 'coingate' && !empty($payload)) {
            if (isset($payload['status']) && $payload['status'] == 'paid') {
                // Then create a data for success paypal data
                $paymentResponseData = [
                    'status' => true,
                    'rawData' => (array) $payload,
                    'data' => $this->preparePaymentData($payload['order_id'], $payload['price_amount'], $payload['id'], 'coingate')
                ];
                // Send data to payment response function for further process
                return redirect()->to($this->payment_Response($paymentResponseData));
            } else {
                $paymentResponseData = [
                    'status' => $payload['status'],
                    'rawData' => (array) $payload,
                    'data' => $this->preparePaymentData($payload['order_id'], $payload['price_amount'], $payload['id'], 'coingate')
                ];
                // Send data to payment response function for further process
                return redirect()->to($this->payment_Response($paymentResponseData));
            }
        }

        // Check payment Method is paytm
        if ($requestData['paymentOption'] == 'paytm') {
            // Get Payment Response instance
            $paytmResponse = new PaytmResponse();

            // Fetch payment data using payment response instance
            $paytmData = $paytmResponse->getPaytmPaymentData($requestData);

            // Check if payment status is success
            if ($paytmData['STATUS'] == 'TXN_SUCCESS') {
                // Create payment success response data.
                $paymentResponseData = [
                    'status' => true,
                    'rawData' => $paytmData,
                    'data' => $this->preparePaymentData($paytmData['ORDERID'], $paytmData['TXNAMOUNT'], $paytmData['TXNID'], 'paytm')
                ];
                // Send data to payment response.
                return redirect()->to($this->payment_Response($paymentResponseData));
            } else {
                // Create payment failed response data.
                $paymentResponseData = [
                    'status' => false,
                    'rawData' => $paytmData,
                    'data' => $this->preparePaymentData($paytmData['ORDERID'], $paytmData['TXNAMOUNT'], $paytmData['TXNID'], 'paytm')
                ];
                // Send data to payment response function
                return redirect()->to($this->payment_Response($paymentResponseData));
            }
            // Check payment method is instamojo
        } elseif ($requestData['paymentOption'] == 'instamojo') {
            // Check if payment successfully procced
            if ($requestData['payment_status'] == 'Credit') {
                // Get Instance of instamojo response service
                $instamojoResponse = new InstamojoResponse();

                // fetch payment data from instamojo response instance
                $instamojoData = $instamojoResponse->getInstamojoPaymentData($requestData);

                // Prepare data for payment response
                $paymentResponseData = [
                    'status' => true,
                    'rawData' => $instamojoData,
                    'data' => $this->preparePaymentData($requestData['orderId'], $instamojoData['amount'], $instamojoData['payment_id'], 'instamojo')
                ];
                // Send data to payment response
                return redirect()->to($this->payment_Response($paymentResponseData));
                // Check if payment failed then send failed response
            } else {
                // Prepare data for failed response data
                $paymentResponseData = [
                    'status' => false,
                    'rawData' => $requestData,
                    'data' => $this->preparePaymentData($requestData['orderId'], $instamojoData['amount'], null, 'instamojo')
                ];
                // Send data to payment response function
                return redirect()->to($this->payment_Response($paymentResponseData));
            }

            // Check if payment method is iyzico.
        } elseif ($requestData['paymentOption'] == 'iyzico') {
            // Check if payment status is success for iyzico.
            if ($requestData['status'] == 'success') {
                // Get iyzico response.
                $iyzicoResponse = new IyzicoResponse();

                // fetch payment data using iyzico response instance.
                $iyzicoData = $iyzicoResponse->getIyzicoPaymentData($requestData);
                $rawResult = json_decode($iyzicoData->getRawResult(), true);

                // Check if iyzico payment data is success
                // Then create a array for success data
                if ($iyzicoData->getStatus() == 'success') {
                    $paymentResponseData = [
                        'status' => true,
                        'rawData' => (array) $iyzicoData,
                        'data' => $this->preparePaymentData($requestData['orderId'], $rawResult['price'], $rawResult['conversationId'], 'iyzico')
                    ];
                    // Send data to payment response
                    return redirect()->to($this->payment_Response($paymentResponseData));
                    // If payment failed then create data for failed
                } else {
                    // Prepare failed payment data
                    $paymentResponseData = [
                        'status' => false,
                        'rawData' => (array) $iyzicoData,
                        'data' => $this->preparePaymentData($requestData['orderId'], $rawResult['price'], $rawResult['conversationId'], 'iyzico')
                    ];
                    // Send data to payment response
                    return redirect()->to($this->payment_Response($paymentResponseData));
                }
                // Check before 3d payment process payment failed
            } else {
                // Prepare failed payment data
                $paymentResponseData = [
                    'status' => false,
                    'rawData' => $requestData,
                    'data' => $this->preparePaymentData($requestData['orderId'], $rawResult['price'], null, 'iyzico')
                ];
                // Send data to process response
                return redirect()->to($this->payment_Response($paymentResponseData));
            }

            // Check Paypal payment process
        } elseif ($requestData['paymentOption'] == 'paypal') {
            //   dd($request->all());

            //  // Get instance of paypal
            //  $paypalIpnResponse  = new PaypalIpnResponse();

            //  // fetch paypal payment data
            //  $paypalIpnData = $paypalIpnResponse->getPaypalPaymentData();
            //  $rawData = json_decode($paypalIpnData, true);

            if (!isset($requestData['payment_status'])) {
                return redirect()->route('front.home');
            }

            $rawData = $requestData;

            $datapp = json_encode($rawData);
            if (isset($rawData['custom'])) {
                $custom = json_decode($rawData['custom'], true);
            }

            // Note : IPN and redirects will come here
            // Check if payment status exist and it is success
            if (isset($requestData['payment_status']) and $requestData['payment_status'] == 'Completed') {
                // Then create a data for success paypal data
                $paymentResponseData = [
                    'status' => true,
                    'rawData' => (array) $datapp,
                    'data' => $this->preparePaymentData($rawData['invoice'], $rawData['payment_gross'], $rawData['txn_id'], 'paypal', $custom['pay_id'])
                ];

                // Send data to payment response function for further process
                return redirect()->to($this->payment_Response($paymentResponseData));
                // Check if payment not successful
            } else {
                // Prepare payment failed data
                $paymentResponseData = [
                    'status' => false,
                    'rawData' => [],
                    'data' => $this->preparePaymentData($rawData['invoice'], $rawData['payment_gross'], null, 'paypal', $custom['pay_id'])
                ];
                // Send data to payment response function for further process
                return redirect()->to($this->payment_Response($paymentResponseData));
            }

            // Check Paystack payment process
        } elseif ($requestData['paymentOption'] == 'paystack') {
            $requestData = json_decode($requestData['response'], true);

            // Check if status key exists and payment is successfully completed
            if (isset($requestData['status']) and $requestData['status'] == 'success') {
                // Create data for payment success
                $paymentResponseData = [
                    'status' => true,
                    'rawData' => $requestData,
                    'data' => $this->preparePaymentData($requestData['data']['reference'], $requestData['data']['amount'], $requestData['data']['reference'], 'paystack')
                ];
                // Send data to payment response for further process
                return redirect()->to($this->payment_Response($paymentResponseData));

                // If paystack payment is failed
            } else {
                // Prepare data for failed payment
                $paymentResponseData = [
                    'status' => false,
                    'rawData' => $requestData,
                    'data' => $this->preparePaymentData($requestData['data']['reference'], $requestData['data']['amount'], $requestData['data']['reference'], 'paystack')
                ];
                // Send data to payment response to further process
                return redirect()->to($this->payment_Response($paymentResponseData));
            }

            // Check Stripe payment process
        } elseif ($requestData['paymentOption'] == 'stripe') {
            $stripeResponse = new StripeResponse();

            $stripeData = $stripeResponse->retrieveStripePaymentData($requestData['stripe_session_id']);

            // Check if payment charge status key exist in stripe data and it success
            if (isset($stripeData['status']) and $stripeData['status'] == 'succeeded') {
                // Prepare data for success
                $paymentResponseData = [
                    'status' => true,
                    'rawData' => $stripeData,
                    'data' => $this->preparePaymentData($requestData['orderId'], $stripeData->amount, $requestData['stripe_session_id'], 'stripe', $requestData['pay_id'])
                ];

                // Check if stripe data is failed
            } else {
                // Prepare failed payment data
                $paymentResponseData = [
                    'status' => false,
                    'rawData' => $stripeData,
                    'data' => $this->preparePaymentData($requestData['orderId'], $stripeData[0]->amount_total, null, 'stripe', $requestData['pay_id'])
                ];
            }

            // Send data to payment response for further process
            return redirect()->to($this->payment_Response($paymentResponseData));

            // Check Razorpay payment process
        } elseif ($requestData['paymentOption'] == 'razorpay') {
            $orderId = $requestData['orderId'];
            $pay_id = $requestData['payid'];

            $requestData = json_decode($requestData['response'], true);

            // Check if razorpay status exist and status is success
            if (isset($requestData['status']) and $requestData['status'] == 'captured') {
                // prepare payment data
                $paymentResponseData = [
                    'status' => true,
                    'rawData' => $requestData,
                    'data' => $this->preparePaymentData($orderId, $requestData['amount'], $requestData['id'], 'razorpay', $pay_id)
                ];
                // send data to payment response
                return redirect()->to($this->payment_Response($paymentResponseData));
                // razorpay status is failed
            } else {
                // prepare payment data for failed payment
                $paymentResponseData = [
                    'status' => false,
                    'rawData' => $requestData,
                    'data' => $this->preparePaymentData($orderId, $requestData['amount'], $requestData['id'], 'razorpay', $pay_id)
                ];
                // send data to payment response
                return redirect()->to($this->payment_Response($paymentResponseData));
            }
        } elseif ($requestData['paymentOption'] == 'authorize-net') {
            $orderId = $requestData['order_id'];

            $requestData = json_decode($requestData['response'], true);

            // Check if razorpay status exist and status is success
            if (isset($requestData['status']) and $requestData['status'] == 'success') {
                // prepare payment data
                $paymentResponseData = [
                    'status' => true,
                    'rawData' => $requestData,
                    'data' => $this->preparePaymentData($orderId, $requestData['amount'], $requestData['transaction_id'], 'authorize-net')
                ];
                // send data to payment response
                return redirect()->to($this->payment_Response($paymentResponseData));
                // razorpay status is failed
            } else {
                // prepare payment data for failed payment
                $paymentResponseData = [
                    'status' => false,
                    'rawData' => $requestData,
                    'data' => $this->preparePaymentData($orderId, $requestData['amount'], $requestData['transaction_id'], 'authorize-net')
                ];
                // send data to payment response
                return redirect()->to($this->payment_Response($paymentResponseData));
            }
        } elseif ($requestData['paymentOption'] == 'mercadopago') {
            if ($requestData['collection_status'] == 'approved') {
                $paymentResponseData = [
                    'status' => true,
                    'rawData' => $requestData,
                    'data' => $this->preparePaymentData($requestData['order_id'], $requestData['amount'], $requestData['collection_id'], 'mercadopago')
                ];
            } elseif ($requestData['collection_status'] == 'pending') {
                $paymentResponseData = [
                    'status' => 'pending',
                    'rawData' => $requestData,
                    'data' => $this->preparePaymentData($requestData['order_id'], $requestData['amount'], $requestData['collection_id'], 'mercadopago')
                ];
            } else {
                $paymentResponseData = [
                    'status' => false,
                    'rawData' => $requestData,
                    'data' => $this->preparePaymentData($requestData['order_id'], $requestData['amount'], $requestData['collection_id'], 'mercadopago')
                ];
            }
            return redirect()->to($this->payment_Response($paymentResponseData));
        } elseif ($requestData['paymentOption'] == 'mercadopago-ipn') {
            $mercadopagoResponse = new MercadopagoResponse();
            $mercadopagoIpnData = $mercadopagoResponse->getMercadopagoPaymentData($requestData);

            // Ipn data recieved here are as following
            // $mercadopagoIpnData['status'] = 'total_paid or not_paid';
            // $mercadopagoIpnData['message'] = 'Message';
            // $mercadopagoIpnData['raw_data'] = 'Raw Ipn Data';
        } elseif ($requestData['paymentOption'] == 'payumoney') {
            $payUmoneyResponse = new PayUmoneyResponse();
            $payUmoneyResponseData = $payUmoneyResponse->getPayUmoneyPaymentResponse($requestData);

            [$requestData['udf5'], $pay_id] = explode('`', $requestData['udf5']);

            $session = new Session();
            $session->set('pay_id', $pay_id);

            if ($payUmoneyResponseData['status'] == 'success') {
                $paymentResponseData = [
                    'status' => true,
                    'order_id' => $payUmoneyResponseData['raw_Data'],
                    'rawData' => $payUmoneyResponseData['raw_Data'],
                    'data' => $this->preparePaymentData($payUmoneyResponseData['order_id'], $payUmoneyResponseData['amount'], $payUmoneyResponseData['txn_id'], 'payumoney')
                ];
            } elseif ($payUmoneyResponseData['status'] == 'failed') {
                $paymentResponseData = [
                    'status' => false,
                    'order_id' => '',
                    'rawData' => $payUmoneyResponseData['raw_Data'],
                    'data' => $this->preparePaymentData($payUmoneyResponseData['order_id'], $payUmoneyResponseData['amount'], $payUmoneyResponseData['txn_id'], 'payumoney')
                ];
            }

            return redirect()->to($this->payment_Response($paymentResponseData));
        } elseif ($requestData['paymentOption'] == 'mollie') {
            $paymentResponseData = [
                'status' => true,
                'order_id' => $requestData['order_id'],
                'rawData' => $requestData,
                'data' => $this->preparePaymentData($requestData['order_id'], $requestData['amount'], null, 'mollie')
            ];

            return redirect()->to($this->payment_Response($paymentResponseData));
        } elseif ($requestData['paymentOption'] == 'mollie-webhook') {
            $mollieResponse = new MollieResponse();
            $webhookData = $mollieResponse->retrieveMollieWebhookData($requestData);

            // mollie webhook data received here with following option
            // $webhookData['status']; - payment status (paid|open|pending|failed|expired|canceled|refund|chargeback)
            // $webhookData['raw_data']; - webhook all raw data
            // $webhookData['message']; - if payment failed then message

            // Check Ravepay payment process
        } elseif ($requestData['paymentOption'] == 'ravepay') {
            $requestData = json_decode($requestData['response'], true);

            // Check if status key exists and payment is successfully completed
            if (isset($requestData['body']['status']) and $requestData['body']['status'] == 'success') {
                // Create data for payment success
                $paymentResponseData = [
                    'status' => true,
                    'rawData' => $requestData,
                    'data' => $this->preparePaymentData($requestData['body']['data']['txref'], $requestData['body']['data']['amount'], $requestData['body']['data']['txid'], 'ravepay')
                ];
                // Send data to payment response for further process
                return redirect()->to($this->payment_Response($paymentResponseData));
                // If ravepay payment is failed
            } else {
                // Prepare data for failed payment
                $paymentResponseData = [
                    'status' => false,
                    'rawData' => $requestData,
                    'data' => $this->preparePaymentData($requestData['body']['data']['txref'], $requestData['body']['data']['amount'], $requestData['body']['data']['txid'], 'ravepay')
                ];
                // Send data to payment response to further process
                return redirect()->to($this->payment_Response($paymentResponseData));
            }

            // Check Pagseguro payment process
        } elseif ($requestData['paymentOption'] == 'pagseguro') {
            // Get Payment Response instance
            $pagseguroResponse = new PagseguroResponse();

            // Fetch payment data using payment response instance
            $pagseguroData = $pagseguroResponse->fetchTransactionByRefrenceId($requestData['reference_id']);

            // handling errors
            if (isset($pagseguroData['status']) and $pagseguroData['status'] == 'error') {
                // throw exception when generate errors
                throw new Exception($pagseguroData['message']);
            }

            // transaction status
            // 1 - Awaiting payment, 2 - In analysis, 3 - Pay, 4 - Available, 5 - In dispute,
            // 6 - Returned, 7 - Canceled
            $txnStatus = $pagseguroData['responseData']->getTransactions()[0]->getStatus();

            // collect transaction code
            $transactionCode = $pagseguroData['responseData']->getTransactions()[0]->getCode();

            // Fetch transaction data by transaction code
            $transactionData = $pagseguroResponse->fetchTransactionByTxnCode($transactionCode);

            // Check if payment status is success
            if ($transactionData['status'] == 'success' and $txnStatus == 3 and $transactionData['responseData']->getReference() == $requestData['reference_id']) {
                // Create payment success response data.
                $paymentResponseData = [
                    'status' => true,
                    'rawData' => $transactionData['responseData'],
                    'data' => $this->preparePaymentData(
                        $transactionData['responseData']->getReference(),
                        $transactionData['responseData']->getGrossAmount(),
                        $transactionData['responseData']->getCode(),
                        'pagseguro'
                    )
                ];
                // Send data to payment response.
                return redirect()->to($this->payment_Response($paymentResponseData));
            } else {
                // Create payment failed response data.
                $paymentResponseData = [
                    'status' => false,
                    'rawData' => $paytmData,
                    'data' => $this->preparePaymentData(
                        $transactionData['responseData']->getReference(),
                        $transactionData['responseData']->getGrossAmount(),
                        $transactionData['responseData']->getCode(),
                        'pagseguro'
                    )
                ];
                // Send data to payment response function
                return redirect()->to($this->payment_Response($paymentResponseData));
            }
        } else if ($requestData['paymentOption'] == 'paypal-checkout') {
            $rawData = json_decode($requestData['response'], true);
            $amount = $rawData['purchase_units'][0]['payments']['captures'][0]['amount']['value'];

            // Check if payment status exist and it is success
            if (isset($rawData['status']) and $rawData['status'] == 'COMPLETED') {
                // Then create a data for success paypal data
                $paymentResponseData = [
                    'status' => true,
                    'rawData' => (array) $requestData,
                    'data' => $this->preparePaymentData($requestData['orderId'], $amount, $rawData['id'], 'paypal-checkout')
                ];
                // Send data to payment response function for further process
                return redirect()->to($this->payment_Response($paymentResponseData));
                // Check if payment not successful
            } else {
                // Prepare payment failed data
                $paymentResponseData = [
                    'status' => false,
                    'rawData' => [],
                    'data' => $this->preparePaymentData($requestData['orderId'], $amount, null, 'paypal-checkout')
                ];
                // Send data to payment response function for further process
                return redirect()->to($this->payment_Response($paymentResponseData));
                // return redirect()->to($this->payment_Response($paymentResponseData));
            }
        }

        //  return view('payment.payment-response',compact('requestData','payload'));
    }

    /*
     * This payment used for get Success / Failed data for any payment method.
     *
     * @param array $paymentResponseData - contains : status and rawData
     */

    public function payment_Response($paymentResponseData)
    {
        $pay_id = $paymentResponseData['data']['pay_id'] ?? null;
        $order_id = $paymentResponseData['data']['order_id'] ?? null;

        // payment status success
        if ($paymentResponseData['status'] === true) {
            $payment = null;
            if (!empty($pay_id)) {
                $payment = Payment::where('payment.id', $pay_id)
                    ->join('users', 'users.id', 'payment.userId')
                    ->select('payment.*', 'users.id as userId', 'users.name', 'users.email', 'users.contactNo')
                    ->where('payment.paymentStatus', 'pending')
                    ->first();
            } elseif (!empty($order_id)) {
                $payment = Payment::where('payment.orderId', $order_id)
                    ->join('users', 'users.id', 'payment.userId')
                    ->select('payment.*', 'users.id as userId', 'users.name', 'users.email', 'users.contactNo')
                    ->where('payment.paymentStatus', 'pending')
                    ->first();
            }

            if ($payment) {
                $user = \App\Models\User::find($payment->userId);
                if ($user) {
                    try {
                        $logtoken = \Illuminate\Support\Facades\Auth::guard('api')->login($user);
                        $session = new Session();
                        $session->set('token', $logtoken);
                        $session->remove('phonepe_merchant_order_id');
                    } catch (\Throwable $e) {}
                }

                $inr_usd_conv_rate = DB::table('systemflag')->where('name', 'UsdtoInr')->select('value')->first();
                $conv_rate = $inr_usd_conv_rate ? $inr_usd_conv_rate->value : 83.5;

                DB::transaction(function () use ($payment, $paymentResponseData, $conv_rate) {
                    $gst_amount = $payment->gst_amount ?? 0;
                    $original_amount = $payment->amount - $gst_amount;
                    $payment->update([
                        'paymentMode' => $paymentResponseData['data']['payment_gateway'],
                        'paymentReference' => $paymentResponseData['data']['payment_reference_id'],
                        'paymentStatus' => 'success',
                        'amount' => $original_amount,
                        'orderId' => $paymentResponseData['data']['order_id'],
                    ]);

                    $userWallet = UserWallet::where('userId', '=', $payment->userId)->lockForUpdate()->first();
                    $cashback = $payment->cashback_amount ?? 0;

                    if ($userWallet) {
                        $userWallet->amount = $userWallet->amount + $original_amount + $cashback;
                        $userWallet->update();
                    } else {
                        UserWallet::create([
                            'userId' => $payment->userId,
                            'amount' => $original_amount + $cashback,
                            'createdBy' => $payment->userId,
                            'modifiedBy' => $payment->userId,
                        ]);
                    }

                    if (!empty($cashback) && $cashback > 0) {
                        DB::table('wallettransaction')->insert([
                            'userId' => $payment->userId,
                            'amount' => $cashback,
                            'isCredit' => true,
                            'transactionType' => 'Cashback',
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                            'inr_usd_conversion_rate' => $conv_rate,
                        ]);
                    }

                    // Auto-execute PlacePujaOrder if this payment was specifically for a Puja
                    if ($payment->payment_for === 'puja' && !empty($payment->payment_order_info)) {
                        $orderInfo = is_array($payment->payment_order_info) ? $payment->payment_order_info : json_decode($payment->payment_order_info, true);
                        if (!empty($orderInfo)) {
                            try {
                                if (function_exists('PlacePujaOrder')) {
                                    PlacePujaOrder($orderInfo, $payment->userId);
                                }
                            } catch (\Throwable $ex) {
                                \Log::error("PlacePujaOrder failed after payment: " . $ex->getMessage());
                            }
                        }
                    }
                });

                $session = new Session();
                $session->remove('pay_id');
                $session->remove('phonepe_merchant_order_id');
                return getAppUrl('payment-success');
            } else {
                // Idempotency guard: If already processed and marked success, redirect to success
                $alreadySuccess = Payment::where(function ($q) use ($pay_id, $order_id) {
                    if (!empty($pay_id)) {
                        $q->where('id', $pay_id);
                    } elseif (!empty($order_id)) {
                        $q->where('orderId', $order_id);
                    }
                })->where('paymentStatus', 'success')->first();

                if ($alreadySuccess) {
                    return getAppUrl('payment-success');
                }
            }
            return getAppUrl('payment-success');
        } elseif ($paymentResponseData['status'] === 'pending') {
            return getAppUrl('payment-pending');
        } else {
            // Failed payment branch
            $payment = null;
            if (!empty($pay_id)) {
                $payment = Payment::where('id', $pay_id)->where('paymentStatus', 'pending')->first();
            } elseif (!empty($order_id)) {
                $payment = Payment::where('orderId', $order_id)->where('paymentStatus', 'pending')->first();
            }

            if ($payment) {
                $payment->update([
                    'paymentMode' => $paymentResponseData['data']['payment_gateway'],
                    'paymentReference' => $paymentResponseData['data']['payment_reference_id'],
                    'paymentStatus' => 'failed',
                    'orderId' => $paymentResponseData['data']['order_id'],
                ]);

                $user = \App\Models\User::find($payment->userId);
                if ($user) {
                    try {
                        $logtoken = \Illuminate\Support\Facades\Auth::guard('api')->login($user);
                        $session = new Session();
                        $session->set('token', $logtoken);
                    } catch (\Throwable $e) {}
                }
            }
            $session = new Session();
            $session->remove('pay_id');
            $session->remove('phonepe_merchant_order_id');
            return getAppUrl('payment-failed');
        }
    }

    /*
     * Prepare Payment Data.
     *
     * @param array $paymentData
     */
    public function preparePaymentData($orderId, $amount, $txnId, $paymentGateway, $pay_id = 0)
    {
        if ($pay_id == 0) {
            $session = new Session();
            $pay_id = $session->get('pay_id');
        }

        return [
            'order_id' => $orderId,
            'amount' => $amount,
            'payment_reference_id' => $txnId,
            'payment_gateway' => $paymentGateway,
            'pay_id' => $pay_id
        ];
    }

    public function paymentpending()
    {
        return view('payment.payment-pending');
    }

    public function paymentfailed()
    {
        $user = authcheck();
        $userId = $user ? ($user['id'] ?? $user->id ?? null) : null;

        $payment = $userId ? Payment::where('userId', $userId)
            ->where('payment_for', 'topupchat')
            ->latest()
            ->first() : null;

        $paymentcall = $userId ? Payment::where('userId', $userId)
            ->where('payment_for', 'topupcall')
            ->latest()
            ->first() : null;

        return view('payment.payment-failed', compact('payment', 'paymentcall'));
    }

    public function paymentsuccess(Request $req)
    {
        $user = authcheck();
        $userId = $user ? ($user['id'] ?? $user->id ?? null) : null;

        $payment = $userId ? Payment::where('userId', $userId)
            ->where('payment_for', 'topupchat')
            ->latest()
            ->first() : null;

        if ($payment && $payment->chatId) {
            try {
                $chatReq = new Request([
                    'chat_duration' => $payment->durationchat,
                    'chatId' => $payment->chatId,
                ]);
                app(\App\Http\Controllers\API\User\ChatRequestController::class)->updatechatMinute($chatReq);
            } catch (\Throwable $e) {
                \Log::error("Failed to update chat minutes after payment: " . $e->getMessage());
            }
        }

        $paymentcall = $userId ? Payment::where('userId', $userId)
            ->where('payment_for', 'topupcall')
            ->latest()
            ->first() : null;

        if ($paymentcall && $paymentcall->callId) {
            try {
                $callReq = new Request([
                    'call_duration' => $paymentcall->durationcall,
                    'callId' => $paymentcall->callId,
                ]);
                app(\App\Http\Controllers\API\User\CallRequestController::class)->updateCallMinute($callReq);
            } catch (\Throwable $e) {
                \Log::error("Failed to update call minutes after payment: " . $e->getMessage());
            }
        }

                // Meta Conversions API (CAPI) Server-Side Purchase Dispatch
        try {
            $paidPayment = $payment ?: $paymentcall;
            $paidAmount = $paidPayment ? (float)($paidPayment->amount ?? 0) : 0;
            if ($paidAmount > 0) {
                $capi = app(\App\Services\MetaCapiService::class);
                $capi->sendEvent('Purchase', [
                    'email' => $user['email'] ?? ($user->email ?? null),
                    'phone' => $user['contactNo'] ?? ($user->contactNo ?? null),
                ], [
                    'value'        => $paidAmount,
                    'currency'     => 'INR',
                    'content_name' => 'Wallet Recharge / Vedic Service',
                ], 'pay_' . ($paidPayment->id ?? time()));
            }
        } catch (\Throwable $e) {
            \Log::warning("Meta CAPI paymentSuccess dispatch failed non-fatally: " . $e->getMessage());
        }

        return view('payment.payment-success', compact('payment', 'paymentcall', 'user'));
    }
}
