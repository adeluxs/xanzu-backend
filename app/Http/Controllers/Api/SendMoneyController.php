<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SendMoneyService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SendMoneyController extends Controller
{
    use ApiResponse;

    public function __construct(
        private SendMoneyService $sendMoneyService
    ) {
    }

    public function config(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $userBalance = $user->balance;
            $isMerchant = $user->user_type === 'merchant';

            return $this->successResponse([
                'user_balance' => $userBalance,
                'transfer_status' => $isMerchant ? 1 : $user->transfer_status,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to load transfer config',
                'error' => config('app.debug') ? $th->getMessage() : null,
            ], 500);
        }
    }

    public function validate(Request $request)
    {
        try {
            $data = $request->all();
            $isMerchant = auth()->user()->user_type === 'merchant';
            $this->sendMoneyService->validate($data, $isMerchant);

            return $this->successWithoutDataResponse(__('Validation passed.'));
        } catch (\Throwable $th) {
            return $this->validationErrorResponse($th->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $isMerchant = auth()->user()->user_type === 'merchant';
            $sendMoney = $this->sendMoneyService->sendMoney($data, $isMerchant);

            return $this->successResponse($sendMoney, __('Send money request has been placed successfully.'));
        } catch (\Throwable $th) {
            return $this->validationErrorResponse($th->getMessage());
        }
    }
}
