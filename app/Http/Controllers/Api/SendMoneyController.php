<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SendMoneyService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
                Log::warning('SendMoneyController@config: Unauthorized request', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $userBalance = $user->balance;
            $isMerchant = $user->user_type === 'merchant';

            $responseData = [
                'user_balance' => $userBalance,
                'transfer_status' => $isMerchant ? 1 : $user->transfer_status,
            ];

            Log::info('SendMoneyController@config: Success', [
                'user_id' => $user->id,
                'user_type' => $user->user_type,
                'balance' => $userBalance,
                'transfer_status' => $responseData['transfer_status'],
            ]);

            return $this->successResponse($responseData);
        } catch (\Throwable $th) {
            Log::error('SendMoneyController@config: Failed', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to load transfer config',
                'error' => config('app.debug') ? $th->getMessage() : null,
            ], 500);
        }
    }

    public function validateTransferRequest(Request $request)
    {
        try {
            $data = $request->all();
            $user = auth()->user();
            $isMerchant = $user?->user_type === 'merchant';

            Log::info('SendMoneyController@validateTransferRequest: Request received', [
                'user_id' => $user?->id,
                'user_type' => $user?->user_type,
                'recipient_phone' => $data['recipient_phone'] ?? null,
                'amount' => $data['amount'] ?? null,
            ]);

            $this->sendMoneyService->validate($data, $isMerchant);

            Log::info('SendMoneyController@validateTransferRequest: Validation passed', [
                'user_id' => $user?->id,
                'recipient_phone' => $data['recipient_phone'] ?? null,
            ]);

            return $this->successWithoutDataResponse(__('Validation passed.'));
        } catch (\Throwable $th) {
            Log::warning('SendMoneyController@validateTransferRequest: Validation failed', [
                'user_id' => auth()->id(),
                'error' => $th->getMessage(),
                'request_data' => $request->all(),
            ]);

            return $this->validationErrorResponse($th->getMessage());
        }
    }

    public function lookupRecipient(Request $request)
    {
        try {
            $phone = $request->input('phone');
            $user = auth()->user();

            Log::info('SendMoneyController@lookupRecipient: Request received', [
                'user_id' => $user?->id,
                'lookup_phone' => $phone,
            ]);

            $request->validate([
                'phone' => 'required|string',
            ]);

            $result = $this->sendMoneyService->lookupRecipient($phone);

            if (!$result) {
                Log::info('SendMoneyController@lookupRecipient: Recipient not found', [
                    'user_id' => $user?->id,
                    'lookup_phone' => $phone,
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Recipient not found.',
                    'data' => null,
                ], 200);
            }

            Log::info('SendMoneyController@lookupRecipient: Recipient found', [
                'user_id' => $user?->id,
                'lookup_phone' => $phone,
                'recipient_id' => $result['id'] ?? null,
                'recipient_name' => $result['full_name'] ?? null,
            ]);

            return $this->successResponse($result);
        } catch (\Throwable $th) {
            Log::error('SendMoneyController@lookupRecipient: Error', [
                'user_id' => auth()->id(),
                'lookup_phone' => $request->input('phone'),
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return $this->validationErrorResponse($th->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $user = auth()->user();
            $isMerchant = $user?->user_type === 'merchant';

            Log::info('SendMoneyController@store: Request received', [
                'user_id' => $user?->id,
                'user_type' => $user?->user_type,
                'recipient_phone' => $data['recipient_phone'] ?? null,
                'amount' => $data['amount'] ?? null,
            ]);

            $sendMoney = $this->sendMoneyService->sendMoney($data, $isMerchant);

            Log::info('SendMoneyController@store: Success', [
                'user_id' => $user?->id,
                'transaction_tnx' => $sendMoney['tnx'] ?? null,
                'recipient_phone' => $data['recipient_phone'] ?? null,
                'amount' => $data['amount'] ?? null,
            ]);

            return $this->successResponse($sendMoney, __('Send money request has been placed successfully.'));
        } catch (\Throwable $th) {
            Log::error('SendMoneyController@store: Failed', [
                'user_id' => auth()->id(),
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return $this->validationErrorResponse($th->getMessage());
        }
    }
}
