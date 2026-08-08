<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\TxnStatus;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\Payments\RayplusmoneyService;
use Illuminate\Http\Request;

class RayplusmoneyController extends Controller
{
    public function __construct(private RayplusmoneyService $rayplusmoney) {}

    public function success(Request $request)
    {
        $transaction = $this->resolveTransaction($request);
        if (! $transaction) {
            return response()->json(['status' => false, 'message' => __('Transaction not found.'), 'payment_status' => 'notcompleted'], 404);
        }

        $result = $this->rayplusmoney->reconcile($transaction);
        $completed = (bool) ($result['completed'] ?? false);
        $pending = (bool) ($result['pending'] ?? false);

        return response()->json([
            'status' => $completed,
            'message' => $completed
                ? __('Payment successful.')
                : ($pending ? __('Payment is still being confirmed.') : __('Payment was not completed.')),
            'payment_status' => $result['payment_status'] ?? 'pending',
            'transaction_status' => $result['status'] ?? TxnStatus::Pending->value,
            'data' => $result,
        ]);
    }

    public function cancel(Request $request)
    {
        $transaction = $this->resolveTransaction($request);
        if (! $transaction) {
            return response()->json(['status' => false, 'message' => __('Payment canceled.'), 'payment_status' => 'notcompleted']);
        }

        // A hosted-page cancel is not trusted as final gateway state. Keep a
        // verifiable state and allow a later provider callback to reconcile it.
        $this->rayplusmoney->markCancelled($transaction);

        return response()->json([
            'status' => false,
            'message' => __('Payment canceled by user.'),
            'payment_status' => 'cancelled',
            'transaction_status' => TxnStatus::Cancelled->value,
        ]);
    }

    private function resolveTransaction(Request $request): ?Transaction
    {
        $encrypted = (string) $request->query('reftrn', $request->input('reftrn', ''));
        if ($encrypted === '') {
            return null;
        }
        try {
            $tnx = decrypt($encrypted);
        } catch (\Throwable) {
            return null;
        }
        return Transaction::query()->where('tnx', $tnx)->first();
    }
}
