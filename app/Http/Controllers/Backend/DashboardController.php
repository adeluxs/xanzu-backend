<?php

namespace App\Http\Controllers\Backend;

use App\Enums\KYCStatus;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Gateway;
use App\Models\Listing;
use App\Models\LoginActivities;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        [$startDate, $endDate, $dateArray] = $this->dateWindow($request);
        $symbol = setting('currency_symbol', 'global');

        // Charts are aggregated in SQL. The previous implementation loaded all
        // matching transactions/orders into PHP and grouped them in memory.
        $depositStatistics = $this->dailyTransactionSum(
            $startDate,
            $endDate,
            [TxnType::Deposit->value, TxnType::ManualDeposit->value],
            TxnStatus::Success->value,
            $dateArray,
        );
        $withdrawStatistics = $this->dailyTransactionSum(
            $startDate,
            $endDate,
            [TxnType::Withdraw->value, TxnType::WithdrawAuto->value],
            null,
            $dateArray,
        );
        $orderStatistics = $this->dailyOrderSum($startDate, $endDate, false, $dateArray);
        $bnplOrderStatistics = $this->dailyOrderSum($startDate, $endDate, true, $dateArray);

        // AJAX chart refresh should not execute the expensive non-chart
        // dashboard queries (latest orders, geo breakdown, global counters).
        if ($request->ajax() && $request->input('type') === 'site') {
            return response()->json([
                'date_label' => $dateArray,
                'deposit_statistics' => $depositStatistics,
                'withdraw_statistics' => $withdrawStatistics,
                'listing_order_statistics' => $orderStatistics,
                'bnpl_order_statistics' => $bnplOrderStatistics,
                'symbol' => $symbol,
            ]);
        }

        $userStats = User::query()->selectRaw(
            'SUM(CASE WHEN user_type = ? THEN 1 ELSE 0 END) AS total_buyers, '
            .'SUM(CASE WHEN user_type = ? THEN 1 ELSE 0 END) AS total_merchants, '
            .'SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS disabled_user, '
            .'SUM(CASE WHEN kyc = ? THEN 1 ELSE 0 END) AS pending_kyc, '
            .'SUM(CASE WHEN ref_id IS NOT NULL THEN 1 ELSE 0 END) AS total_referral',
            ['buyer', 'merchant', KYCStatus::Pending->value]
        )->first();

        $transactionStats = Transaction::query()->selectRaw(
            'SUM(CASE WHEN status = ? AND type IN (?, ?) THEN CAST(amount AS DECIMAL(20,8)) ELSE 0 END) AS total_deposit, '
            .'SUM(CASE WHEN status = ? AND type IN (?, ?) THEN CAST(amount AS DECIMAL(20,8)) ELSE 0 END) AS total_withdraw, '
            .'SUM(CASE WHEN status = ? AND type IN (?, ?) THEN 1 ELSE 0 END) AS pending_withdraw, '
            .'SUM(CASE WHEN status = ? AND type = ? THEN 1 ELSE 0 END) AS pending_manual_deposit, '
            .'SUM(CASE WHEN status = ? AND target_id IS NOT NULL AND target_type = ? AND type = ? THEN CAST(amount AS DECIMAL(20,8)) ELSE 0 END) AS deposit_bonus',
            [
                TxnStatus::Success->value, TxnType::Deposit->value, TxnType::ManualDeposit->value,
                TxnStatus::Success->value, TxnType::Withdraw->value, TxnType::WithdrawAuto->value,
                TxnStatus::Pending->value, TxnType::Withdraw->value, TxnType::WithdrawAuto->value,
                TxnStatus::Pending->value, TxnType::ManualDeposit->value,
                TxnStatus::Success->value, 'deposit', TxnType::Referral->value,
            ]
        )->first();

        $staticCounts = Cache::remember('admin.dashboard.static-counts.v2', now()->addSeconds(60), fn () => [
            'total_staff' => Admin::count(),
            'total_gateway' => Gateway::where('status', true)->count(),
            'total_category' => Category::count(),
            'total_coupons' => Coupon::count(),
            'total_listing' => Listing::count(),
            'total_ticket' => Ticket::count(),
        ]);

        // browser/platform were historically virtual accessors parsed from `agent`,
        // so older databases do not have physical columns. Use fast SQL grouping
        // after the additive migration, with a chunked legacy fallback that works
        // immediately when code is deployed before migrations are run.
        $browser = Cache::remember('admin.dashboard.login-browser.v3', now()->addMinutes(2), fn () =>
            $this->loginActivityBreakdown('browser')
        );
        $platform = Cache::remember('admin.dashboard.login-platform.v3', now()->addMinutes(2), fn () =>
            $this->loginActivityBreakdown('platform')
        );

        $country = Cache::remember('admin.dashboard.country-top5.v2', now()->addMinutes(2), fn () =>
            User::query()->select('country', DB::raw('COUNT(*) AS aggregate_count'))
                ->whereNotNull('country')->where('country', '!=', '')
                ->groupBy('country')->orderByDesc('aggregate_count')->limit(5)
                ->pluck('aggregate_count', 'country')->toArray()
        );

        $orderStatusStatistics = Order::query()
            ->select('status', DB::raw('COUNT(*) AS analysis_count'))
            ->whereBetween('order_date', [$startDate, $endDate])
            ->groupBy('status')
            ->pluck('analysis_count', 'status');

        $bnplOrderAnalysis = Order::query()
            ->where('is_bnpl', true)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->select('status', DB::raw('COUNT(*) AS analysis_count'))
            ->groupBy('status')
            ->pluck('analysis_count', 'status')
            ->mapWithKeys(static function ($count, $status) {
                $label = ucwords(str_replace('_', ' ', (string) $status));

                return [$label => (int) $count];
            })
            ->toArray();

        $data = [
            'withdraw_count' => (int) ($transactionStats->pending_withdraw ?? 0),
            'kyc_count' => (int) ($userStats->pending_kyc ?? 0),
            'deposit_count' => (int) ($transactionStats->pending_manual_deposit ?? 0),
            'total_buyers' => (int) ($userStats->total_buyers ?? 0),
            'total_merchants' => (int) ($userStats->total_merchants ?? 0),
            'disabled_user' => (int) ($userStats->disabled_user ?? 0),
            'latest_user' => User::query()->latest()->limit(5)->get(),
            'latest_orders' => Order::query()->with([
                'seller' => static function ($query) {
                    $query->select([
                        'users.id',
                        'users.first_name',
                        'users.last_name',
                        'users.email',
                        'users.avatar',
                    ]);
                },
                'buyer:id,first_name,last_name,email,avatar',
                'items.listing:id,product_name,category_id,thumbnail',
                'items.listing.category:id,name,slug',
            ])->latest()->limit(8)->get(),
            'total_staff' => $staticCounts['total_staff'],
            'total_deposit' => (float) ($transactionStats->total_deposit ?? 0),
            'total_withdraw' => (float) ($transactionStats->total_withdraw ?? 0),
            'total_referral' => (int) ($userStats->total_referral ?? 0),
            'total_category' => $staticCounts['total_category'],
            'total_coupons' => $staticCounts['total_coupons'],
            'total_listing' => $staticCounts['total_listing'],
            'date_label' => $dateArray,
            'deposit_statistics' => $depositStatistics,
            'withdraw_statistics' => $withdrawStatistics,
            'listing_order_statistics' => $orderStatistics,
            'bnpl_order_statistics' => $bnplOrderStatistics,
            'bnpl_order_analysis' => $bnplOrderAnalysis,
            'start_date' => $startDate->format('m/d/Y'),
            'end_date' => $endDate->format('m/d/Y'),
            'deposit_bonus' => (float) ($transactionStats->deposit_bonus ?? 0),
            'total_gateway' => $staticCounts['total_gateway'],
            'total_ticket' => $staticCounts['total_ticket'],
            'browser' => $browser,
            'platform' => $platform,
            'country' => $country,
            'symbol' => $symbol,
            'order_status_statistics' => $orderStatusStatistics,
        ];

        return view('backend.dashboard', compact('data'));
    }

    private function loginActivityBreakdown(string $attribute): array
    {
        if (! in_array($attribute, ['browser', 'platform'], true)) {
            return [];
        }

        $counts = [];
        $hasColumn = false;

        try {
            $hasColumn = Schema::hasColumn('login_activities', $attribute);
        } catch (\Throwable) {
            // Keep the dashboard usable during partial/rolling deployments.
        }

        if ($hasColumn) {
            $counts = LoginActivities::query()
                ->select($attribute, DB::raw('COUNT(*) AS aggregate_count'))
                ->whereNotNull($attribute)
                ->where($attribute, '!=', '')
                ->groupBy($attribute)
                ->pluck('aggregate_count', $attribute)
                ->map(fn ($count) => (int) $count)
                ->toArray();
        }

        $legacyQuery = LoginActivities::query()->select('login_activities.id', 'login_activities.agent')->whereNotNull('agent')->where('agent', '!=', '');
        if ($hasColumn) {
            $legacyQuery->where(function (Builder $query) use ($attribute) {
                $query->whereNull($attribute)->orWhere($attribute, '');
            });
        }

        $legacyQuery->chunkById(1000, function ($rows) use (&$counts, $attribute) {
            foreach ($rows as $row) {
                $label = LoginActivities::parseClientInfo($row->agent)[$attribute] ?? null;
                if ($label !== null && $label !== '') {
                    $counts[$label] = ($counts[$label] ?? 0) + 1;
                }
            }
        });

        arsort($counts);

        return array_slice($counts, 0, 5, true);
    }

    private function dateWindow(Request $request): array
    {
        $start = $request->filled('start_date')
            ? Date::parse($request->input('start_date'))->startOfDay()
            : Date::now()->subDays(7)->startOfDay();
        $end = $request->filled('end_date')
            ? Date::parse($request->input('end_date'))->endOfDay()
            : Date::now()->endOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $labels = array_fill_keys(generate_date_range_array($start->copy(), $end->copy()), 0);

        return [$start, $end, $labels];
    }

    private function dailyTransactionSum($start, $end, array $types, ?string $status, array $labels): array
    {
        $query = Transaction::query()
            ->whereIn('type', $types)
            ->whereBetween('created_at', [$start, $end]);
        if ($status !== null) {
            $query->where('status', $status);
        }

        $rows = $query
            ->selectRaw('DATE(created_at) AS aggregate_date, SUM(CAST(amount AS DECIMAL(20,8))) AS aggregate_total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('aggregate_total', 'aggregate_date');

        foreach ($rows as $day => $amount) {
            $labels[Date::parse($day)->format('d M')] = (float) $amount;
        }

        return $labels;
    }

    private function dailyOrderSum($start, $end, bool $bnplOnly, array $labels): array
    {
        $rows = Order::query()
            ->where('status', '!=', 'pending')
            ->when($bnplOnly, fn (Builder $query) => $query->where('is_bnpl', true))
            ->whereBetween('order_date', [$start, $end])
            ->selectRaw('DATE(order_date) AS aggregate_date, SUM(total_price) AS aggregate_total')
            ->groupByRaw('DATE(order_date)')
            ->pluck('aggregate_total', 'aggregate_date');

        foreach ($rows as $day => $amount) {
            $labels[Date::parse($day)->format('d M')] = (float) $amount;
        }

        return $labels;
    }

    private function dailyBnplCount($start, $end, array $labels): array
    {
        $rows = Order::query()
            ->where('is_bnpl', true)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) AS aggregate_date, COUNT(*) AS aggregate_total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('aggregate_total', 'aggregate_date');

        foreach ($rows as $day => $count) {
            $labels[Date::parse($day)->format('d M')] = (int) $count;
        }

        return $labels;
    }
}
