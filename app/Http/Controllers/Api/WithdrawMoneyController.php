<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WithdrawService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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

            $result = $service->withdrawMoney($withdrawAccount, (float) $amount);
            $transaction = $result['transaction'];

            return $this->successResponse([
                'transaction' => [
                    'tnx' => $transaction->tnx,
                    'status' => $transaction->status?->value ?? (string) $transaction->status,
                    'amount' => (float) $transaction->amount,
                    'charge' => (float) $transaction->charge,
                    'final_amount' => (float) $transaction->final_amount,
                    'pay_amount' => (float) $transaction->pay_amount,
                    'pay_currency' => $transaction->pay_currency,
                ],
                'gateway' => $result['gateway'],
            ], __('Withdraw request submitted successfully'));
        } catch (ValidationException $th) {
            return $this->validationErrorResponse($th->errors());
        } catch (\Throwable $th) {
            report($th);
            return $this->errorResponse(__('Sorry! Something went wrong. Please try again.'), 500);
        }
    }
}
