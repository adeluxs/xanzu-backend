<?php

namespace App\Services;

use App\Enums\CardApplicationStatus;
use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\CardApplication;
use App\Models\CardHolder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Card\Stripe\StripeCard;

class CardApplicationService
{
    public function submit(User $user, array $data): CardApplication
    {
        if ((int) $user->card_status === 0) {
            throw new \Exception(__('Card application is disabled for your account.'));
        }

        $existingCard = Card::query()->where('user_id', $user->id)->exists();
        if ($existingCard) {
            throw new \Exception(__('You already have a card. Multiple cards are not allowed.'));
        }

        $existing = CardApplication::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                CardApplicationStatus::Pending->value,
                CardApplicationStatus::OnHold->value,
                CardApplicationStatus::Approved->value,
            ])
            ->first();

        if ($existing) {
            throw new \Exception(__('You already have a pending or approved card application.'));
        }

        return CardApplication::create([
            'user_id' => $user->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'dob' => now()->parse($data['dob'])->format('Y-m-d'),
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'country' => $data['country'],
            'postal_code' => $data['postal_code'],
            'status' => CardApplicationStatus::Pending->value,
        ]);
    }

    public function approve(CardApplication $application, ?string $note = null): CardApplication
    {
        return DB::transaction(function () use ($application, $note) {
            $application->refresh();

            if ($application->status === CardApplicationStatus::Approved) {
                return $application;
            }

            $user = User::query()->whereKey($application->user_id)->lockForUpdate()->firstOrFail();

            $existingCard = Card::query()->where('user_id', $user->id)->exists();
            if ($existingCard) {
                throw new \Exception(__('User already has a card. Multiple cards are not allowed.'));
            }

            $application->update([
                'status' => CardApplicationStatus::Approved->value,
                'admin_note' => $note,
                'approved_at' => now(),
                'rejected_at' => null,
            ]);

            $user->update([
                'card_status' => 1,
            ]);

            $cardHolder = CardHolder::query()->where('user_id', $user->id)->first();
            if (! $cardHolder) {
                $cardHolder = $this->createCardHolderFromApplication($user, $application);
            }

            $card = Card::query()->where('user_id', $user->id)->latest()->first();
            if (! $card) {
                $this->createCardForHolder($cardHolder, $user);
            }

            app(CardBalanceSyncService::class)->syncUserCardBalance($user, true);
            app(CardStatusSyncService::class)->syncUserCardStatus($user, true);

            return $application->fresh();
        });
    }

    public function hold(CardApplication $application, ?string $note = null): CardApplication
    {
        return DB::transaction(function () use ($application, $note) {
            $application->refresh();

            if ($application->status === CardApplicationStatus::OnHold) {
                return $application;
            }

            $application->update([
                'status' => CardApplicationStatus::OnHold->value,
                'admin_note' => $note,
                'approved_at' => null,
                'rejected_at' => null,
            ]);

            return $application->fresh();
        });
    }

    public function reject(CardApplication $application, ?string $note = null, bool $disableUser = true): CardApplication
    {
        return DB::transaction(function () use ($application, $note, $disableUser) {
            $application->refresh();

            if ($application->status === CardApplicationStatus::Rejected) {
                return $application;
            }

            $application->update([
                'status' => CardApplicationStatus::Rejected->value,
                'admin_note' => $note,
                'rejected_at' => now(),
            ]);

            if ($disableUser) {
                $user = User::query()->whereKey($application->user_id)->first();
                if ($user) {
                    $user->update([
                        'card_status' => 0,
                    ]);

                    app(CardStatusSyncService::class)->syncUserCardStatus($user);
                }
            }

            return $application->fresh();
        });
    }

    protected function createCardHolderFromApplication(User $user, CardApplication $application): CardHolder
    {
        $data = [
            'first_name' => $application->first_name,
            'last_name' => $application->last_name,
            'email' => $application->email,
            'phone_number' => $application->phone_number,
            'address' => $application->address,
            'city' => $application->city,
            'dob' => $application->dob,
            'state' => $application->state,
            'country' => $application->country,
            'postal_code' => $application->postal_code,
        ];

        $provider = 'stripe';
        $stripeEnabled = (bool) plugin_active('Stripe Virtual Card');

        if ($provider === 'stripe' && $stripeEnabled && class_exists(StripeCard::class)) {
            return (new StripeCard)->createCardHolderForUser($user, $data);
        }

        return CardHolder::create([
            'user_id' => $user->id,
            'card_holder_id' => null,
            'provider' => $provider,
            'status' => 'active',
            'dob' => $data['dob'],
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'type' => 'individual',
            'address' => $data['address'],
            'country' => $data['country'],
            'city' => $data['city'],
            'state' => $data['state'],
            'postal_code' => $data['postal_code'],
        ]);
    }

    protected function createCardForHolder(CardHolder $cardHolder, User $user): Card
    {
        $initialAmount = max(0, round((float) $user->remaining_credit_limit_amount, 2));

        $stripeEnabled = (bool) plugin_active('Stripe Virtual Card');
        if (($cardHolder->provider ?? null) === 'stripe' && $stripeEnabled && class_exists(StripeCard::class)) {
            $stripe = new StripeCard;
            $created = $stripe->execute($cardHolder, $initialAmount);
            $data = $created['data'] ?? [];
            $stripeCard = $created['card'] ?? null;

            $data['user_id'] = $user->id;
            $data['card_id'] = $stripeCard?->id;
            $data['status'] = $data['status'] ?? CardStatus::Active->value;
            $data['amount'] = $initialAmount;
            $data['provider'] = $cardHolder->provider;

            return Card::create($data);
        }

        return Card::create([
            'user_id' => $user->id,
            'card_holder_id' => $cardHolder->id,
            'status' => CardStatus::Active->value,
            'amount' => $initialAmount,
        ]);
    }
}
