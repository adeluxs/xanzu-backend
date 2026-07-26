<?php

namespace App\Services;

use App\Models\Listing;

class ShippingCalculator
{
    public function calculate(array $checkoutItems, array $defaults): array
    {
        $defaultShippingChargeAmount = (float) ($defaults['charge_amount'] ?? 0);
        $defaultShippingChargeType = $this->normalizeShippingType((string) ($defaults['charge_type'] ?? 'fixed'));

        $shippingChargeAmount = $defaultShippingChargeAmount;
        $shippingChargeType = $defaultShippingChargeType;
        $finalShippingCharge = 0.0;
        $hasCapturedShippingSnapshot = false;

        foreach ($checkoutItems as $item) {
            [$effectiveShippingAmount, $effectiveShippingType] = $this->resolveShippingForListing(
                $item['listing'] ?? null,
                $defaultShippingChargeAmount,
                $defaultShippingChargeType
            );

            if (! $hasCapturedShippingSnapshot) {
                $shippingChargeAmount = $effectiveShippingAmount;
                $shippingChargeType = $effectiveShippingType;
                $hasCapturedShippingSnapshot = true;
            }

            $finalShippingCharge += $this->calculateShipping(
                (float) ($item['line_total'] ?? 0),
                $effectiveShippingAmount,
                $effectiveShippingType
            );
        }

        return [
            'shipping_charge_amount' => $shippingChargeAmount,
            'shipping_charge_type' => $shippingChargeType,
            'final_shipping_charge' => round($finalShippingCharge, 2),
        ];
    }

    private function calculateShipping(float $subtotal, float $chargeAmount, string $type): float
    {
        if ($type === 'percentage') {
            return round($subtotal * $chargeAmount / 100, 2);
        }

        return round($chargeAmount, 2);
    }

    private function resolveShippingForListing(?Listing $listing, float $defaultChargeAmount, string $defaultChargeType): array
    {
        if (! $listing) {
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
