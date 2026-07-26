<?php

namespace Modules\Card\Stripe;

use App\Models\CardHolder;
use App\Models\User;
use Stripe\StripeClient;

class StripeCard
{
    public function execute(CardHolder $cardholder, float $initialAmount = 0)
    {
        $initialAmount = max(0, floor($initialAmount));

        // Create card in stripe
        $stripe_card = $this->client()->issuing->cards->create([
            'cardholder' => $cardholder->card_holder_id,
            'currency' => 'usd',
            'type' => 'virtual',
            'spending_controls' => [
                'spending_limits' => [
                    [
                        'amount' => $initialAmount,
                        'interval' => 'all_time',
                    ],
                ],
            ],
        ]);

        $data = [
            'card_holder_id' => $cardholder->id,
            'card_id' => $stripe_card->id,
            'currency' => 'usd',
            'type' => 'virtual',
            'status' => 'active',
            'card_number' => '0000000000000000',
            'cvc' => '000',
            'amount' => $initialAmount,
            'expiration_month' => $stripe_card->exp_month,
            'expiration_year' => $stripe_card->exp_year,
            'last_four_digits' => $stripe_card->last4,
        ];

        return [
            'card' => $stripe_card,
            'data' => $data,
        ];
    }

    public function updateCardStatus($card)
    {
        $stripe_card = $this->client()->issuing->cards->update($card->card_id, [
            'status' => $card->status == 'active' ? 'inactive' : 'active',
        ]);

        // Update card status in database
        $card->update([
            'status' => $stripe_card->status,
        ]);

        return $card;
    }

    public function addCardBalance($card, $amount)
    {
        try {
            $this->client()->issuing->cards->update($card->card_id, [
                'spending_controls' => [
                    'spending_limits' => [
                        [
                            'amount' => $card->amount + $amount, // The spending limit in cents (e.g., $50.00)
                            'interval' => 'all_time', // The interval for the limit (e.g., daily, weekly, monthly, yearly, all_time)
                        ],
                    ],
                ],
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }

        // Update card balance in database
        $card->update(['amount' => $card->amount + $amount]);

        return $card;
    }

    public function setCardBalance($card, $amount)
    {
        $amount = max(0, $amount);

        $this->client()->issuing->cards->update($card->card_id, [
            'spending_controls' => [
                'spending_limits' => [
                    [
                        'amount' => $amount * 100, // The spending limit in cents (e.g., $50.00)
                        'interval' => 'all_time',
                    ],
                ],
            ],
        ]);

        $card->update(['amount' => $amount]);

        return $card;
    }

    public function setCardStatus($card, string $status)
    {
        $status = $status === 'active' ? 'active' : 'inactive';

        $stripe_card = $this->client()->issuing->cards->update($card->card_id, [
            'status' => $status,
        ]);

        $card->update([
            'status' => $stripe_card->status,
        ]);

        return $card;
    }

    public function validationRules($request)
    {
        if ($request->type == 'existing_one') {
            $validator_rules = [
                'cardholder_id' => 'required|exists:card_holders,id',
            ];
        } else {
            $validator_rules = [
                'name' => 'required|string',
                'email' => 'nullable|email',
                'phone_number' => 'nullable|string',
                // 'type' => 'required|in:individual,business',
                'address' => 'required|string',
                'country' => 'required|string',
                'city' => 'required|string',
                'state' => 'required|string',
                'postal_code' => 'required|string',
            ];
        }

        return $validator_rules;
    }

    public function createCardHolderForUser(User $user, array $data)
    {
        $name = trim(($data['first_name'] ?? $user->first_name).' '.($data['last_name'] ?? $user->last_name));
        $email = $data['email'] ?? $user->email;
        $country = getCountryData($data['country'] ?? $user->country);
        $phoneNumber = ($data['phone_number'] ?? $user->phone) ? ($country ? $country['dial_code'] : '').($data['phone_number'] ?? $user->phone) : null;
        $countryCode = $country ? $country['code'] : null;

        $dob = $data['dob'] ? now()->parse($data['dob']) : now();

        $stripe_card_holder = $this->client()->issuing->cardholders->create([
            'type' => 'individual',
            'name' => $name,
            'email' => $email,
            'phone_number' => $phoneNumber,
            'individual' => [
                'first_name' => $data['first_name'] ?? $user->first_name,
                'last_name' => $data['last_name'] ?? $user->last_name,
                'dob' => [
                    'day' => $dob->day,
                    'month' => $dob->month,
                    'year' => $dob->year,
                ],
                'card_issuing' => [
                    'user_terms_acceptance' => [
                        'date' => now()->getTimestamp(),
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ],
                ],
            ],
            'billing' => [
                'address' => [
                    'line1' => $data['address'] ?? $user->address,
                    'city' => $data['city'] ?? $user->city,
                    'state' => $data['state'] ?? $user->state,
                    'country' => $countryCode ?? $user->country,
                    'postal_code' => $data['postal_code'] ?? $user->zip_code,
                ],
            ],
        ]);

        return CardHolder::create([
            'user_id' => $user->id,
            'card_holder_id' => $stripe_card_holder->id,
            'provider' => 'stripe',
            'name' => $name,
            'dob' => $data['dob'] ?? null,
            'email' => $email,
            'phone_number' => $phoneNumber,
            'type' => 'individual',
            'address' => $data['address'] ?? $user->address,
            'country' => $data['country'] ?? $user->country,
            'city' => $data['city'] ?? $user->city,
            'state' => $data['state'] ?? $user->state,
            'postal_code' => $data['postal_code'] ?? $user->zip_code,
            'status' => 'active',
        ]);
    }

    public function getCardDetails($card_id)
    {
        return $this->client()->issuing->cards->retrieve($card_id, [
            'expand' => [
                'number',
                'cvc',
            ],
        ]);
    }

    public function getCardTransactions($card_id)
    {
        try {
            $transactions = $this->client()->issuing->transactions->all([
                'card' => $card_id,
                'limit' => 5,
            ]);

            return array_map(function ($transaction) {
                return (object) [
                    'id' => $transaction->id,
                    'created' => now()->parse($transaction->created)->format('d M Y h:i A'),
                    'amount' => $transaction->amount,
                    'currency' => strtoupper($transaction->currency),
                    'status' => $transaction->status ?? 'Success',
                    'merchant_data' => (object) [
                        'name' => $transaction->merchant_data->name,
                    ],
                ];
            }, $transactions->data);
        } catch (\Throwable $th) {
            return [];
        }
    }

    protected function client()
    {
        $stripeCredential = plugin_active('Stripe Virtual Card');
        $stripe_secret = $stripeCredential ? json_decode($stripeCredential->data, true)['secret_key'] : null;

        $stripe = new StripeClient($stripe_secret);

        return $stripe;
    }
}
