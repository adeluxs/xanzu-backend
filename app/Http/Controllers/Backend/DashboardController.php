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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $transaction = new Transaction;
        $user = User::query();
        $admin = Admin::query();

        $totalDeposit = Transaction::where('status', TxnStatus::Success)->where(function ($query) {
            $query->where('type', TxnType::ManualDeposit)
                ->orWhere('type', TxnType::Deposit);
        });

        $disabledUser = User::where('status', 0)->count();

        $totalStaff = Admin::count();

        $latestUser = User::latest()->take(5)->get();

        $latestOrders = Order::with(['seller', 'buyer', 'items.listing.category'])->latest()->take(8)->get();

        $totalGateway = Gateway::where('status', true)->count();

        $totalWithdraw = Transaction::where('type', [TxnType::Withdraw, TxnType::WithdrawAuto]);

        $withdrawCount = Transaction::where('type', TxnType::Withdraw)
            ->where('status', 'pending')
            ->count();

        $kycCount = User::where('kyc', KYCStatus::Pending)->count();

        $depositCount = Transaction::where('type', TxnType::ManualDeposit)
            ->where('status', 'pending')
            ->count();

        $totalReferral = User::where('ref_id', '!=', null)->count();

        // ============================= Start dashboard statistics =============================================

        $startDate = request()->start_date ? Date::createFromDate(request()->start_date) : Date::now()->subDays(7);
        $endDate = request()->end_date ? Date::createFromDate(request()->end_date) : Date::now();
        $dateArray = array_fill_keys(generate_date_range_array($startDate, $endDate), 0);

        $dateFilter = [request()->start_date ? $startDate : $startDate->subDays(1), $endDate->addDays(1)];

        $depositStatistics = $totalDeposit->whereBetween('created_at', $dateFilter)->get()->groupBy('day')->map(function ($group) {
            return $group->sum('amount');
        })->toArray();

        $depositStatistics = array_replace($dateArray, $depositStatistics);

        $withdrawStatistics = $totalWithdraw->whereBetween('created_at', $dateFilter)->get()->groupBy('day')->map(function ($group) {
            return $group->sum('amount');
        })->toArray();


        $bnplOrderStatistics = Order::where('status', '!=', 'pending')->whereIsBnpl(true)->whereBetween('order_date', $dateFilter)->get()->groupBy('day')->map(function ($group) {
            return $group->sum('total_price');
        })->toArray();
        $bnplOrderStatistics = array_replace($dateArray, $bnplOrderStatistics);
        // $withdrawStatistics = array_replace($dateArray, $withdrawStatistics);

        // order count statistics
        $OrderStatistics = Order::select(['*', DB::raw("DATE_FORMAT(order_date, '%d %b') AS order_date")])->where('status', '!=', 'pending')->whereBetween('order_date', $dateFilter)->get()->groupBy('order_date')->map(function ($group) {
            return $group->sum('total_price');
        })->toArray();
        $OrderStatistics = array_replace($dateArray, $OrderStatistics);

        // dd($OrderStatistics, $withdrawStatistics);

        // ============================= End dashboard statistics =============================================

        // set cache for 1 minute
        $loginActivities = Cache::remember('login-activities', 60, function () {
            return LoginActivities::get();
        });

        $browser = $loginActivities->groupBy('browser')->map->count()->toArray();
        $platform = $loginActivities->groupBy('platform')->map->count()->toArray();

        $country = User::all()->groupBy('country')->map(function ($country) {
            return $country->count();
        })->toArray();

        arsort($country);
        $country = array_slice($country, 0, 5);

        $symbol = setting('currency_symbol', 'global');

        $bnplOrderCount = Order::whereIsBnpl(true)
            ->select([DB::raw('DATE(created_at) as day'), DB::raw('count(*) as bnpl_count')])
            // last 7 days
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('bnpl_count', 'day')->toArray();

        $total_category = Category::count();
        $total_coupons = Coupon::count();
        $total_listing = Listing::count();

        // order grp by status
        $OrderStatusStatistics = Order::select(['status', DB::raw('count(*) as analysis_count')])
            ->when(request()->start_date, function ($query) use ($dateFilter) {
                $query->whereBetween('order_date', $dateFilter);
            }, function ($query) {
                $query->whereDate('order_date', '>=', now()->subDays(7));
            })
            ->groupBy(DB::raw('status'))
            ->oldest('analysis_count')
            ->pluck('analysis_count', 'status');

        $data = [
            'withdraw_count' => $withdrawCount,
            'kyc_count' => $kycCount,
            'deposit_count' => $depositCount,

            'total_buyers' => (clone $user)->where('user_type', 'buyer')->count(),
            'total_merchants' => (clone $user)->where('user_type', 'merchant')->count(),
            'disabled_user' => $disabledUser,
            'latest_user' => $latestUser,
            'latest_orders' => $latestOrders,

            'total_staff' => $totalStaff,

            'total_deposit' => $totalDeposit->sum('amount'),
            'total_withdraw' => $transaction->totalWithdraw()->sum('amount'),
            'total_referral' => $totalReferral,
            'total_category' => $total_category,
            'total_coupons' => $total_coupons,
            'total_listing' => $total_listing,

            'date_label' => $dateArray,
            'deposit_statistics' => $depositStatistics,
            'withdraw_statistics' => $withdrawStatistics,
            'listing_order_statistics' => $OrderStatistics,

            'bnpl_order_statistics' => $bnplOrderCount,

            'start_date' => isset(request()->start_date) ? $startDate : $startDate->addDays(1)->format('m/d/Y'),
            'end_date' => isset(request()->end_date) ? $endDate : $endDate->subDays(1)->format('m/d/Y'),

            'deposit_bonus' => $transaction->totalDepositBonus(),
            'total_gateway' => $totalGateway,
            'total_ticket' => Ticket::count(),

            'browser' => $browser,
            'platform' => $platform,
            'country' => $country,
            'symbol' => $symbol,
        ];
        // Date range filter for statistics
        if (request()->ajax()) {

            if ($request->type == 'site') {
                return response()->json([
                    'date_label' => $dateArray,
                    'deposit_statistics' => $depositStatistics,
                    'withdraw_statistics' => $withdrawStatistics,
                    'listing_order_statistics' => $OrderStatistics,
                    'bnpl_order_statistics' => $bnplOrderCount,
                    'symbol' => $symbol,
                ]);
            }
        }

        return view('backend.dashboard', compact('data'));
    }
}
