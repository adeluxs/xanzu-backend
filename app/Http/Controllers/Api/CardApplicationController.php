<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\CardApplication;
use App\Services\CardApplicationService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CardApplicationController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $applications = CardApplication::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()->map(function ($application) {
                return [
                    'id' => $application->id,
                    'first_name' => $application->first_name,
                    'last_name' => $application->last_name,
                    'email' => $application->email,
                    'phone_number' => $application->phone_number,
                    'address' => $application->address,
                    'city' => $application->city,
                    'state' => $application->state,
                    'country' => $application->country,
                    'postal_code' => $application->postal_code,
                    'status' => $application->status,
                    'submitted_at' => $application->created_at->format('Y-m-d H:i:s'),
                    'approved_at' => $application->approved_at ? $application->approved_at->format('Y-m-d h:i:s a') : null,
                    'rejected_at' => $application->rejected_at ? $application->rejected_at->format('Y-m-d h:i:s a') : null,
                    'rejection_reason' => $application->admin_note,
                ];
            });

        return $this->successResponse($applications);
    }

    public function cards(Request $request)
    {
        $cards = Card::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function ($card) {
                return [
                    'id' => $card->id,
                    'card_id' => $card->card_id,
                    'currency' => $card->currency,
                    'type' => $card->type,
                    'status' => $card->status,
                    'amount' => formatCurrency($card->amount, strtoupper($card->currency)),
                    'last_four_digits' => $card->last_four_digits,
                    'expiration_month' => $card->expiration_month,
                    'expiration_year' => $card->expiration_year,
                    'card_number' => $card->card_number,
                    'cvv' => $card->cvc,
                    'created_at' => $card->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return $this->successResponse($cards);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:191'],
            'phone_number' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:30'],
            'dob' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $application = app(CardApplicationService::class)->submit(
                $request->user(),
                $validator->validated()
            );

            return $this->successResponse($application, __('Card application submitted successfully.'));
        } catch (\Throwable $e) {
            report($e);

            return $this->errorResponse(
                __('Unable to submit the card application. Please try again.'),
                500
            );
        }
    }
}
