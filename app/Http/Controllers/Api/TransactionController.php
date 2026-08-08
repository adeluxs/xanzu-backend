<?php

namespace App\Http\Controllers\Api;

use App\Enums\CardApplicationStatus;
use App\Enums\CardStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Card;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;

class TransactionController extends Controller
{
    use ApiResponse;

    /**
     * All user transactions with filters.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $transactions = $this->applyCommonFilters(
            query: Transaction::query()->whereBelongsTo($user)->latest(),
            request: $request,
        )->paginate($request->integer('per_page', 15));

        return $this->successResponse(
            data: [
                'transactions' => TransactionResource::collection($transactions),
            ],
            meta: $this->paginationMeta($transactions),
        );
    }

    /**
     * Card transactions and user credit-balance summary.
     * method in ('credit_card', 'bnpl')
     */
    public function cardTransactions(Request $request)
    {
        $user = $request->user();

        $query = Transaction::query()
            ->whereBelongsTo($user)
            // MySQL's default app collation is case-insensitive; direct equality
            // keeps the composite user/method index usable.
            ->whereIn('method', ['credit_card', 'bnpl'])
            ->latest();

        $transactions = $this->applyCommonFilters($query, $request)->paginate($request->integer('per_page', 15));


        $hasCard = $user->cards()->exists();

        if (!$hasCard) {
            $user->load([
                'cardApplications' => function ($query) {
                    $query->latest()->limit(1);
                }
            ]);
            $lastApplication = $user->cardApplications->first();
            $hasCard = $lastApplication && $lastApplication->status == CardApplicationStatus::Approved;
            $rejectedReason = $lastApplication && $lastApplication->status == CardApplicationStatus::Rejected ? $lastApplication->admin_note : null;
            $lastApplicationStatus = $lastApplication ? $lastApplication->status : null;
        }

        $cards = Card::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->where('status', CardStatus::Active)
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

        return $this->successResponse(
            data: [
                'has_card' => $hasCard,
                'rejection_reason' => $rejectedReason ?? null,
                'last_card_application_status' => $lastApplicationStatus ?? null,
                'credit_balance' => [
                    'credit_limit_amount' => formatCurrency($user->credit_limit_amount, $user->currency),
                    'used_credit_limit_amount' => formatCurrency($user->used_credit_limit_amount, $user->currency),
                    'remaining_credit_limit_amount' => formatCurrency($user->remaining_credit_limit_amount, $user->currency),
                ],
                'cards' => $cards->first(),
                'transactions' => TransactionResource::collection($transactions),
            ],
            meta: $this->paginationMeta($transactions),
        );
    }

    /**
     * Main wallet transactions and current balance.
     * method = 'Balance' (case-insensitive)
     */
    public function mainWalletTransactions(Request $request)
    {
        $user = $request->user();

        $query = Transaction::query()
            ->whereBelongsTo($user)
            ->where('method', 'balance')
            ->latest();

        $transactions = $this->applyCommonFilters($query, $request)->paginate($request->integer('per_page', 15));

        return $this->successResponse(
            data: [
                'balance' => formatCurrency($user->balance, $user->currency),
                'transactions' => TransactionResource::collection($transactions),
            ],
            meta: $this->paginationMeta($transactions),
        );
    }

    private function applyCommonFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('type'), function (Builder $query) use ($request) {
                $query->type($request->input('type'));
            })
            ->when($request->filled('status'), function (Builder $query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->filled('method'), function (Builder $query) use ($request) {
                $query->where('method', strtolower(trim((string) $request->input('method'))));
            })
            ->when($request->filled('date'), function (Builder $query) use ($request) {
                $raw = trim((string) $request->input('date'));
                if (str($raw)->contains(' to ')) {
                    [$from, $to] = array_pad(explode(' to ', $raw, 2), 2, $raw);
                    $query->whereBetween('created_at', [
                        Date::parse($from)->startOfDay(),
                        Date::parse($to)->endOfDay(),
                    ]);
                } else {
                    $day = Date::parse($raw);
                    $query->whereBetween('created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()]);
                }
            })
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $query->search($request->input('search'));
            });
    }

    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
