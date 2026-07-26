<?php

namespace App\Services;

use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Card\Stripe\StripeCard;

class CardStatusSyncService
{
    public function syncUserCardStatus(User $user, $force = false): void
    {
        $card = Card::query()
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        if (! $card) {
            return;
        }

        $targetStatus = (int) $user->card_status === 1
            ? CardStatus::Active->value
            : CardStatus::Inactive->value;

        $this->setCardStatus($card, $targetStatus, $force);
    }

    public function setCardStatus(Card $card, string $status, $force = false): void
    {
        if ($card->status?->value === $status && ! $force) {
            return;
        }

        $provider = $card->cardHolder?->provider;
        $stripeEnabled = (bool) plugin_active('Stripe Virtual Card');

        if ($provider === 'stripe' && $stripeEnabled && class_exists(StripeCard::class)) {
            try {
                (new StripeCard)->setCardStatus($card, $status);

                return;
            } catch (\Throwable $e) {
                Log::warning('Failed to sync Stripe card status', [
                    'card_id' => $card->id,
                    'user_id' => $card->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $card->update([
            'status' => $status,
        ]);
    }
}
