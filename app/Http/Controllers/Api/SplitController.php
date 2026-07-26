<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CreditLimitSplit;
use App\Models\Listing;
use App\Services\BnplScheduleService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class SplitController extends Controller
{
    use ApiResponse;

    /**
     * Return active split configurations.
     * GET /api/user/splits
     *
     * Optional query:
     * amount - when provided, returns split preview with installment schedule and dates.
     */
    public function index(Request $request)
    {
        $amount = $request->query('amount');
        if (!is_null($amount) && (!is_numeric($amount) || (float) $amount <= 0)) {
            return $this->validationErrorResponse(__('The amount query must be a number greater than 0.'));
        }

        $splitsQuery = CreditLimitSplit::active()->latest();

        if (is_null($amount)) {
            $splits = $splitsQuery->get()->map(function (CreditLimitSplit $s) {
                return [
                    'id' => $s->id,
                    'total_split' => (int) $s->total_split,
                    'payment_interval_amount' => (int) $s->payment_interval_amount,
                    'payment_interval_type' => $s->payment_interval_type,
                    'interest_rate_amount' => (float) $s->interest_rate_amount,
                    'interest_rate_type' => $s->interest_rate_type,
                    'delay_fine_amount' => (float) $s->delay_fine_amount,
                    'delay_fine_type' => $s->delay_fine_type,
                ];
            });

            return $this->successResponse(['splits' => $splits, 'max_split' => CreditLimitSplit::active()->max('total_split')], 'Splits fetched successfully');
        }

        $scheduleService = app(BnplScheduleService::class);
        $takeInitialInstallment = (bool) setting('bnpl_take_initial_installment', 'permission');
        $amount = round((float) $amount, 2);
        $listings = $this->loadPreviewListings($request->query('listing_id'));

        $amountForPreview = $amount;
        $discountRules = [];
        $couponCode = $request->query('coupon_code');
        if (is_string($couponCode) && $couponCode !== '') {
            $coupon = Coupon::query()->where('code', $couponCode)->first();
            if ($coupon && $coupon->is_valid) {
                $discountRules[] = [
                    'type' => (string) $coupon->discount_type,
                    'value' => (float) $coupon->discount_value,
                ];
            }
        }

        $discountAmount = $this->calculateTotalDiscount($amount, $discountRules);

        $shippingChargeAmount = $this->calculatePreviewShippingCharge($amount, $listings);
        $amountForPreview = round($amount + $shippingChargeAmount - $discountAmount, 2);


        $splits = CreditLimitSplit::active()->latest()->get()->map(function (CreditLimitSplit $s) use ($scheduleService, $amountForPreview, $takeInitialInstallment) {
            $preview = $scheduleService->buildSchedulePreview($s, $amountForPreview, $takeInitialInstallment);
            $installments = collect($preview['installments']);
            $firstPayable = $installments->firstWhere('is_upfront', false) ?? $installments->first();
            $installmentAmount = (float) ($firstPayable['total_due_amount'] ?? 0);
            $splitCount = (int) $preview['split_count'];
            $currencySymbol = (string) setting('currency_symbol', 'global');
            $duration = $this->estimatePlanDuration(
                $splitCount,
                (int) $s->payment_interval_amount,
                (string) $s->payment_interval_type
            );
            $headerText = $splitCount . ' ' . Str::plural('Payment', $splitCount);
            $amountText = formatCurrency($this->toCompactAmountString((float) $preview['total_payable']), $currencySymbol);
            $planUnit = Str::plural($duration['unit'], (int) $duration['value']);
            $planText = $splitCount . ' Instalment for ' . $duration['value'] . ' ' . $planUnit;
            $interestText = (float) $preview['total_fees'] > 0
                ? 'Total fees: ' . $currencySymbol . $this->toAmountString((float) $preview['total_fees'])
                : 'Always interest-free';

            return [
                'id' => $s->id,
                'header_text' => $headerText,
                'interest_text' => $interestText,
                'amount_text' => $amountText,
                'plan_text' => $planText,
                'subtitle' => (int) $preview['split_count'] . ' Payments for total ' . setting('currency_symbol', 'global') . $this->toAmountString((float) $preview['total_payable']),
                'always_interest_free' => (float) $preview['total_fees'] <= 0,
                'installment_amount' => $installmentAmount,
                'total_fees' => (float) $preview['total_fees'],
                'total_payable' => (float) $preview['total_payable'],
                'initial_paid_amount' => (float) $preview['initial_paid_amount'],
                'final_amount_to_pay' => (float) $preview['final_amount_to_pay'],
                'installments' => $installments->map(function ($installment) {
                    $dueAt = $installment['due_at'];

                    return [
                        'installment_no' => (int) $installment['installment_no'],
                        'title' => Number::ordinal((int) $installment['installment_no']) . ' Payment',
                        'is_upfront' => (bool) $installment['is_upfront'],
                        'status' => (string) $installment['status'],
                        'principal_amount' => (float) $installment['principal_amount'],
                        'interest_amount' => (float) $installment['interest_amount'],
                        'total_due_amount' => (float) $installment['total_due_amount'],
                        'due_at' => $dueAt->toDateTimeString(),
                        'display_due_date' => $dueAt->format('d M Y'),
                    ];

                })->values(),
            ];
        });

        return $this->successResponse([
            'amount' => $amount,
            'currency_symbol' => setting('currency_symbol', 'global'),
            'splits' => $splits,
            'max_split' => CreditLimitSplit::active()->max('total_split'),
        ], 'Split preview fetched successfully');
    }

    private function toAmountString(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    private function toCompactAmountString(float $amount): string
    {
        return rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');
    }

    private function estimatePlanDuration(int $splitCount, int $intervalAmount, string $intervalType): array
    {
        $intervalAmount = max(1, $intervalAmount);

        if ($intervalType === 'day') {
            return [
                'value' => max(1, $splitCount * $intervalAmount),
                'unit' => 'Day',
            ];
        }

        if ($intervalType === 'week') {
            return [
                'value' => max(1, $splitCount * $intervalAmount),
                'unit' => 'Week',
            ];
        }

        return [
            'value' => max(1, $splitCount * $intervalAmount),
            'unit' => 'Month',
        ];
    }

    private function calculatePreviewShippingCharge(float $amount, ?Collection $listings = null): float
    {
        $defaultShippingChargeAmount = (float) (setting('shipping_charge', 'fee') ?? 0);
        $defaultShippingChargeType = $this->normalizeShippingType((string) (setting('shipping_charge_type', 'fee') ?? 'fixed'));
        $listings = $listings ?? collect();
        if ($listings->isEmpty()) {
            return 0;
        }
        $shippingTotal = 0.0;

        foreach ($listings as $listing) {
            [$effectiveShippingAmount, $effectiveShippingType] = $this->resolveShippingForListing(
                $listing,
                $defaultShippingChargeAmount,
                $defaultShippingChargeType
            );


            $shippingTotal += $this->calculateShipping(
                $amount,
                $effectiveShippingAmount,
                $effectiveShippingType
            );
        }

        return round($shippingTotal, 2);
    }

    private function loadPreviewListings(mixed $listingIdsInput): Collection
    {
        $listingIds = $this->parseListingIds($listingIdsInput);
        if ($listingIds === []) {
            return collect();
        }

        return Listing::query()->whereIn('id', $listingIds)->get();
    }

    private function buildListingDiscountRules(Collection $listings): array
    {
        return $listings
            ->map(function (Listing $listing) {
                $type = strtolower(trim((string) ($listing->discount_type ?? '')));
                $value = max(0, (float) ($listing->discount_value ?? 0));

                if ($value <= 0 || !in_array($type, ['percentage', 'amount'], true)) {
                    return null;
                }

                return [
                    'type' => $type,
                    'value' => $value,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function parseListingIds(mixed $listingIdsInput): array
    {
        if (is_null($listingIdsInput) || $listingIdsInput === '') {
            return [];
        }

        $rawIds = is_array($listingIdsInput)
            ? $listingIdsInput
            : explode(',', (string) $listingIdsInput);

        return collect($rawIds)
            ->flatMap(function ($id) {
                return explode(',', (string) $id);
            })
            ->map(fn($id) => (int) trim((string) $id))
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function calculateShipping(float $subtotal, float $chargeAmount, string $type): float
    {
        if ($type === 'percentage') {
            return round($subtotal * $chargeAmount / 100, 2);
        }

        return round($chargeAmount, 2);
    }

    private function calculateTotalDiscount(float $subtotal, array $discountRules): float
    {
        $discount = 0.0;

        foreach ($discountRules as $rule) {
            $type = strtolower(trim((string) ($rule['type'] ?? 'amount')));
            $value = max(0, (float) ($rule['value'] ?? 0));

            $discount += $type === 'percentage'
                ? ($subtotal * $value / 100)
                : $value;
        }

        return round($discount, 2);
    }

    private function resolveShippingForListing(?Listing $listing, float $defaultChargeAmount, string $defaultChargeType): array
    {
        if (!$listing) {
            return [$defaultChargeAmount, $defaultChargeType];
        }

        $shippingConfig = $listing->shippingConfig();
        $listingChargeAmount = $shippingConfig['shipping_charge'] ?? $defaultChargeAmount;
        $listingChargeType = $shippingConfig['shipping_charge_type'] ?? $defaultChargeType;

        $effectiveChargeAmount = (float) $listingChargeAmount;
        $effectiveChargeType = is_string($listingChargeType)
            ? $this->normalizeShippingType($listingChargeType, $defaultChargeType)
            : $defaultChargeType;

        return [$effectiveChargeAmount, $effectiveChargeType];
    }

    private function normalizeShippingType(string $type, string $fallback = 'fixed'): string
    {
        $type = strtolower(trim($type));

        return in_array($type, ['fixed', 'percentage'], true) ? $type : $fallback;
    }
}
