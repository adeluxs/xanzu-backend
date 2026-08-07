<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SendMoneyService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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

                return $this->unauthorizedResponse('Unauthorized');
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

            return $this->errorResponse('Failed to load transfer config', 500);
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

            return $this->successWithoutDataResponse('Validation passed.');
        } catch (ValidationException $e) {
            $fieldErrors = $this->formatFieldErrors($e);

            Log::warning('SendMoneyController@validateTransferRequest: Validation failed', [
                'user_id' => auth()->id(),
                'errors' => $fieldErrors,
                'request_data' => $request->all(),
            ]);

            return $this->structuredErrorResponse(
                message: $fieldErrors['general'] ?? 'Validation failed.',
                errorCode: $this->mapValidationErrorCode($fieldErrors),
                fieldErrors: $fieldErrors,
                statusCode: 422
            );
        } catch (\Throwable $th) {
            Log::error('SendMoneyController@validateTransferRequest: Unexpected error', [
                'user_id' => auth()->id(),
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return $this->errorResponse('An unexpected error occurred during validation.', 500);
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

                return $this->structuredErrorResponse(
                    message: 'Recipient not found.',
                    errorCode: 'RECIPIENT_NOT_FOUND',
                    statusCode: 200
                );
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

            return $this->errorResponse('An unexpected error occurred during lookup.', 500);
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

            return $this->successResponse($sendMoney, 'Send money request has been placed successfully.');
        } catch (ValidationException $e) {
            $fieldErrors = $this->formatFieldErrors($e);

            Log::warning('SendMoneyController@store: Validation failed', [
                'user_id' => auth()->id(),
                'errors' => $fieldErrors,
                'request_data' => $request->all(),
            ]);

            return $this->structuredErrorResponse(
                message: $fieldErrors['general'] ?? 'Transfer failed.',
                errorCode: $this->mapValidationErrorCode($fieldErrors),
                fieldErrors: $fieldErrors,
                statusCode: 422
            );
        } catch (\Throwable $th) {
            Log::error('SendMoneyController@store: Failed', [
                'user_id' => auth()->id(),
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return $this->errorResponse('An unexpected error occurred while processing your transfer.', 500);
        }
    }

    private function formatFieldErrors(ValidationException $e): array
    {
        $fieldErrors = [];
        $generalErrors = [];

        foreach ($e->errors()->all() as $error) {
            $generalErrors[] = $error;
        }

        foreach ($e->errors()->getMessages() as $field => $messages) {
            $fieldErrors[$field] = $messages[0] ?? $messages[0];
        }

        $fieldErrors['general'] = !empty($generalErrors)
            ? implode(', ', $generalErrors)
            : 'Validation failed.';

        return $fieldErrors;
    }

    private function mapValidationErrorCode(array $fieldErrors): string
    {
        $keys = array_keys($fieldErrors);
        $lowerKeys = array_map('strtolower', $keys);

        if (in_array('transfer', $lowerKeys)) {
            $message = strtolower($fieldErrors['transfer'] ?? '');
            if (str_contains($message, 'temporarily disabled globally')) {
                return 'TRANSFER_GLOBAL_DISABLED';
            }
            if (str_contains($message, 'not enabled for your account')) {
                return 'TRANSFER_USER_DISABLED';
            }
            if (str_contains($message, 'kyc')) {
                return 'TRANSFER_KYC_REQUIRED';
            }
            if (str_contains($message, 'limit of')) {
                return 'LIMIT_EXCEEDED';
            }
            return 'TRANSFER_DISABLED';
        }

        if (in_array('recipient_phone', $lowerKeys)) {
            $message = strtolower($fieldErrors['recipient_phone'] ?? '');
            if (str_contains($message, 'not found')) {
                return 'RECIPIENT_NOT_FOUND';
            }
            if (str_contains($message, 'not active')) {
                return 'RECIPIENT_INACTIVE';
            }
            if (str_contains($message, 'yourself')) {
                return 'SELF_TRANSFER';
            }
        }

        if (in_array('amount', $lowerKeys)) {
            $message = strtolower($fieldErrors['amount'] ?? '');
            if (str_contains($message, 'balance')) {
                return 'INSUFFICIENT_BALANCE';
            }
            if (str_contains($message, 'limit')) {
                return 'LIMIT_EXCEEDED';
            }
            if (str_contains($message, 'min') || str_contains($message, 'numeric')) {
                return 'INVALID_AMOUNT';
            }
        }

        if (isset($fieldErrors['recipient_phone']) || isset($fieldErrors['amount'])) {
            return 'VALIDATION_FAILED';
        }

        return 'UNKNOWN_ERROR';
    }

    protected function structuredErrorResponse(
        string $message,
        string $errorCode = 'UNKNOWN_ERROR',
        array $fieldErrors = [],
        int $statusCode = 400,
        ?array $meta = null
    ) {
        $defaultMeta = [
            'actionable' => true,
            'retryable' => false,
            'retry_after_seconds' => null,
            'field_errors' => $fieldErrors,
            'suggested_action' => $this->getSuggestedAction($errorCode),
        ];

        return response()->json([
            'status' => false,
            'status_code' => $errorCode,
            'message' => $message,
            'data' => null,
            'meta' => array_merge($defaultMeta, $meta ?? []),
        ], $statusCode);
    }

    private function getSuggestedAction(string $errorCode): string
    {
        return match ($errorCode) {
            'RECIPIENT_NOT_FOUND' => 'Verify the recipient phone number and try again.',
            'RECIPIENT_INACTIVE' => 'The recipient account is currently inactive.',
            'SELF_TRANSFER' => 'You cannot send money to your own account.',
            'TRANSFER_GLOBAL_DISABLED' => 'Transfers are temporarily disabled. Please contact support or try again later.',
            'TRANSFER_USER_DISABLED' => 'Contact support to enable transfers on your account.',
            'TRANSFER_KYC_REQUIRED' => 'Complete KYC verification to enable transfers.',
            'TRANSFER_DISABLED' => 'Contact support to enable transfers on your account.',
            'INSUFFICIENT_BALANCE' => 'Add funds to your wallet or enter a smaller amount.',
            'INVALID_AMOUNT' => 'Enter a valid amount within the allowed range.',
            'LIMIT_EXCEEDED' => 'You have exceeded your transfer limit. Please try again later or contact support.',
            'VALIDATION_FAILED' => 'Check the entered details and try again.',
            'RATE_LIMITED' => 'Too many attempts. Please wait and try again.',
            default => 'Please try again later or contact support if the problem persists.',
        };
    }
}
