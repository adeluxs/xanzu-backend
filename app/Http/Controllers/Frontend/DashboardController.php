<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\OrderStatus;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\ReferralService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    public function dashboard(Request $request)
    {
        // Chart refreshes should not execute the full dashboard workload.
        if ($request->ajax() && $request->type === 'chart') {
            return response()->json($this->getChartData($request));
        }
        if ($request->ajax() && $request->type === 'pie') {
            return response()->json($this->getPieData($request));
        }

        $recentSell = Order::whereBelongsTo(auth()->user(), 'seller')
            ->latest()
            ->take(5)
            ->get();
        $recentPurchase = Order::whereBelongsTo(auth()->user(), 'buyer')
            ->where('is_topup', false)
            ->latest()
            ->take(5)
            ->get();

        $totalViews = DB::table('listing_analysis')
            ->join('listings', 'listing_analysis.listing_id', '=', 'listings.id')
            ->where('listings.seller_id', auth()->id())
            ->where('listing_analysis.event_type', 'view')
            ->count();

        $totalRevenue = Order::whereBelongsTo(auth()->user(), auth()->user()->is_seller ? 'seller' : 'buyer')
            ->where('is_topup', false)
            ->whereIn('status', [OrderStatus::Success->value, OrderStatus::Completed->value, OrderStatus::WaitingForDelivery->value])
            ->sum('total_price');
        $getReferral = $this->referralService->getOrCreateFirstReferralLink(auth()->user());
        $dataCount = [
            'total_views' => $totalViews,
            'total_revenue' => $totalRevenue,
            'total_referral' => $getReferral?->relationships()?->count() ?? 0,
        ];

        if (! auth()->user()->is_seller) {
            $dataCount['total_orders'] = Order::whereBelongsTo(auth()->user(), auth()->user()->is_seller ? 'seller' : 'buyer')
                ->whereStatus(OrderStatus::Completed->value)->count();
        }

        $chartData = $this->getChartData($request);
        $pieData = $this->getPieData($request);
        $sellerData = null;
        if (auth()->user()->is_seller) {
            $sellerData = $this->getSellerData($request);
        }

        $sellerOverviewIcon = null;
        if (auth()->user()->is_seller) {
            $sellerOverviewIcon = [
                'Pending' => 'majesticons:basket-2',
                'Payment Success' => 'majesticons:shopping-cart',
                'Waiting For Delivery' => 'majesticons:repeat-circle',
                'Cancelled' => 'majesticons:analytics-delete',
                'Completed' => 'majesticons:clipboard-check',
                'Failed' => 'majesticons:receipt-text',
                'Refunded' => 'majesticons:arrow-up-circle',
            ];

        }

        return view('frontend::user.dashboard', compact('recentSell', 'recentPurchase', 'sellerOverviewIcon', 'dataCount', 'chartData', 'pieData', 'sellerData'));
    }

    protected function getSellerData(Request $request)
    {
        $user = Auth::user();

        $totalSold = Transaction::where('type', TxnType::ProductSold->value)
            ->where('user_id', $user->id)
            ->where('status', TxnStatus::Success->value)
            ->sum('amount');

        $orders = Order::select(['status', DB::raw('count(*) as total_sells')])
            ->whereHas('items', fn ($q) => $q->where('seller_id', $user->id))
            ->groupBy('status')
            ->get();
        $orderData = [];
        foreach (OrderStatus::cases() as $key => $value) {
            $orderData[str($value->value)->headline()->toString()] = $orders->where('status', $value->value)->sum('total_sells');
        }
        $listingStats = $user->listings()
            ->selectRaw('COUNT(*) as total_listings, SUM(CASE WHEN is_flash = 1 THEN 1 ELSE 0 END) as flash_listings')
            ->first();
        $planData = $this->getPlanData(
            totalListings: (int) ($listingStats?->total_listings ?? 0),
            flashListings: (int) ($listingStats?->flash_listings ?? 0),
        );

        return [
            'total_sold' => $totalSold,
            'orderData' => $orderData, // 6 status
            'balance' => $user->balance,
            'planData' => $planData,
            'total_listings' => (int) ($listingStats?->total_listings ?? 0),

        ];
    }

    public function getRangeDate($selectedMonth, $selectedYear)
    {
        $startDate = Date::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $dates = collect();
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dates->push($current->format('j M'));
            $current->addDay();
        }

        return $dates;
    }

    protected function getChartData(Request $request)
    {
        $user = Auth::user();

        $selectedMonth = $request->input('month', now()->format('m'));
        $selectedYear = $request->input('year', now()->format('Y'));
        $startDate = Date::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $dates = $this->getRangeDate($selectedMonth, $selectedYear);

        if ($user->is_seller) {
            $txnInArr = [TxnType::ProductSold->value, TxnType::Deposit->value];
        } else {
            $txnInArr = [TxnType::ProductOrder->value];
        }
        $txnInArr[] = TxnType::Withdraw->value;

        $transactions = Transaction::query()
            ->whereBelongsTo($user)
            ->whereIn('type', $txnInArr)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', TxnStatus::Success->value)
            ->selectRaw('type, DAY(created_at) as day_no, SUM(amount) as amount')
            ->groupBy('type', 'day_no')
            ->get();

        $seriesData = [
            TxnType::ProductSold->value => array_fill(0, $dates->count(), 0),
            TxnType::Withdraw->value => array_fill(0, $dates->count(), 0),
            TxnType::ProductOrder->value => array_fill(0, $dates->count(), 0),
            TxnType::Deposit->value => array_fill(0, $dates->count(), 0),
        ];
        foreach ($transactions as $txn) {
            $type = $txn->type instanceof TxnType ? $txn->type->value : (string) $txn->type;
            $dayIndex = max(0, (int) $txn->day_no - 1);
            if (isset($seriesData[$type][$dayIndex])) {
                $seriesData[$type][$dayIndex] = (float) $txn->amount;
            }
        }

        $__seriesData = [
            ['name' => 'Withdraw', 'data' => $seriesData[TxnType::Withdraw->value]],
        ];
        if ($user->is_seller) {
            $__seriesData[] = ['name' => 'Deposit', 'data' => $seriesData[TxnType::Deposit->value]];
            $__seriesData[] = ['name' => 'Sell', 'data' => $seriesData[TxnType::ProductSold->value]];
        } else {
            $__seriesData[] = ['name' => 'Orders', 'data' => $seriesData[TxnType::ProductOrder->value]];
        }

        return [
            'labels' => $dates->toArray(),
            'series' => $__seriesData,
        ];
    }

    protected function getPlanData(?int $totalListings = null, ?int $flashListings = null)
    {

        $user = Auth::user();

        $userCurrentPlan = $user->hasValidSubscription;

        if (! $userCurrentPlan) {
            return [
                'name' => __('No Plan'),
                'expiry' => __('No Expiry'),
                'total' => 0,
                'remaining' => 0,
                'total_flash_sale' => 0,
                'flash_sale_remaining' => 0,
                'listing_limit' => 0,
                'listing_remaining' => 0,
                'commission_circle_value' => 0,
                'commission_circle_inner_text' => '0%',
                'commission_circle_inner_text_type' => '%',
                'commission_text' => __('No active plan'),
            ];
        }

        try {

            $totalDays = 0;
            if ($userCurrentPlan->validity_at && $userCurrentPlan->created_at) {
                $totalDays = now()->parse($userCurrentPlan->created_at)->diffInDays(now()->parse($userCurrentPlan->validity_at), true);
            }

            $remainingDays = 0;
            if ($userCurrentPlan->validity_at) {
                $remainingDays = max(0, now()->diffInDays(now()->parse($userCurrentPlan->validity_at), true));
            }

            if ($totalListings === null || $flashListings === null) {
                $listingStats = $user->listings()
                    ->selectRaw('COUNT(*) as total_listings, SUM(CASE WHEN is_flash = 1 THEN 1 ELSE 0 END) as flash_listings')
                    ->first();
                $totalListings ??= (int) ($listingStats?->total_listings ?? 0);
                $flashListings ??= (int) ($listingStats?->flash_listings ?? 0);
            }

            $flashSaleRemaining = max(0, ($userCurrentPlan->flash_sale_limit ?? 0) - $flashListings);
            $listingRemaining = max(0, ($userCurrentPlan->listing_limit ?? 0) - $totalListings);

            $chargeType = $userCurrentPlan->charge_type ?? 'fixed';
            $chargeValue = $userCurrentPlan->charge_value ?? 0;

            $commissionCircleValue = $chargeType == 'percentage' ? $chargeValue : $chargeValue;
            $commissionCircleInnerText = $chargeType == 'percentage'
                ? $chargeValue.'%'
                : setting('currency_symbol').$chargeValue;
            $commissionCircleInnerTextType = $chargeType == 'percentage' ? '%' : setting('currency_symbol');

            $commissionText = $chargeType == 'percentage'
                ? __('You earn :percentage% per sell', ['percentage' => max(0, 100 - $chargeValue)])
                : __('You pay :amount per sell', ['amount' => setting('currency_symbol').$chargeValue]);

            return [
                'name' => $userCurrentPlan->plan->name ?? __('No Plan'),
                'expiry' => $userCurrentPlan->validity_at ?? __('No Expiry'),
                'total' => (int) $totalDays,
                'remaining' => (int) $remainingDays,
                'total_flash_sale' => $userCurrentPlan->flash_sale_limit ?? 0,
                'flash_sale_remaining' => $flashSaleRemaining,
                'listing_limit' => $userCurrentPlan->listing_limit ?? 0,
                'listing_remaining' => $listingRemaining,
                'commission_circle_value' => $commissionCircleValue,
                'commission_circle_inner_text' => $commissionCircleInnerText,
                'commission_circle_inner_text_type' => $commissionCircleInnerTextType,
                'commission_text' => $commissionText,
            ];
        } catch (Exception $e) {

            return [
                'name' => __('No Plan'),
                'expiry' => __('No Expiry'),
                'total' => 0,
                'remaining' => 0,
                'total_flash_sale' => 0,
                'flash_sale_remaining' => 0,
                'listing_limit' => 0,
                'listing_remaining' => 0,
                'commission_circle_value' => 0,
                'commission_circle_inner_text' => '0%',
                'commission_circle_inner_text_type' => '%',
                'commission_text' => __('Error loading plan data'),
            ];
        }
    }

    protected function getPieData(Request $request)
    {
        $user = Auth::user();

        $selectedMonth = $request->input('month', now()->format('m'));
        $selectedYear = $request->input('year', now()->format('Y'));

        $startDate = Date::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $dates = $this->getRangeDate($selectedMonth, $selectedYear);

        $orders = Order::select(['status', DB::raw('count(*) as total_sells')])
            ->whereHas('items', fn ($q) => $q->where('seller_id', $user->id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get();
        $seriesData = [];

        foreach (OrderStatus::cases() as $key => $value) {
            $seriesData[str($value->value)->headline()->toString()] = $orders->where('status', $value->value)->sum('total_sells');
        }

        return [
            'labels' => array_keys($seriesData),
            'series' => array_values($seriesData),
            'colors' => [
                '#F1B44C',
                '#42af3c',
                '#556EE6',
                '#34C38F',
                '#fb2d26',
                '#F46A6A',
            ],
        ];
    }
}
