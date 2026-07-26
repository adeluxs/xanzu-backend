<?php

namespace App\Http\Controllers\Api;

use App\Enums\SendMoneyStatus;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Http\Controllers\Controller;
use App\Http\Resources\BeneficiaryResource;
use App\Http\Resources\TransactionResource;
use App\Models\Beneficiary;
use App\Models\Notification;
use App\Models\SendMoney;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Handle the incoming request.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $hour = now()->hour;

        if ($hour < 12) {
            $greeting = __('Good morning');
        } elseif ($hour < 18) {
            $greeting = __('Good afternoon');
        } else {
            $greeting = __('Good evening');
        }

        $latestSendMoney = Beneficiary::where('user_id', $user->id)->latest()->take(4)->get()->map(function ($beneficiary) use ($user) {
            $latestTxn = $beneficiary->latestTransaction($user->id);
            $latestAmount = $latestTxn ? $latestTxn->transaction->amount : null;

            return [
                'id' => $beneficiary->id,
                'name' => $beneficiary->name,
                'email' => $beneficiary->email,
                'phone' => $beneficiary->phone,
                'latest_transaction_amount' => $latestAmount,
                'latest_transaction_date' => $latestTxn ? $latestTxn->created_at->format('Y-m-d') : null,
                'beneficiary' => new BeneficiaryResource($beneficiary),
            ];
        });

        return $this->successResponse([
            'balance' => $user->balance,
            'latest_transactions' => TransactionResource::collection($user->transaction()->latest()->paginate(5)),
            'total_send_money' => number_format(Transaction::where('user_id', $user->id)->where('type', TxnType::SendMoney)->where('status', TxnStatus::Success)->sum('amount'), 8),
            'total_transactions' => $user->transaction()->count(),
            'pending_send_money' => SendMoney::where('user_id', $user->id)->where('status', SendMoneyStatus::Pending)->count(),
            'rejected_send_money' => SendMoney::where('user_id', $user->id)->where('status', SendMoneyStatus::Rejected)->count(),
            'unseen_notifications_count' => $unreadCount = Notification::where('for', 'user')
                ->where('user_id', $user->id)
                ->where('read', 0)
                ->count(),
            'greetings' => $greeting,
            'recent_activity' => $latestSendMoney,
        ]);
    }

    public function getUser(Request $request)
    {
        $user = $request->user();
        $user->is_email_verified = $user->hasVerifiedEmail();
        $user->balance = number_format($user->balance, 2);

        $userFormatted = $user->append('avatar_path')->except('password', 'remember_token', 'badge_id', 'badges', 'updated_at', 'email_verified_at');
        $userFormatted['avatar'] = $user->avatar_path;
        $userFormatted['bnpl_eligible'] = $user->isBnplEligible();

        return $this->successResponse($userFormatted);
    }
}
