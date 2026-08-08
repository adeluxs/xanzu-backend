<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Enums\OrderStatus;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $greeting = $this->greetingText();
        $name = trim((string) ($user->first_name ?: $user->username ?: 'Merchant'));

        $startCurrentWeek = now()->copy()->startOfWeek();
        $endCurrentWeek = now()->copy()->endOfWeek();
        $startLastWeek = now()->copy()->subWeek()->startOfWeek();
        $endLastWeek = now()->copy()->subWeek()->endOfWeek();

        $sales = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.seller_id', $user->id)
            ->whereNotIn('orders.status', [
                OrderStatus::Cancelled->value,
                OrderStatus::Failed->value,
                OrderStatus::Refunded->value,
            ])
            ->selectRaw('COALESCE(SUM(order_items.total_price), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(CASE WHEN orders.is_bnpl = 1 THEN order_items.total_price ELSE 0 END), 0) as total_sales_bnpl')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as total_orders')
            ->selectRaw('COALESCE(SUM(CASE WHEN order_items.created_at BETWEEN ? AND ? THEN order_items.total_price ELSE 0 END), 0) as sales_current_week', [$startCurrentWeek, $endCurrentWeek])
            ->selectRaw('COALESCE(SUM(CASE WHEN order_items.created_at BETWEEN ? AND ? THEN order_items.total_price ELSE 0 END), 0) as sales_last_week', [$startLastWeek, $endLastWeek])
            ->selectRaw('COUNT(DISTINCT CASE WHEN order_items.created_at BETWEEN ? AND ? THEN order_items.order_id END) as orders_current_week', [$startCurrentWeek, $endCurrentWeek])
            ->selectRaw('COUNT(DISTINCT CASE WHEN order_items.created_at BETWEEN ? AND ? THEN order_items.order_id END) as orders_last_week', [$startLastWeek, $endLastWeek])
            ->first();

        $products = Listing::query()
            ->where('seller_id', $user->id)
            ->selectRaw('COUNT(*) as total_products')
            ->selectRaw('SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as products_current_week', [$startCurrentWeek, $endCurrentWeek])
            ->selectRaw('SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as products_last_week', [$startLastWeek, $endLastWeek])
            ->first();

        $withdrawals = Transaction::query()
            ->where('user_id', $user->id)
            ->whereIn('type', [TxnType::Withdraw->value, TxnType::WithdrawAuto->value])
            ->where('status', TxnStatus::Success->value)
            ->selectRaw('COALESCE(SUM(amount), 0) as total_withdraw')
            ->selectRaw('COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as withdraw_current_week', [$startCurrentWeek, $endCurrentWeek])
            ->selectRaw('COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as withdraw_last_week', [$startLastWeek, $endLastWeek])
            ->first();

        $totalSales = (float) ($sales->total_sales ?? 0);
        $totalSalesBnpl = (float) ($sales->total_sales_bnpl ?? 0);
        $totalOrders = (int) ($sales->total_orders ?? 0);
        $salesCurrentWeek = (float) ($sales->sales_current_week ?? 0);
        $salesLastWeek = (float) ($sales->sales_last_week ?? 0);
        $ordersCurrentWeek = (int) ($sales->orders_current_week ?? 0);
        $ordersLastWeek = (int) ($sales->orders_last_week ?? 0);
        $totalProducts = (int) ($products->total_products ?? 0);
        $productsCurrentWeek = (int) ($products->products_current_week ?? 0);
        $productsLastWeek = (int) ($products->products_last_week ?? 0);
        $totalWithdraw = (float) ($withdrawals->total_withdraw ?? 0);
        $withdrawCurrentWeek = (float) ($withdrawals->withdraw_current_week ?? 0);
        $withdrawLastWeek = (float) ($withdrawals->withdraw_last_week ?? 0);

        $bnplSalesPercent = $totalSales > 0 ? round(($totalSalesBnpl / $totalSales) * 100, 2) : 0;

        return $this->successResponse([
            'header' => [
                'welcome' => __('Welcome back, :name', ['name' => $name]),
                'greetings' => $greeting,
                'actions' => [
                    'add_new_listing' => true,
                ],
            ],
            'summary' => [
                'total_balance' => $this->money((float) $user->balance),
                'available_balance' => $this->money((float) $user->balance),
                'total_sales_bnpl' => [
                    'amount' => $this->money($totalSalesBnpl),
                    'change_vs_last_week' => $this->percentChange($salesCurrentWeek, $salesLastWeek),
                ],
                'total_orders' => [
                    'count' => $totalOrders,
                    'change_vs_last_week' => $this->percentChange($ordersCurrentWeek, $ordersLastWeek),
                ],
                'total_withdraw' => [
                    'amount' => $this->money($totalWithdraw),
                    'change_vs_last_week' => $this->percentChange($withdrawCurrentWeek, $withdrawLastWeek),
                ],
                'bnpl_sales_percent' => $bnplSalesPercent,
                'total_product' => [
                    'count' => $totalProducts,
                    'change_vs_last_week' => $productsCurrentWeek - $productsLastWeek,
                ],
            ],
            'charts' => [
                'monthly' => $this->monthlySalesChart($user->id),
                'weekly' => $this->weeklySalesChart($user->id),
            ],
            'recent_orders' => $this->recentOrders($user->id),
            'top_performing_products' => $this->topPerformingProducts($user->id),
        ]);
    }

    public function getUser(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $user->is_email_verified = $user->hasVerifiedEmail();
        $user->balance = number_format((float) $user->balance, 2, '.', '');

        $userFormatted = $user->append('avatar_path')->except(
            'password',
            'remember_token',
            'badge_id',
            'badges',
            'updated_at',
            'email_verified_at'
        );
        $userFormatted['avatar'] = $user->avatar_path;

        return $this->successResponse($userFormatted);
    }

    private function recentOrders(int $merchantId): array
    {
        $rows = OrderItem::query()
            ->where('seller_id', $merchantId)
            ->with([
                'order:id,order_number,status,is_bnpl,buyer_id,created_at',
                'order.buyer:id,first_name,last_name,username',
                'bnplLoan.installments:id,bnpl_item_loan_id',
            ])
            ->latest()
            ->limit(5)
            ->get();

        return $rows->map(function (OrderItem $item) {
            $order = $item->order;
            $buyer = optional($order)->buyer;
            $installments = optional($item->bnplLoan?->installments)->count() ?? 0;

            return [
                'order_id' => optional($order)->order_number,
                'customer' => trim((string) (($buyer->first_name ?? '') . ' ' . ($buyer->last_name ?? ''))) ?: ($buyer->username ?? null),
                'plan' => $order?->is_bnpl
                    ? ($installments > 0 ? $installments . ' installment' : 'Installment')
                    : 'Instant',
                'amount' => $this->money((float) $item->total_price),
                'status' => $this->normalizeOrderStatus((string) optional($order)->status),
                'date' => optional($order?->created_at)?->format('Y-m-d H:i:s'),
            ];
        })->values()->all();
    }

    private function topPerformingProducts(int $merchantId): array
    {
        $rows = OrderItem::query()
            ->leftJoin('listings', 'listings.id', '=', 'order_items.listing_id')
            ->where('order_items.seller_id', $merchantId)
            ->whereHas('order', function ($query) {
                $query->whereNotIn('status', [
                    OrderStatus::Cancelled->value,
                    OrderStatus::Failed->value,
                    OrderStatus::Refunded->value,
                ]);
            })
            ->selectRaw('order_items.listing_id as listing_id')
            ->selectRaw('COALESCE(listings.product_name, order_items.product_name, "N/A") as product_name')
            ->selectRaw('COUNT(order_items.id) as orders_count')
            ->selectRaw('SUM(order_items.total_price) as total_amount')
            ->selectRaw('MAX(COALESCE(listings.is_trending, 0)) as is_trending')
            ->groupBy('order_items.listing_id', 'product_name')
            ->orderByDesc('orders_count')
            ->limit(5)
            ->get();

        return $rows->map(function ($row) {
            return [
                'product_name' => (string) $row->product_name,
                'orders' => (int) $row->orders_count,
                'amount' => $this->money((float) $row->total_amount),
                'status' => ((int) $row->is_trending) === 1 ? 'Trending' : 'Normal',
            ];
        })->values()->all();
    }

    private function monthlySalesChart(int $merchantId): array
    {
        $salesByMonth = OrderItem::query()
            ->where('seller_id', $merchantId)
            ->whereBetween('created_at', [now()->copy()->startOfYear(), now()->copy()->endOfYear()])
            ->whereHas('order', function ($query) {
                $query->whereNotIn('status', [
                    OrderStatus::Cancelled->value,
                    OrderStatus::Failed->value,
                    OrderStatus::Refunded->value,
                ]);
            })
            ->selectRaw('MONTH(created_at) as month_no, SUM(total_price) as amount')
            ->groupBy('month_no')
            ->pluck('amount', 'month_no');

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        return collect($months)->map(function (string $month, int $index) use ($salesByMonth) {
            $monthNo = $index + 1;

            return [
                'month' => $month,
                'amount' => round((float) ($salesByMonth[$monthNo] ?? 0), 2),
            ];
        })->values()->all();
    }

    private function weeklySalesChart(int $merchantId): array
    {
        $startOfWeek = now()->copy()->startOfWeek();
        $endOfWeek = now()->copy()->endOfWeek();

        $salesByDay = OrderItem::query()
            ->where('seller_id', $merchantId)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->whereHas('order', function ($query) {
                $query->whereNotIn('status', [
                    OrderStatus::Cancelled->value,
                    OrderStatus::Failed->value,
                    OrderStatus::Refunded->value,
                ]);
            })
            ->selectRaw('DAYOFWEEK(created_at) as day_no, SUM(total_price) as amount')
            ->groupBy('day_no')
            ->pluck('amount', 'day_no');

        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        return collect($days)->map(function (string $day, int $index) use ($salesByDay) {
            $dayNo = $index + 2;
            if ($dayNo === 8) {
                $dayNo = 1;
            }

            return [
                'month' => $day,
                'amount' => round((float) ($salesByDay[$dayNo] ?? 0), 2),
            ];
        })->values()->all();
    }

    private function weeklySums($query, string $column): array
    {
        $current = (float) (clone $query)
            ->whereBetween('created_at', [now()->copy()->startOfWeek(), now()->copy()->endOfWeek()])
            ->sum($column);

        $last = (float) (clone $query)
            ->whereBetween('created_at', [
                now()->copy()->subWeek()->startOfWeek(),
                now()->copy()->subWeek()->endOfWeek(),
            ])
            ->sum($column);

        return [$current, $last];
    }

    private function weeklyDistinctCounts($query, string $column): array
    {
        $current = (int) (clone $query)
            ->whereBetween('created_at', [now()->copy()->startOfWeek(), now()->copy()->endOfWeek()])
            ->distinct($column)
            ->count($column);

        $last = (int) (clone $query)
            ->whereBetween('created_at', [
                now()->copy()->subWeek()->startOfWeek(),
                now()->copy()->subWeek()->endOfWeek(),
            ])
            ->distinct($column)
            ->count($column);

        return [$current, $last];
    }

    private function percentChange(float|int $current, float|int $last): float
    {
        $current = (float) $current;
        $last = (float) $last;

        if ($last == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $last) / $last) * 100, 2);
    }

    private function normalizeOrderStatus(string $status): string
    {
        return match ($status) {
            OrderStatus::Success->value,
            OrderStatus::Delivered->value,
            OrderStatus::Completed->value => 'Success',
            OrderStatus::Pending->value,
            OrderStatus::WaitingForDelivery->value => 'Pending',
            OrderStatus::Cancelled->value,
            OrderStatus::Failed->value,
            OrderStatus::Refunded->value => 'Failed',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function greetingText(): string
    {
        $hour = now()->hour;

        if ($hour < 12) {
            return __('Good morning');
        }

        if ($hour < 18) {
            return __('Good afternoon');
        }

        return __('Good evening');
    }

    private function money(float $value): string
    {
        return formatCurrency($value);
    }
}
