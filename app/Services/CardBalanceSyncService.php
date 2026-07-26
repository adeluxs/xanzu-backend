<?php

namespace App\Services;

use App\Models\Card;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Card\Stripe\StripeCard;

class CardBalanceSyncService
{
    public function syncUserCardBalance(User $user, $force = false): void
    {
        $card = Card::query()
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        if (! $card) {
            return;
        }

        $targetAmount = max(0, round((float) $user->remaining_credit_limit_amount, 2));
        $this->syncCardBalance($card, $targetAmount, $force);
    }

    public function syncCardBalance(Card $card, float $targetAmount, $force = false): void
    {
        $targetAmount = max(0, round($targetAmount, 2));
        $currentAmount = round((float) $card->amount, 2);

        if ($currentAmount === $targetAmount && ! $force) {
            return;
        }

        $provider = $card->cardHolder?->provider;
        $stripeEnabled = (bool) plugin_active('Stripe Virtual Card');
        if ($provider === 'stripe' && $stripeEnabled && class_exists(StripeCard::class)) {
            try {
                (new StripeCard)->setCardBalance($card, $targetAmount);

                // update card number and cvv
                $cardDetails = (new StripeCard)->getCardDetails($card->card_id);
                $card->card_number = $cardDetails->number;
                $card->cvc = $cardDetails->cvc;
                $card->save();

                return;

            } catch (\Throwable $e) {
                Log::warning('Failed to sync Stripe card balance', [
                    'card_id' => $card->id,
                    'user_id' => $card->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $card->update([
            'amount' => $targetAmount,
        ]);
    }
}
