<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Accounting;
use App\Models\BecomeInstructor;
use App\Models\Cart;
use App\Models\OrderItem;
use App\Models\PaymentChannel;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ReserveMeeting;
use App\Http\Controllers\Web\traits\PaymentsTrait;
use App\Mixins\Cashback\CashbackAccounting;
use App\Models\Reward;
use App\Models\RewardAccounting;
use App\Models\Sale;
use App\Models\TicketUser;
use Illuminate\Http\Client\ConnectionException;

class AfpmanagerController extends Controller
{

    //check auth
    protected function is_authenticated(){
        if(Auth::check()){
            return true;
        }
        return false;
    }

    // Manage payment request
    protected $InitbaseUrl;
    protected $baseUrl;
    protected $appId;
    protected $apiUsername;
    protected $apiPassword;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->InitbaseUrl   = env('AFPAY_INIT_BASE_URL');
        $this->baseUrl       = env('AFPAY_BASE_URL');
        $this->appId         = env('AFPAY_API_PUBLIC_KEY');
        $this->apiUsername   = env('AFPAY_API_ACCOUNT_USERNAME');
        $this->apiPassword   = env('AFPAY_API_ACCOUNT_PASSWORD');
    }

    protected function client()
    {
        $fixedBearer = 'MzFhZWM3YzYtYmE4Ni0xMWYwLTgwMGMtMDAwMDAwMDAwMDBhOjAwMmY1ZDJhYjIyNjQ0NjRhMWZiY2ZjM2YzN2YzOTY1';
        return Http::withHeaders([
            'Authorization' => "Bearer {$fixedBearer}",
            'Accept'        => 'application/json',
        ])->withoutVerifying();
    }

    protected function getAuthToken()
    {
        if($this->is_authenticated()){
            try {
                $payload = [
                    'username' => $this->apiUsername,
                    'password' => $this->apiPassword,
                ];
                $url = "{$this->InitbaseUrl}api-auth/auth";
                $response = Http::asForm()->post($url, $payload);
                $token = $response['token'];
                if($token){
                    return response()->json([
                        'status'    => 200,
                        "message"   => "Opération réussie",
                        "token"     => $token
                    ]);
                }
                return response()->json([
                    'status'    => 500,
                    "message"   => "Echec de l'opération. impossible d'effectuer l'authentification.",
                ]);
            } catch (ConnectionException $e) {
                return response()->json([
                    'status'    => 500,
                    "message"   => "The operation failed. Your internet connection is unstable. Please try again.",
                ]);
            }
        }
        return response()->json([
            'status'    => 500,
            "message"   => "Your session has expired, please log in again and try again.",
        ]);
    }

    protected function getTransactionCommission(Request $request)
    {
        if($this->is_authenticated()){
            try {
                /**Controle de validation des inputs */
                $rules = array(
                    'api_token'             => 'required',
                    'serviceid'             => 'required',
                    'amount'                => 'required',
                );

                $validator = Validator::make($request->all(), $rules);

                $token      = $request->input('api_token');
                $serviceid  = $request->input('serviceid');
                $amount     = $request->input('amount');

                if($validator->fails()){
                    $message = 'Veuillez remplir tous les champs.';
                    return response()->json([
                        'status'    => 500,
                        "message"   => $message
                    ]);
                }

                $payload = [
                    'token'      => $token,
                    'app_id'     => $this->appId,
                    'service_id' => $serviceid,
                    'amount'     => $amount,
                ];
                $url = "{$this->baseUrl}afpay-gateway-commission";
                $response = $this->client()->asMultipart()->post($url, $payload);
                if($response->status() == 200){
                    if($response['status'] == 200){
                        $result = [
                            'finalamount'   => $response['result']['finalamount'],
                            'commission'    => $response['result']['commission'],
                        ];

                        return response()->json([
                            'status'    => 200,
                            "message"   => "Opération réussie",
                            "result"    => $result
                        ]);
                    }
                    return response()->json([
                        'status'    => 500,
                        "message"   => $response['result']['message'],
                    ]);
                }
                //return error
                return response()->json([
                    'status'    => 500,
                    "message"   => "Unable to access the server",
                ]);
            } catch (ConnectionException $e) {
                return response()->json([
                    'status'    => 500,
                    "message"   => "The operation failed. Your internet connection is unstable. Please try again.",
                ]);
            }
        }
        return response()->json([
            'status'    => 500,
            "message"   => "Your session has expired, please log in again and try again.",
        ]);
    }

    protected function getPayItemId(Request $request)
    {
        if($this->is_authenticated()){
            try {
                /**Controle de validation des inputs */
                $rules = array(
                    'api_token'             => 'required',
                    'serviceid'             => 'required'
                );

                $validator  = Validator::make($request->all(), $rules);

                $url        = "{$this->baseUrl}afpay-gateway-cashout";
                $token      = $request->input('api_token');
                $serviceid  = $request->input('serviceid');

                if($validator->fails()){
                    $message = 'Veuillez remplir tous les champs.';
                    return response()->json([
                        'status'    => 500,
                        "message"   => $message
                    ]);
                }

                $payload    = [
                    'token'      => $token,
                    'app_id'     => $this->appId,
                    'service_id' => $serviceid,
                ];

                $response = $this->client()->get($url, $payload);
                if($response->status() == 200){
                    if($response['status'] == 200){
                        $result = [
                            'payItemId' => $response['result'][0]['payItemId'],
                            'serviceid' => $serviceid
                        ];

                        return response()->json([
                            'status'    => 200,
                            "message"   => "Opération réussie",
                            "result"    => $result
                        ]);
                    }
                    return response()->json([
                        'status'    => 500,
                        "message"   => $response['message'],
                    ]);
                }
                //return error
                return response()->json([
                    'status'    => 500,
                    "message"   => "Unable to access the server",
                ]);
            } catch (ConnectionException $e) {
                return response()->json([
                    'status'    => 500,
                    "message"   => "The operation failed. Your internet connection is unstable. Please try again.",
                ]);
            }
        }
        return response()->json([
            'status'    => 500,
            "message"   => "Your session has expired, please log in again and try again.",
        ]);
    }

    protected function getQuoteId(Request $request)
    {
        if($this->is_authenticated()){
            try {
                /**Controle de validation des inputs */
                $rules = array(
                    'api_token'             => 'required',
                    'serviceid'             => 'required',
                    'payItemId'             => 'required',
                    'amount'                => 'required',
                );

                $validator  = Validator::make($request->all(), $rules);

                $url        = "{$this->baseUrl}afpay-gateway-request-quote";
                $token      = $request->input('api_token');
                $serviceid  = $request->input('serviceid');
                $payItemId  = $request->input('payItemId');
                $amount     = $request->input('amount');

                if($validator->fails()){
                    $message = 'Veuillez remplir tous les champs.';
                    return response()->json([
                        'status'    => 500,
                        "message"   => $message
                    ]);
                }

                $payload = [
                    'token'         => $token,
                    'app_id'        => $this->appId,
                    'service_id'    => $serviceid,
                    'payItemId'     => $payItemId,
                    'amount'        => $amount
                ];

                $response = $this->client()->asMultipart()->post($url, $payload);
                error_log($amount.'montant');
                if($response->status() == 200){
                    if($response['status'] == 200){
                        $result = [
                            'quoteId'   => $response['result']['quoteId'],
                            'serviceid' => $serviceid
                        ];

                        return response()->json([
                            'status'    => 200,
                            "message"   => "Opération réussie",
                            "result"    => $result
                        ]);
                    }
                    return response()->json([
                        'status'    => 500,
                        "message"   => $response['message'],
                    ]);
                }
                //return error
                return response()->json([
                    'status'    => 500,
                    "message"   => "Unable to access the server",
                ]);
            } catch (ConnectionException $e) {
                return response()->json([
                    'status'    => 500,
                    "message"   => "The operation failed. Your internet connection is unstable. Please try again.",
                ]);
            }
        }
        return response()->json([
            'status'    => 500,
            "message"   => "Your session has expired, please log in again and try again.",
        ]);
    }

    protected function requestCollection(Request $request)
    {
        if($this->is_authenticated()){
            try {
                /**Controle de validation des inputs */
                $rules = array(
                    'api_token'             => 'required',
                    'serviceid'             => 'required',
                    'quoteId'               => 'required',
                    'account_number'        => 'required',
                    'orderid'               => 'required',
                    'customer_name'         => 'required',
                    'customer_address'      => 'required',
                    'currency'              => 'required',
                    'total_amount'          => 'required',
                    'init_amount'           => 'required'
                );

                $validator  = Validator::make($request->all(), $rules);

                $url                = "{$this->baseUrl}afpay-gateway-request-collection";
                $token              = $request->input('api_token');
                $serviceid          = $request->input('serviceid');
                $quoteId            = $request->input('quoteId');
                $account_number     = $request->input('account_number');
                $orderid            = $request->input('orderid');
                $customer_name      = $request->input('customer_name');
                $customer_address   = $request->input('customer_address');
                $currency           = $request->input('currency');
                $total_amount       = $request->input('total_amount');
                $init_amount        = $request->input('init_amount');
                $trid               = strtoupper(Str::random(8));
                $customer_email     = Auth::user()->email;

                if($validator->fails()){
                    error_log($validator->errors());
                    $message = 'Veuillez remplir tous les champs.';
                    return response()->json([
                        'status'    => 500,
                        "message"   => $message
                    ]);
                }

                $order          = Order::where('id', $orderid)->first();

                $description    = "Payment of order Nº ". $order->id. "by ".Auth::user()->full_name;

                $payload = [
                    'token'                 => $token,
                    'service_id'            => $serviceid,
                    'app_id'                => $this->appId,
                    'quoteId'               => $quoteId,
                    'customerPhonenumber'   => $account_number,
                    'customerName'          => $customer_name,
                    'customerEmailaddress'  => $customer_email,
                    'customerAddress'       => $customer_address,
                    'description'           => $description,
                    'trid'                  => $trid,
                    'initial_amount'        => $init_amount
                ];

                $response = $this->client()->asMultipart()->post($url, $payload);
                if($response->status() == 200){
                    if($response['status'] == 200){
                        //update order
                        $order->update(['status' => Order::$paying]);
                        $order->update(['payment_method' => Order::$paymentChannel]);
                        $order->update(['reference_id' => $response['transaction_ref']]);

                        if ($order->type === Order::$meeting) {
                            $orderItem = OrderItem::where('order_id', $order->id)->first();
                            $reserveMeeting = ReserveMeeting::where('id', $orderItem->reserve_meeting_id)->first();
                            $reserveMeeting->update(['locked_at' => time()]);
                        }

                        $result = [
                            'transaction_ref'   => $response['transaction_ref'],
                            'serviceid'         => $serviceid
                        ];

                        return response()->json([
                            'status'    => 200,
                            "message"   => "Opération réussie",
                            "result"    => $result
                        ]);
                    }
                    return response()->json([
                        'status'    => 500,
                        "message"   => $response['message'],
                    ]);
                }
                //return error
                return response()->json([
                    'status'    => 500,
                    "message"   => "Unable to access the server",
                ]);
            } catch (ConnectionException $e) {
                return response()->json([
                    'status'    => 500,
                    "message"   => "The operation failed. Your internet connection is unstable. Please try again.",
                ]);
            }
        }
        return response()->json([
            'status'    => 500,
            "message"   => "Your session has expired, please log in again and try again.",
        ]);
    }

    protected function transactionStatus(Request $request)
    {
        if($this->is_authenticated()){
            try {
                /**Controle de validation des inputs */
                $rules = array(
                    'api_token'             => 'required',
                    'serviceid'             => 'required',
                    'transaction_ref'       => 'required',
                );

                $validator  = Validator::make($request->all(), $rules);

                $url                = "{$this->baseUrl}afpay-gateway-transaction-status";
                $token              = $request->input('api_token');
                $serviceid          = $request->input('serviceid');
                $ptn                = $request->input('transaction_ref');

                if($validator->fails()){
                    $message = 'Please fill in all the fields.';
                    return response()->json([
                        'status'    => 500,
                        "message"   => $message
                    ]);
                }

                $payload = [
                    'token'      => $token,
                    'service_id' => $serviceid,
                    'app_id'     => $this->appId,
                    'ptn'        => $ptn,
                ];

                $response = $this->client()->get($url, $payload);
                if($response->status() == 200){

                    //get current payment
                    $order = Order::where('reference_id', $ptn)->first();

                    if($response['status'] == 200){
                        if($response['transactionStatus'] == 'SUCCESS'){

                            //update order
                            $this->setPaymentAccounting($order, 'payment_channel');
                            $order->update(['status' => Order::$paid]);

                            return response()->json([
                                'status'            => 200,
                                "message"           => "Opération réussie",
                            ]);
                        }
                    }else if($response['status'] == 403){
                        return response()->json([
                            'status'    => 403,
                            "message"   => $response['message'],
                        ]);
                    }

                    //update order
                    $order->update(['status' => Order::$fail]);

                    if ($order->type === Order::$meeting) {
                        $orderItem = OrderItem::where('order_id', $order->id)->first();

                        if ($orderItem && $orderItem->reserve_meeting_id) {
                            $reserveMeeting = ReserveMeeting::where('id', $orderItem->reserve_meeting_id)->first();

                            if ($reserveMeeting) {
                                $reserveMeeting->update(['locked_at' => null]);
                            }
                        }
                    }

                    return response()->json([
                        'status'    => 500,
                        "message"   => $response['message'],
                    ]);
                }
                //return error
                return response()->json([
                    'status'    => 302,
                    "message"   => "Unable to access the server",
                ]);
            } catch (ConnectionException $e) {
                return response()->json([
                    'status'    => 301,
                    "message"   => "The operation failed. Your internet connection is unstable. Please try again.",
                ]);
            }
        }
        return response()->json([
            'status'    => 500,
            "message"   => "Your session has expired, please log in again and try again.",
        ]);
    }

    protected function getVisaToken(Request $request)
    {
        if($this->is_authenticated()){
            try {
                /**Controle de validation des inputs */
                $rules = array(
                    'api_token'             => 'required',
                    'serviceid'             => 'required',
                );

                $validator  = Validator::make($request->all(), $rules);

                $url        = "{$this->baseUrl}afpay-gateway-visa-auth";
                $token      = $request->input('api_token');
                $serviceid  = $request->input('serviceid');

                if($validator->fails()){
                    $message = 'Veuillez remplir tous les champs.';
                    return response()->json([
                        'status'    => 500,
                        "message"   => $message
                    ]);
                }

                $payload = [
                    'token'         => $token,
                    'app_id'        => $this->appId,
                    'service_id'    => $serviceid,
                ];

                $response = $this->client()->asMultipart()->post($url, $payload);
                if($response->status() == 200){
                    if($response['status'] == 200){
                        $result = [
                            'access_token'  => $response['result']['access_token'],
                            'serviceid'     => $serviceid
                        ];

                        return response()->json([
                            'status'    => 200,
                            "message"   => "Opération réussie",
                            "result"    => $result
                        ]);
                    }
                    return response()->json([
                        'status'    => 500,
                        "message"   => $response['message'],
                    ]);
                }
                //return error
                return response()->json([
                    'status'    => 500,
                    "message"   => "Unable to access the server",
                ]);
                } catch (ConnectionException $e) {
                    return response()->json([
                        'status'    => 500,
                        "message"   => "The operation failed. Your internet connection is unstable. Please try again.",
                    ]);
                }
            }
        return response()->json([
            'status'    => 500,
            "message"   => "Your session has expired, please log in again and try again.",
        ]);
    }

    protected function requestVisaCollectionLink(Request $request)
    {
        if($this->is_authenticated()){
            try {
                /**Controle de validation des inputs */
                $rules = array(
                    'api_token'             => 'required',
                    'serviceid'             => 'required',
                    'access_token'          => 'required',
                    'total_amount'          => 'required',
                    'init_amount'           => 'required',
                    'orderid'               => 'required',
                    'account_number'        => 'required',
                    'customer_name'         => 'required',
                    'customer_address'      => 'required',
                );

                $validator  = Validator::make($request->all(), $rules);

                $url                = "{$this->baseUrl}afpay-gateway-visa-order";
                $token              = $request->input('api_token');
                $serviceid          = $request->input('serviceid');
                $access_token       = $request->input('access_token');
                $total_amount       = $request->input('total_amount');
                $init_amount        = $request->input('init_amount');
                $account_number     = $request->input('serviceid');
                $serviceid          = $request->input('serviceid');
                $currency           = $request->input('currency');
                $merchantReference  = strtoupper(Str::random(8));
                $orderid            = $request->input('orderid');
                $customer_name      = $request->input('customer_name');
                $customer_address   = $request->input('customer_address');
                $customer_email     = Auth::user()->email;
                $returnUrl          = "http://127.0.0.1:8000/panel/courses/purchases";

                $order          = Order::where('id', $orderid)->first();

                $description    = "Payment of order Nº ". $order->id. "by ".Auth::user()->full_name;

                if($validator->fails()){
                    $message = 'Veuillez remplir tous les champs.';
                    return response()->json([
                        'status'    => 500,
                        "message"   => $message
                    ]);
                }

                $payload = [
                    'token'                 => $token,
                    'service_id'            => $serviceid,
                    'app_id'                => $this->appId,
                    'trans_token'           => $access_token,
                    'currency'              => $currency,
                    'customerPhone'         => $account_number,
                    'customerName'          => $customer_name,
                    'customerEmail'         => $customer_email,
                    'customerAddress'       => $customer_address,
                    'description'           => $description,
                    'merchantReference'     => $merchantReference,
                    'total_amount'          => $total_amount,
                    'initial_amount'        => $init_amount,
                    'returnUrl'             => $returnUrl,
                ];

                $response = $this->client()->asMultipart()->post($url, $payload);
                if($response->status() == 200){
                    if($response['status'] == 200){

                        //update order
                        $order->update(['status' => Order::$paying]);
                        $order->update(['payment_method' => Order::$paymentChannel]);
                        $order->update(['reference_id' => $response['result']['transaction_ref']]);

                        if ($order->type === Order::$meeting) {
                            $orderItem = OrderItem::where('order_id', $order->id)->first();
                            $reserveMeeting = ReserveMeeting::where('id', $orderItem->reserve_meeting_id)->first();
                            $reserveMeeting->update(['locked_at' => time()]);
                        }

                        //return data
                        $result = [
                            'transaction_ref'   => $response['result']['transaction_ref'],
                            'payment_link'      => $response['result']['payment_link'],
                            'serviceid'         => $serviceid
                        ];

                        return response()->json([
                            'status'    => 200,
                            "message"   => "Opération réussie",
                            "result"    => $result
                        ]);
                    }
                    return response()->json([
                        'status'    => 500,
                        "message"   => $response['message'],
                    ]);
                }
                //return error
                return response()->json([
                    'status'    => 500,
                    "message"   => "Unable to access the server",
                ]);
            } catch (ConnectionException $e) {
                return response()->json([
                    'status'    => 500,
                    "message"   => "The operation failed. Your internet connection is unstable. Please try again.",
                ]);
            }
        }
        return response()->json([
            'status'    => 500,
            "message"   => "Your session has expired, please log in again and try again.",
        ]);
    }

    protected function transactionVisaStatus(Request $request)
    {
        if($this->is_authenticated()){
            try {
                /**Controle de validation des inputs */
                $rules = array(
                    'api_token'             => 'required',
                    'access_token'          => 'required',
                    'serviceid'             => 'required',
                    'transaction_ref'       => 'required',
                );

                $validator  = Validator::make($request->all(), $rules);

                $url                = "{$this->baseUrl}afpay-gateway-visa-paymentstatus";
                $token              = $request->input('api_token');
                $access_token       = $request->input('access_token');
                $serviceid          = $request->input('serviceid');
                $txid                = $request->input('transaction_ref');

                if($validator->fails()){
                    $message = 'Please fill in all the fields.';
                    return response()->json([
                        'status'    => 500,
                        "message"   => $message
                    ]);
                }

                $payload = [
                    'token'         => $token,
                    'trans_token'   => $access_token,
                    'service_id'    => $serviceid,
                    'app_id'        => $this->appId,
                    'txid'          => $txid,
                ];

                $response = $this->client()->get($url, $payload);
                if($response->status() == 200){

                    //get current payment
                    $order = Order::where('reference_id', $txid)->first();

                    if($response['status'] == 200){
                        if($response['transactionStatus'] == 'SUCCESS'){

                            //update order
                            $this->setPaymentAccounting($order, 'payment_channel');
                            $order->update(['status' => Order::$paid]);

                            return response()->json([
                                'status'            => 200,
                                "message"           => "Opération réussie",
                            ]);
                        }
                    }else if($response['status'] == 403){
                        return response()->json([
                            'status'    => 403,
                            "message"   => $response['message'],
                        ]);
                    }

                    //update order
                    $order->update(['status' => Order::$fail]);

                    if ($order->type === Order::$meeting) {
                        $orderItem = OrderItem::where('order_id', $order->id)->first();

                        if ($orderItem && $orderItem->reserve_meeting_id) {
                            $reserveMeeting = ReserveMeeting::where('id', $orderItem->reserve_meeting_id)->first();

                            if ($reserveMeeting) {
                                $reserveMeeting->update(['locked_at' => null]);
                            }
                        }
                    }

                    return response()->json([
                        'status'    => 500,
                        "message"   => $response['message'],
                    ]);
                }
                //return error
                return response()->json([
                    'status'    => 302,
                    "message"   => "Unable to access the server",
                ]);
            } catch (ConnectionException $e) {
                return response()->json([
                    'status'    => 301,
                    "message"   => "The operation failed. Your internet connection is unstable. Please try again.",
                ]);
            }
        }
        return response()->json([
            'status'    => 500,
            "message"   => "Your session has expired, please log in again and try again.",
        ]);
    }


    public function setPaymentAccounting($order, $type = null)
    {
        $cashbackAccounting = new CashbackAccounting();

        if ($order->is_charge_account) {
            Accounting::charge($order);

            $cashbackAccounting->rechargeWallet($order);
        } else {
            foreach ($order->orderItems as $orderItem) {
                $updateInstallmentOrderAfterSale = false;
                $updateProductOrderAfterSale = false;

                if (!empty($orderItem->gift_id)) {
                    $gift = $orderItem->gift;

                    $gift->update([
                        'status' => 'active'
                    ]);

                    $gift->sendNotificationsWhenActivated($orderItem->total_amount);
                }

                if (!empty($orderItem->subscribe_id)) {
                    Accounting::createAccountingForSubscribe($orderItem, $type);
                } elseif (!empty($orderItem->promotion_id)) {
                    Accounting::createAccountingForPromotion($orderItem, $type);
                } elseif (!empty($orderItem->registration_package_id)) {
                    Accounting::createAccountingForRegistrationPackage($orderItem, $type);

                    if (!empty($orderItem->become_instructor_id)) {
                        BecomeInstructor::where('id', $orderItem->become_instructor_id)
                            ->update([
                                'package_id' => $orderItem->registration_package_id
                            ]);
                    }
                } elseif (!empty($orderItem->installment_payment_id)) {
                    Accounting::createAccountingForInstallmentPayment($orderItem, $type);

                    $updateInstallmentOrderAfterSale = true;
                } else {
                    // webinar and meeting and product and bundle

                    Accounting::createAccounting($orderItem, $type);
                    TicketUser::useTicket($orderItem);

                    if (!empty($orderItem->product_id)) {
                        $updateProductOrderAfterSale = true;
                    }
                }

                // Set Sale After All Accounting
                $sale = Sale::createSales($orderItem, $order->payment_method);

                if (!empty($orderItem->reserve_meeting_id)) {
                    $reserveMeeting = ReserveMeeting::where('id', $orderItem->reserve_meeting_id)->first();
                    $reserveMeeting->update([
                        'sale_id' => $sale->id,
                        'reserved_at' => time()
                    ]);

                    $reserver = $reserveMeeting->user;

                    if ($reserver) {
                        $this->handleMeetingReserveReward($reserver);
                    }
                }

                if ($updateInstallmentOrderAfterSale) {
                    $this->updateInstallmentOrder($orderItem, $sale);
                }

                if ($updateProductOrderAfterSale) {
                    $this->updateProductOrder($sale, $orderItem);
                }
            }

            // Set Cashback Accounting For All Order Items
            $cashbackAccounting->setAccountingForOrderItems($order->orderItems);
        }

        Cart::emptyCart($order->user_id);
    }
}
