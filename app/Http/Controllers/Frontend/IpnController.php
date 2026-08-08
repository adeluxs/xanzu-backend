<?php

namespace App\Http\Controllers\Frontend;

use Anand\LaravelPaytmWallet\Facades\PaytmWallet;
use App\Enums\OrderStatus;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\CardProvider\Stripe\IpnService;
use App\Services\OrderService;
use App\Services\Payments\RayplusmoneyService;
use App\Traits\Payment;
use BTCPayServer\Client\Webhook;
use Cryptomus\Api\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Modules\Payment\Monnify\Monnify;
use Mollie\Laravel\Facades\Mollie;
use Payment\Securionpay\SecurionpayTxn;
use Payment\Stripe\StripeTxn;
use Payment\Rayplusmoney\RayplusmoneyTxn;
use Payment\Twocheckout\TwocheckoutTxn;
use RuntimeException;

class IpnController extends Controller
{
    use Payment;

    public function coinpaymentsIpn(Request $request)
    {
        $input = $request->all();
        $txn = $input['item_name'];
        $status = $input['status'];
        if ($status >= 100 || $status == 2) {
            self::paymentSuccess($txn);
        }
    }

    public function nowpaymentsIpn(Request $request)
    {
        $input = $request->all();
        $txn = $input['order_id'];
        $status = $input['payment_status'];
        if ($status == 'finished') {
            self::paymentSuccess($txn);
        }
    }

    public function cryptomusIpn(Request $request)
    {
        $data = $request->all();
        $gatewayInfo = gateway_info('cryptomus');
        $merchantId = $gatewayInfo->merchant_id;
        $paymentKey = $gatewayInfo->payment_key;
        $payment = Client::payment($paymentKey, $merchantId);
        $result = $payment->info($data);
        $txn = $result['order_id'];
        $status = $result['status'];
        if ($status == 'paid') {
            $transaction = Transaction::tnx($txn);
            if ($transaction->type == TxnType::Withdraw) {
                Txn::update($transaction->tnx, TxnStatus::Success, $transaction->user_id);
            } else {
                self::paymentSuccess($txn);
            }
        }
    }

    public function paypalIpn(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();
        $response = $provider->capturePaymentOrder($request['token']);
        if (isset($response['status']) && $response['status'] == 'COMPLETED') {
            $txn = $response['purchase_units'][0]['reference_id'];

            return self::paymentSuccess($txn);
        }

        if (session('order_id')) {
            Order::find(session('order_id'))->update(['payment_type' => TxnStatus::Cancelled->value, 'status' => OrderStatus::Cancelled->value]);
            notify()->warning(__('Payment Canceled'));
            app(OrderService::class)->dismissSession();

            return redirect(buyerSellerRoute('dashboard'))->setStatusCode(200);
        }

        return redirect()
            ->buyerSellerRoute('deposit.now')
            ->with('error', $response['message'] ?? 'Something went wrong.');
    }

    public function mollieIpn(Request $request)
    {
        $paymentId = $request->id;
        $payment = Mollie::api()->payments()->get($paymentId);
        if ($payment->isPaid()) {
            $ref = $request->reftrn;

            return self::paymentSuccess($ref);
        } elseif (! $payment->isPending()) {
            $order = Transaction::tnx($request->reftrn)->order;
            if ($payment->isCanceled()) {
                orderService()->setOrderCancelled($order);
            } else {
                orderService()->setOrderFailed($order);
            }
        }
    }

    public function perfectMoneyIpn(Request $request)
    {
        $ref = Crypt::decryptString($request->PAYMENT_ID);

        return self::paymentSuccess($ref);
    }

    public function paystackIpn()
    {
        $paymentDetails = Paystack::getPaymentData();
        if ($paymentDetails['data']['status'] == 'success') {
            $transactionId = $paymentDetails['data']['reference'];

            return self::paymentSuccess($transactionId);
        }

        return to_route('status.cancel');
    }

    public function flutterwaveIpn()
    {
        if (request()->query('status') !== null) {

            $credentials = gateway_info('flutterwave');

            // Check payment status
            $txnid = request()->query('tx_ref');
            $txnInfo = Transaction::tnx($txnid);

            if (request()->query('status') == 'cancelled') {
                $txnInfo->update([
                    'status' => TxnStatus::Failed,
                ]);
            } elseif (request()->query('status') == 'successful') {
                $txid = request()->query('transaction_id');

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$credentials->secret_key,
                ])->get("https://api.flutterwave.com/v3/transactions/{$txid}/verify");

                if ($response->successful()) {
                    $res = $response->json();
                    $amountPaid = $res['data']['charged_amount'];
                    $amountToPay = $res['data']['meta']['price'];

                    if ($amountPaid >= $amountToPay) {
                        return self::paymentSuccess($txnid, false);
                    }
                    $txnInfo->update([
                        'status' => TxnStatus::Failed,
                    ]);
                } else {
                    $txnInfo->update([
                        'status' => TxnStatus::Failed,
                    ]);
                }
            }
        }
    }

    public function coingateIpn(Request $request)
    {
        if ($request->status == 'paid') {
            self::paymentSuccess($request->order_id);
        } else {
            Txn::update($request->order_id, 'failed');
        }
    }

    public function monnifyIpn()
    {
        $ref = request('paymentReference');

        $trx = Session::get('deposit_tnx');
        $txnInfo = Transaction::tnx($trx);
        if (htmlspecialchars(request('paymentReference'))) {

            // Query the transaction reference from your DB call the method

            $monnify = new Monnify;

            $verify = $monnify->verifyTrans($txnInfo->approval_cause);

            if ($verify['paymentStatus'] == 'PAID') {
                $txnInfo->update([
                    'approval_cause' => 'none',
                ]);

                return self::paymentSuccess($ref, false);

                // Payment has been verified!

            }
            Txn::update($ref, 'failed');
        } else {
            Txn::update($ref, 'failed');
        }
    }

    public function nonHostedSecurionpayIpn(Request $request)
    {
        $depositTnx = Session::get('deposit_tnx');
        $tnxInfo = Transaction::tnx($depositTnx);
        $securionPay = new SecurionpayTxn($tnxInfo);

        return $securionPay->nonHostedPayment($request);
    }

    public function coinremitterIpn(Request $request)
    {
        $txn = $request->description;
        if ($request->status == 'paid') {
            return self::paymentSuccess($txn);
        }
    }

    public function btcpayIpn(Request $request)
    {
        $gatewayInfo = gateway_info('btcpayserver');
        $host = $gatewayInfo->host;
        $apiKey = $gatewayInfo->api_key;
        $storeId = $gatewayInfo->store_id;
        $webhookSecret = $gatewayInfo->webhook_secret;

        $raw_post_data = file_get_contents('php://input');
        $payload = json_decode($raw_post_data, false, 512, JSON_THROW_ON_ERROR);

        // Get the BTCPay signature header.
        $headers = getallheaders();
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'btcpay-sig') {
                $sig = $value;
            }
        }

        $webhookClient = new Webhook($host, $apiKey);

        // Validate the webhook request.
        throw_unless($webhookClient->isIncomingWebhookRequestValid($raw_post_data, $sig, $webhookSecret), RuntimeException::class, 'Invalid BTCPayServer payment notification message received - signature did not match.');

        $data = $request->all();
        self::paymentSuccess($data['metadata']['orderId']);
    }

    public function binanceIpn(Request $request)
    {
        header('Content-Type: application/json');
        $webhookResponse = $request->all();
        $returnCode = $webhookResponse['bizStatus'];
        $data = json_decode($webhookResponse['data'], true);
        if ($returnCode == 'SUCCESS') {
            self::paymentSuccess($data['productName']);
        }
    }

    public function blockchainIpn(Request $request)
    {

        $requestData = $request->all();
        $btcValue = $requestData['value'] / 100000000;
        $deposit = Transaction::tnx($requestData['txn']);
        if ($deposit->pay_amount >= $btcValue && $requestData['confirmations'] > 2 && $deposit->status == TxnStatus::Pending) {
            self::paymentSuccess($requestData['txn']);
        }
    }

    public function instamojoIpn(Request $request)
    {
        $payload = $request->all();
        $gatewayInfo = gateway_info('instamojo');
        $instamojoSignature = $payload['mac'];
        $expectedSignature = hash_hmac('sha1', json_encode($payload), $gatewayInfo->salt);
        if ($instamojoSignature == $expectedSignature && $request->payment_status == 'Credit') {
            self::paymentSuccess(($request->txn));
        }
    }

    public function paytmIpn(Request $request)
    {
        $transaction = PaytmWallet::with('receive');
        $txn = $transaction->getOrderId();
        if ($transaction->isSuccessful()) {
            self::paymentSuccess(($txn));
        }
    }

    public function razorpayIpn(Request $request)
    {
        $credentials = gateway_info('razorpay');
        $computedSignature = hash_hmac('sha256', $request->input('razorpay_order_id').'|'.$request->input('razorpay_payment_id'), $credentials->razorpay_secret);
        if ($computedSignature === $request->input('razorpay_signature')) {
            return $this->paymentSuccess($request->input('txn'));
        } elseif ($request->filled('razorpay_payment_id')) {
            $response = Http::withBasicAuth($credentials->razorpay_key, $credentials->razorpay_secret)->get('https://api.razorpay.com/v1/payments/'.$request->input('razorpay_payment_id'))->json();
            if (isset($response['status']) && $response['status'] == 'authorized') {
                return $this->paymentSuccess($request->input('txn'));
            }
        }
    }

    public function twocheckoutIpn(Request $request)
    {

        $gatewayInfo = gateway_info('twocheckout');
        $payload = $request->getContent();
        $expectedHash = hash_hmac('md5', $payload, $gatewayInfo->secret_word);
        $receivedHash = $request->header('X-2Checkout-Signature');
        if ($receivedHash == $expectedHash) {
            $this->paymentSuccess($request->li_0_product_id);
        }
    }

    public function stripeWebhook(Request $request)
    {
        app(IpnService::class)->handle($request);
    }

    public function rayplusmoneyIpn(Request $request)
    {
        $service = app(RayplusmoneyService::class);
        $transaction = $service->findCallbackTransaction(
            $request->all(),
            $request->query('reftrn')
        );

        if (! $transaction) {
            return response()->json(['status' => false, 'message' => 'Transaction not found'], 404);
        }

        try {
            $result = $service->reconcile($transaction);

            return response()->json([
                'status' => true,
                'message' => ($result['completed'] ?? false)
                    ? 'Transaction completed'
                    : (($result['failed'] ?? false) ? 'Transaction failed' : 'Transaction pending'),
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            \Log::error('RayPlusMoney callback reconciliation failed.', [
                'tnx' => $transaction->tnx,
                'error' => $e->getMessage(),
            ]);

            // Return a non-2xx response so the provider can retry the callback.
            return response()->json(['status' => false, 'message' => 'Verification failed'], 503);
        }
    }

}
