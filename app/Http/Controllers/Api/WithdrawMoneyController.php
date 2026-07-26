<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WithdrawService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class WithdrawMoneyController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request)
    {
        $service = app(WithdrawService::class);
        $withdrawAccount = $request->input('withdraw_account');
        $amount = $request->input('amount');
        try {

            $service->validate($request->all([
                'withdraw_account',
                'amount',
            ]));

            $txnInfo = $service->withdrawMoney($withdrawAccount, $amount);

            return $this->successWithoutDataResponse(__('Withdraw request submitted successfully'));
        } catch (\Throwable $th) {
            return $this->validationErrorResponse($th->getMessage());
        }
    }
}
