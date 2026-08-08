<?php

namespace App\Traits;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Models\DepositMethod;
use App\Models\Gateway;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\OrderService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Payment\Binance\BinanceTxn;
use Payment\Blockchain\BlockchainTxn;
use Payment\BlockIo\BlockIoTxn;
use Payment\Btcpayserver\BtcpayserverTxn;
use Payment\Cashmaal\CashmaalTxn;
use Payment\Coinbase\CoinbaseTxn;
use Payment\Coingate\CoingateTxn;
use Payment\Coinpayments\CoinpaymentsTxn;
use Payment\Coinremitter\CoinremitterTxn;
use Payment\Cryptomus\CryptomusTxn;
use Payment\Flutterwave\FlutterwaveTxn;
use Payment\Instamojo\InstamojoTxn;
use Payment\Mollie\MollieTxn;
use Payment\Monnify\MonnifyTxn;
use Payment\Nowpayments\NowpaymentsTxn;
use Payment\Paymongo\PaymongoTxn;
use Payment\Paypal\PaypalTxn;
use Payment\Paytm\PaytmTxn;
use Payment\Perfectmoney\PerfectmoneyTxn;
use Payment\Razorpay\RazorpayTxn;
use Payment\Rayplusmoney\RayplusmoneyTxn;
use Payment\Securionpay\SecurionpayTxn;
use Payment\Stripe\StripeTxn;
use Payment\Twocheckout\TwocheckoutTxn;

trait Payment
{
    use NotifyTrait, PlanTrait, ApiResponse;

    protected function depositAutoGateway($gateway, $txnInfo)
    {
        $txn = $txnInfo->tnx;
        Session::put('deposit_tnx', $txn);

        $depositMethod = DepositMethod::code($gateway)->with('gateway')->first();
        $gateway = $depositMethod?->gateway?->gateway_code ?? $depositMethod?->gateway_code ?? 'none';

        $gatewayTxn = self::gatewayMap($gateway, $txnInfo);
        if ($gatewayTxn) {
            return $gatewayTxn->deposit();
        }

        return self::paymentNotify($txn, 'pending');
    }

    protected function withdrawAutoGateway($gatewayCode, $txnInfo)
    {
        $gatewayTxn = self::gatewayMap($gatewayCode, $txnInfo);
        if ($gatewayTxn && config('app.demo') == 0) {
            $gatewayTxn->withdraw();
        }

        return to_buyerSellerRoute('payment.index');
    }

    protected function paymentNotify($tnx, $status)
    {
        $tnxInfo = Transaction::tnx($tnx);

        $status = ucfirst($status);

        if ($status == 'Pending') {

            $shortcodes = [
                '[[full_name]]' => $tnxInfo->user->full_name,
                '[[txn]]' => $tnxInfo->tnx,
                '[[gateway_name]]' => $tnxInfo->method,
                '[[deposit_amount]]' => $tnxInfo->amount,
                '[[site_title]]' => setting('site_title', 'global'),
                '[[site_url]]' => route('home'),
                '[[message]]' => '',
                '[[status]]' => $status,
            ];

            $this->sendNotify(
                setting('site_email', 'global'),
                'manual_payment_request',
                'Admin',
                $shortcodes,
                $tnxInfo->user->phone,
                $tnxInfo->user->id,
                route('admin.deposit.manual.pending')
            );
        }
        app(OrderService::class)->dismissSession();
        if ($status == 'Pending') {
            notify()->info(__('Your payment is pending for approval. We will notify you once it is approved.'));
        }

        return to_buyerSellerRoute('transactions');
    }

    protected function paymentSuccess($ref, $isRedirect = true)
    {
        $txnInfo = Transaction::tnx($ref);
        if (!$txnInfo) {
            return to_route('status.cancel');
        }
        (new Txn)->update($ref, TxnStatus::Success, $txnInfo->user_id);

        if ($txnInfo->type == TxnType::BnplInstallment) {
            orderService()->markBnplInstallmentTransactionPaid($txnInfo->fresh());

            if ($isRedirect) {
                return redirect(URL::temporarySignedRoute(
                    'status.success',
                    now()->addMinutes(2)
                ));
            }

            return $this->successResponse([
                'reftrn' => $ref,
            ], __('Payment successful.'));
        }

        if ($txnInfo->order_id) {
            $order = Order::find($txnInfo->order_id);
            if ($order) {
                orderService()->orderPaymentSuccess($order, !$isRedirect);
            }
        }


        if ($isRedirect) {
            return redirect(URL::temporarySignedRoute(
                'status.success',
                now()->addMinutes(2)
            ));
        }

        return $this->successResponse([
            'reftrn' => $ref,
        ], __('Payment successful.'));
        ;

    }

    // automatic gateway map snippet
    private function gatewayMap($gateway, $txnInfo)
    {
        $gatewayMap = [
            'paypal' => PaypalTxn::class,
            'stripe' => StripeTxn::class,
            'mollie' => MollieTxn::class,
            'perfectmoney' => PerfectmoneyTxn::class,
            'coinbase' => CoinbaseTxn::class,
            'paystack' => PaytmTxn::class,
            'voguepay' => BinanceTxn::class,
            'flutterwave' => FlutterwaveTxn::class,
            'cryptomus' => CryptomusTxn::class,
            'nowpayments' => NowpaymentsTxn::class,
            'securionpay' => SecurionpayTxn::class,
            'coingate' => CoingateTxn::class,
            'monnify' => MonnifyTxn::class,
            'coinpayments' => CoinpaymentsTxn::class,
            'paymongo' => PaymongoTxn::class,
            'coinremitter' => CoinremitterTxn::class,
            'btcpayserver' => BtcpayserverTxn::class,
            'binance' => BinanceTxn::class,
            'cashmaal' => CashmaalTxn::class,
            'blockio' => BlockIoTxn::class,
            'blockchain' => BlockchainTxn::class,
            'instamojo' => InstamojoTxn::class,
            'paytm' => PaytmTxn::class,
            'razorpay' => RazorpayTxn::class,
            'rayplusmoney' => RayplusmoneyTxn::class,
            'twocheckout' => TwocheckoutTxn::class,
        ];
        if (array_key_exists($gateway, $gatewayMap)) {
            return app($gatewayMap[$gateway], ['txnInfo' => $txnInfo]);
        }

        return false;
    }
}
