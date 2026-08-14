<?php

namespace App\Http\Controllers\Api;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Models\DepositMethod;
use App\Models\Transaction;
use App\Services\AddMoneyService;
use App\Services\Payments\RayplusmoneyService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AddMoneyController extends Controller
{
    use ApiResponse;

    public function __construct(private AddMoneyService $addMoneyService) {}

    public function index(Request $request)
    {
        $methods = DepositMethod::query()
            ->with('gateway')
            ->when($request->filled('currency'), fn ($query) => $query->where('currency', strtoupper((string) $request->currency)))
            ->where('status', 1)
            ->when($request->boolean('auto'), fn ($query) => $query->auto())
            ->get()
            ->filter(function (DepositMethod $method) {
                $rate = (float) $method->rate;
                $minimum = (float) $method->minimum_deposit;
                $maximum = (float) $method->maximum_deposit;
                if (! is_finite($rate) || $rate <= 0 || $minimum < 0 || ($maximum > 0 && $maximum < $minimum)) {
                    return false;
                }

                if ($method->type !== 'auto') {
                    return true;
                }

                if (! $method->gateway || (int) $method->gateway->status !== 1) {
                    return false;
                }

                // RayPlusMoney's pay-in specification requires XOF. Do not
                // expose a misconfigured method to mobile users only to fail
                // after they enter an amount.
                if (str_starts_with(strtolower((string) $method->gateway_code), RayplusmoneyService::GATEWAY_CODE)) {
                    return strtoupper((string) $method->currency) === 'XOF';
                }

                return true;
            })
            ->values()
            ->map(function (DepositMethod $method) {
                $method->logo = asset($method->logo ?? $method->gateway?->logo ?? 'frontend/default-old/images/payment/balance.png');
                $method->field_options = dynamicFieldKeyFormat($method->field_options ?? []);
                return $method;
            });

        return $this->successResponse($methods);
    }

    public function store(Request $request)
    {
        $requestId = (string) ($request->attributes->get('request_id') ?: Str::uuid());
        $request->attributes->set('request_id', $requestId);
        Log::info('ADD_MONEY_REQUEST', [
            'request_id' => $requestId,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'gateway' => $request->input('gateway'),
            'amount' => $request->input('amount'),
            'custom_field_names' => array_keys((array) $request->input('customFields', [])),
        ]);

        try {
            $this->addMoneyService->validate($request->all());
            $response = $this->addMoneyService->process(
                $request->amount,
                $request->gateway,
                $request->customFields ?? null
            );

            Log::info('ADD_MONEY_RESPONSE', [
                'request_id' => $requestId,
                'status_code' => 200,
                'user_id' => auth()->id(),
                'gateway' => $request->input('gateway'),
                'transaction' => data_get($response, 'tnx') ?? data_get($response, 'transaction.tnx'),
            ]);

            return $this->successResponse($response, __('Deposit request successful.'));
        } catch (PaymentGatewayException $e) {
            Log::warning('ADD_MONEY_GATEWAY_ERROR', [
                'request_id' => $requestId,
                'status_code' => 502,
                'user_id' => auth()->id(),
                'gateway' => $request->input('gateway'),
                'error_code' => $e->errorCode(),
                ...$e->logContext(),
            ]);

            return $this->errorResponse(
                $e->getMessage(),
                502,
                $e->publicContext(),
                null,
                $e->errorCode(),
            );
        } catch (ValidationException $e) {
            Log::warning('ADD_MONEY_VALIDATION_ERROR', [
                'request_id' => $requestId,
                'status_code' => 422,
                'user_id' => auth()->id(),
                'gateway' => $request->input('gateway'),
                'fields' => array_keys($e->errors()),
            ]);
            return $this->validationErrorResponse($e->errors());
        } catch (\Throwable $e) {
            Log::error('ADD_MONEY_ERROR', [
                'request_id' => $requestId,
                'status_code' => 500,
                'user_id' => auth()->id(),
                'gateway' => $request->gateway,
                'exception' => get_class($e),
                'exception_code' => $e->getCode(),
            ]);
            return $this->errorResponse(__('Unable to start the deposit. Please try again.'), 500);
        }
    }

    public function addMoneyHistory(Request $request)
    {
        $transactions = Transaction::query()
            ->where('user_id', auth()->id())
            ->whereIn('type', [TxnType::Deposit->value, TxnType::ManualDeposit->value])
            ->latest('id')
            ->paginate(min(max((int) $request->integer('per_page', 15), 1), 100));

        return $this->successResponse($transactions);
    }

    public function status(string $tnx, RayplusmoneyService $rayplusmoney)
    {
        $transaction = Transaction::query()
            ->where('user_id', auth()->id())
            ->where('tnx', $tnx)
            ->whereIn('type', [TxnType::Deposit->value, TxnType::ManualDeposit->value])
            ->first();

        if (! $transaction) {
            return $this->notFoundResponse(__('Deposit transaction not found.'));
        }

        $storedStatus = $transaction->status instanceof TxnStatus ? $transaction->status->value : (string) $transaction->status;
        $result = [
            'tnx' => $transaction->tnx,
            'status' => $storedStatus,
            'completed' => $storedStatus === TxnStatus::Success->value,
            'failed' => $storedStatus === TxnStatus::Failed->value,
            'pending' => in_array($storedStatus, [TxnStatus::Pending->value, TxnStatus::Cancelled->value], true),
            'provider' => strtolower((string) $transaction->method),
        ];

        $methodCode = strtolower(trim((string) $transaction->method));
        // Deposit method codes may be currency-qualified (for example
        // rayplusmoney-xof). Reconcile every RayPlus variant through the same
        // provider service instead of leaving valid top-ups permanently pending.
        if (str_starts_with($methodCode, RayplusmoneyService::GATEWAY_CODE) && ! $result['completed']) {
            $result = $rayplusmoney->reconcile($transaction);
        }

        return $this->successResponse($result, __('Deposit status retrieved.'));
    }
}
