<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Facades\Txn\Txn;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use App\Traits\NotifyTrait;
use App\Traits\Payment;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;

class StatusController extends Controller
{
    use ApiResponse;
    use NotifyTrait;
    use Payment;

    public function __destruct()
    {
    }

    public function pending(Request $request)
    {
        if (needJsonResponse()) {
            $resolved = $this->resolveEncryptedReftrn($request);
            if (isset($resolved['response'])) {
                return $resolved['response'];
            }

            if (!Transaction::tnx($resolved)) {
                return $this->jsonTransactionNotFound('pending', $resolved['reftrn']);
            }

            return $this->jsonStatusResponse('pending', __('Payment Pending, Status will be updated soon'), $resolved['reftrn']);
        }

        $depositTnx = Session::get('deposit_tnx');

        if (session('order_id')) {
            notify()->warning(__('Payment Pending, Status will be updated soon'));

            return redirect(buyerSellerRoute('dashboard'))->setStatusCode(200);
        }

        return self::paymentNotify($depositTnx, 'pending');
    }

    public function success(Request $request)
    {
        if (needJsonResponse()) {
            $resolved = $this->resolveEncryptedReftrn($request);
            if (isset($resolved['response'])) {
                return $resolved['response'];
            }
            if (!Transaction::tnx($resolved['reftrn'])) {
                return $this->jsonTransactionNotFound('failed', $resolved['reftrn']);
            }

            self::paymentSuccess($resolved['reftrn'], false);


            return $this->jsonStatusResponse('success', __('Payment Successful'), $resolved['reftrn']);
        }

        $decryptedRef = null;
        if (isset($request->reftrn)) {
            try {
                $decryptedRef = $this->resolveEncryptedReftrn($request->reftrn);

                return self::paymentSuccess($decryptedRef);
            } catch (DecryptException $e) {
                return $this->errorResponse(__('Payment Failed'), 400);
            }
        }

    }

    public function cancel(Request $request)
    {
        if (needJsonResponse()) {
            $resolved = $this->resolveEncryptedReftrn($request);
            if (isset($resolved['response'])) {
                return $resolved['response'];
            }

            $transaction = Transaction::tnx($resolved['reftrn']);
            if (!$transaction) {
                return $this->jsonTransactionNotFound('canceled', $resolved['reftrn']);
            }

            $this->cancelTransaction($resolved['reftrn'], $transaction, false);

            return $this->jsonStatusResponse('canceled', __('Payment Canceled'), $resolved['reftrn']);
        }

        $trx = Session::get('deposit_tnx');
        if (!$trx && $request->reftrn) {
            try {
                $trx = decrypt($request->reftrn);
            } catch (DecryptException $e) {
                return $this->redirectWithWarning(__('Payment Canceled'));
            }
        }

        $transaction = Transaction::tnx($trx);
        if (!$transaction) {
            return $this->redirectWithWarning(__('Payment Canceled'));
        }

        $this->cancelTransaction($trx, $transaction, true);

        return $this->redirectWithWarning(__('Payment Canceled'));
    }

    private function resolveEncryptedReftrn(Request|string $request): array
    {
        $reftrn = is_string($request) ? $request : $request->reftrn;

        if (!$reftrn) {
            return [
                'response' => $this->errorResponse(__('Reference transaction not provided.'), 400),
            ];
        }

        try {
            $decryptedRef = decrypt($reftrn);

            return [
                'reftrn' => $decryptedRef,
            ];
        } catch (DecryptException $e) {
            return [
                'response' => $this->errorResponse(__('Invalid reference transaction.'), 400),
            ];
        }
    }

    private function jsonTransactionNotFound(string $status, string $reftrn)
    {
        return $this->errorResponse(__('Transaction not found.'), 404, [
            'status' => $status,
            'reftrn' => $reftrn,
        ]);
    }

    private function jsonStatusResponse(string $status, string $message, string $reftrn)
    {
        return $this->successResponse([
            'status' => $status,
            'reftrn' => $reftrn,
        ], $message);
    }

    private function redirectWithWarning(string $message)
    {
        return $this->errorResponse($message);
    }

    private function cancelTransaction(string $trx, $transaction, bool $preferSessionOrder = false): void
    {
        (new Txn)->update($trx, TxnStatus::Cancelled->value);

        if ($transaction->type == TxnType::BnplInstallment) {
            orderService()->markBnplInstallmentTransactionRejected($transaction);
            return;
        }

        $order = $preferSessionOrder ? Order::find(session('order_id')) : null;
        $order = $order ?? $transaction->order;
        if ($order) {
            orderService()->setOrderCancelled($order);
        }
    }
}
