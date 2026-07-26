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
        $user = auth()->user();
        $userBalance = $user->balance;

        return $this->successResponse([
            'user_balance' => $userBalance,
            'transfer_status' => $user->transfer_status,
        ]);
    }

    public function validate(Request $request)
    {
        try {
            $data = $request->all();
            $this->sendMoneyService->validate($data, false);

            return $this->successWithoutDataResponse(__('Validation passed.'));
        } catch (\Throwable $th) {
            return $this->validationErrorResponse($th->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $sendMoney = $this->sendMoneyService->sendMoney($data, false);

            return $this->successResponse($sendMoney, __('Send money request has been placed successfully.'));
        } catch (\Throwable $th) {
            return $this->validationErrorResponse($th->getMessage());
        }
    }
}
