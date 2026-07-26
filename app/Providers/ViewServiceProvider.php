<?php

namespace App\Providers;

use App\Enums\NavigationType;
use App\Enums\TxnType;
use App\Models\Category;
use App\Models\Chat;
use App\Models\DepositMethod;
use App\Models\Kyc;
use App\Models\LandingPage;
use App\Models\Listing;
use App\Models\Navigation;
use App\Models\Notification;
use App\Models\Page;
use App\Models\Social;
use App\Models\UserNavigation;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Jenssegers\Agent\Agent;
use Remotelywork\Installer\Repository\App;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register modules.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap modules.
     *
     * @return void
     */
    public function boot()
    {

        if (App::dbConnectionCheck()) {
            View::composer(['backend.include.__side_nav', 'backend.setting.site_setting.include.__global'], function ($view) {
                $view->with([
                    'landingSections' => cache()->remember('landingSections', 60 * 60 * 24, function () {
                        return LandingPage::where('locale', 'en')->whereNot('code', 'footer')->where('theme', site_theme())->orderBy('short')->get();
                    }),
                    'pages' => cache()->remember('pages', 60 * 60 * 24, function () {
                        return Page::where('locale', 'en')->where('theme', site_theme())->get();
                    }),
                ]);
            });

            View::composer(['frontend::include.__header', 'frontend::include.user_header', 'frontend::layouts.app', 'frontend::include.__header_auth', 'frontend::user.include.__user_header'], function ($view) {
                $view->with([
                    'navigations' => Navigation::where('status', 1)->header()->orderBy('header_position')
                        ->when(!isPlanModuleEnabled(), function ($query) {
                            $query->whereNOt('url', 'seller-subscription');
                        })
                        ->get(),
                    'categories' => Category::select(['id', 'name', 'image', 'slug'])->isCategory()->active()->orderBy('order')->get(),
                    'firstOrderBonus' => auth()->check() ? auth()->user()->transaction()->where('type', TxnType::ProductOrder)->count() == 0 : true,
                ]);
            });

            View::composer(['frontend::user.include.__user_side_nav', 'frontend::include.common.__user-header'], function ($view) {
                $view->with([
                    'sellerKyc' => Kyc::sellerVerification()->first(),
                    'userNavigation' => UserNavigation::orderBy('position')->when(!isPlanModuleEnabled(), function ($query) {
                        $query->whereNOt('type', 'packages');
                    })->get(),
                ]);
            });

            View::composer(['frontend::include.__footer'], function ($view) {
                $rawNavigation = Navigation::where('status', 1)->footer()->orderBy('footer_position');
                $view->with([
                    'footer_navigation_1' => (clone $rawNavigation)->where('type', 'like', '%' . NavigationType::FooterWidget1->value . '%')->get(),
                ]);
            });

            View::composer(['frontend::include.__footer'], function ($view) {
                $view->with([
                    'socials' => Social::all(),
                ]);
            });

            View::composer(['frontend::*gateway'], function ($view) {
                $gateways = DepositMethod::where('status', 1)->get();
                View::share('gateways', $gateways);
            });

            View::composer(['frontend::home.include.__latest-items'], function ($view) {
                if (site_theme() != 'accxone') {
                    $view->with([
                        'latestItemListing' => Listing::public()->latest()->whereNot('is_flash', 1)->whereNot('is_trending', 1)->take(4)->get(),
                    ]);
                }
            });

            View::composer(['frontend::include.common.chat', 'frontend::chat.include.recent-chat', 'frontend::include.user_header', 'frontend::user.include.__user_header', 'frontend::include.__header'], function ($view) {
                $authUser = auth()->id();
                // get all chat person
                $chattedUserList = [];
                $allChats = Chat::whereHas('sender')
                    ->whereHas('receiver')
                    ->where(function ($query) use ($authUser) {
                        $query->where('sender_id', $authUser)->orWhere('receiver_id', $authUser);
                    })

                    ->select('sender_id', 'receiver_id', 'created_at', 'message', 'id', 'seen')
                    ->latest()
                    ->get()->filter(function ($chat) use (&$chattedUserList) {
                        $checkId = null;

                        $chat->role == 'sender' ? $checkId = $chat->receiver_id : $checkId = $chat->sender_id;

                        if (!in_array($checkId, $chattedUserList)) {
                            $chattedUserList[] = $checkId;

                            return $chat;
                        }

                        return false;

                    });
                $unseenChatCount = $allChats->where('receiver_id', $authUser)->where('seen', false)->count();

                $view->with(['allChats' => $allChats, 'unseenChatCount' => $unseenChatCount]);
            });

            View::composer(['frontend::include.common.notification', 'frontend::include.__header', 'frontend::user.include.__user_header', 'frontend::include.user_header'], function ($view) {

                $authUser = auth()->id();

                $__notification = Notification::with('user')->where('for', 'user')->where('user_id', $authUser);

                $latestNotifications = $__notification->clone()->latest()->take(10)->get();
                $totalUnreadNotification = $__notification->clone()->where('read', 0)->count();
                $totalNotificationCount = $__notification->clone()->get()->count();
                $view->with(['latestNotifications' => $latestNotifications, 'totalUnreadNotification' => $totalUnreadNotification, 'totalNotificationCount' => $totalNotificationCount]);

            });

            View::composer(['*'], function ($view) {
                $view->with([
                    'currencySymbol' => setting('currency_symbol', 'global'),
                    'currency' => setting('site_currency', 'global'),
                ]);
            });

            if (auth('web')) {
                $agent = new Agent;
                View::composer(['frontend*'], function ($view) use ($agent) {
                    $view->with([
                        'user' => auth()->user(),
                        'isMobile' => $agent->isMobile(),
                    ]);
                });
            }
        }
    }
}
