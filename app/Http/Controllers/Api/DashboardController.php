<?php

namespace App\Http\Controllers\Api;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Notification;
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
        $greeting = $hour < 12 ? __('Good morning') : ($hour < 18 ? __('Good afternoon') : __('Good evening'));

        $summary = Transaction::query()
            ->where('user_id', $user->id)
            ->selectRaw('COUNT(*) as total_transactions')
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? AND status = ? THEN amount ELSE 0 END), 0) as total_send_money', [TxnType::Transfer->value, TxnStatus::Success->value])
            ->first();

        $recentTransfers = Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', TxnType::Transfer->value)
            ->with('fromUser:id,first_name,last_name,username,phone')
            ->latest('id')
            ->limit(4)
            ->get();

        return $this->successResponse([
            'balance' => $user->balance,
            'latest_transactions' => TransactionResource::collection(
                $user->transaction()->latest('id')->paginate(5)
            ),
            'total_send_money' => number_format((float) ($summary->total_send_money ?? 0), 8),
            'total_transactions' => (int) ($summary->total_transactions ?? 0),
            'unseen_notifications_count' => Notification::query()
                ->where('for', 'user')
                ->where('user_id', $user->id)
                ->where('read', 0)
                ->count(),
            'greetings' => $greeting,
            'recent_activity' => TransactionResource::collection($recentTransfers),
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
