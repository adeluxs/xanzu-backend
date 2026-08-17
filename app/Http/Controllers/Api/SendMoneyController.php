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
                    'request_id' => $request->attributes->get('request_id'),
                    'ip' => $request->ip(),
                ]);

                return $this->unauthorizedResponse('Unauthorized');
            }

            $responseData = $this->sendMoneyService->transferConfig($user);

            Log::info('TRANSFER_CONFIG_RESOLVED', [
                'request_id' => $request->attributes->get('request_id'),
                'user_id' => $user->id,
                'user_type' => $responseData['user_type'],
                'transfer_status' => $responseData['transfer_status'],
                'global_status' => $responseData['global_status'],
                'role_status' => $responseData['role_status'],
                'user_status' => $responseData['user_status'],
                'kyc_required' => $responseData['kyc_required'],
                'kyc_verified' => $responseData['kyc_verified'],
                'disabled_reason' => $responseData['disabled_reason'],
            ]);

            return $this->successResponse($responseData);
        } catch (\Throwable $th) {
            Log::error('SendMoneyController@config: Failed', [
                'request_id' => $request->attributes->get('request_id'),
                'user_id' => auth()->id(),
                'error_type' => get_class($th),
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

            $validation = $this->sendMoneyService->validate($data, $isMerchant);

            return $this->successResponse([
                'recipient_id' => $validation['recipient']->id,
                'recipient_phone' => $validation['recipient_phone'],
                'amount' => $validation['amount'],
            ], 'Validation passed.');
        } catch (ValidationException $e) {
            $fieldErrors = $this->formatFieldErrors($e);

            Log::warning('SendMoneyController@validateTransferRequest: Validation failed', [
                'request_id' => $request->attributes->get('request_id'),
                'user_id' => auth()->id(),
                'fields' => array_keys($e->errors()),
            ]);

            return $this->structuredErrorResponse(
                message: $fieldErrors['general'] ?? 'Validation failed.',
                errorCode: $this->mapValidationErrorCode($fieldErrors),
                fieldErrors: $fieldErrors,
                statusCode: 422
            );
        } catch (\Throwable $th) {
            Log::error('SendMoneyController@validateTransferRequest: Unexpected error', [
                'request_id' => $request->attributes->get('request_id'),
                'user_id' => auth()->id(),
                'error_type' => get_class($th),
            ]);

            return $this->errorResponse('An unexpected error occurred during validation.', 500);
        }
    }

    public function lookupRecipient(Request $request)
    {
        try {
            $phone = $request->input('phone');
            $user = auth()->user();

            $request->validate([
                'phone' => 'required|string',
            ]);

            $result = $this->sendMoneyService->lookupRecipient($phone);

            if (!$result) {

                return $this->structuredErrorResponse(
                    message: 'Recipient not found.',
                    errorCode: 'RECIPIENT_NOT_FOUND',
                    statusCode: 404
                );
            }

            return $this->successResponse($result);
        } catch (ValidationException $e) {
            $fieldErrors = $this->formatFieldErrors($e);

            return $this->structuredErrorResponse(
                message: $fieldErrors['general'] ?? 'Validation failed.',
                errorCode: 'VALIDATION_FAILED',
                fieldErrors: $fieldErrors,
                statusCode: 422
            );
        } catch (\Throwable $th) {
            Log::error('SendMoneyController@lookupRecipient: Error', [
                'request_id' => $request->attributes->get('request_id'),
                'user_id' => auth()->id(),
                'has_lookup_phone' => $request->filled('phone'),
                'error_type' => get_class($th),
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

            $sendMoney = $this->sendMoneyService->sendMoney($data, $isMerchant);

            return $this->successResponse($sendMoney, 'Send money request has been placed successfully.');
        } catch (ValidationException $e) {
            $fieldErrors = $this->formatFieldErrors($e);

            Log::warning('SendMoneyController@store: Validation failed', [
                'request_id' => $request->attributes->get('request_id'),
                'user_id' => auth()->id(),
                'fields' => array_keys($e->errors()),
            ]);

            return $this->structuredErrorResponse(
                message: $fieldErrors['general'] ?? 'Transfer failed.',
                errorCode: $this->mapValidationErrorCode($fieldErrors),
                fieldErrors: $fieldErrors,
                statusCode: 422
            );
        } catch (\Throwable $th) {
            Log::error('SendMoneyController@store: Failed', [
                'request_id' => $request->attributes->get('request_id'),
                'user_id' => auth()->id(),
                'error_type' => get_class($th),
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
            $fieldErrors[$field] = $messages[0] ?? 'Validation failed.';
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
            if (str_contains($message, 'disabled for merchant accounts')) {
                return 'TRANSFER_MERCHANT_DISABLED';
            }
            if (str_contains($message, 'disabled for buyer accounts')) {
                return 'TRANSFER_BUYER_DISABLED';
            }
            if (str_contains($message, 'kyc')) {
                return 'TRANSFER_KYC_REQUIRED';
            }
            if (str_contains($message, 'limit of') || str_contains($message, 'limit') || str_contains($message, 'reference')) {
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
            if (str_contains($message, 'minimum') || str_contains($message, 'maximum') || str_contains($message, 'min') || str_contains($message, 'max') || str_contains($message, 'numeric')) {
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
            'success' => false,
            'status' => false,
            'status_code' => $errorCode,
            'http_status' => $statusCode,
            'code' => $errorCode,
            'message' => $message,
            'data' => null,
            'errors' => $fieldErrors,
            'meta' => array_merge($defaultMeta, $meta ?? []),
            'request_id' => request()->attributes->get('request_id'),
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
