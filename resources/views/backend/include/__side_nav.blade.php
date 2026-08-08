<div class="side-nav">
    <div class="side-nav-inside">
        <ul class="side-nav-menu">

            <li class="side-nav-item {{ isActive('admin.dashboard') }}">
                <a href="{{ route('admin.dashboard') }}"><i
                        data-lucide="layout-dashboard"></i><span>{{ __('Dashboard') }}</span></a>
            </li>

            {{-- ************************************************************* Customer Management
            ********************************************************* --}}
            @canany(['customer-list', 'customer-login', 'customer-mail-send', 'customer-basic-manage',
                'customer-balance-add-or-subtract', 'customer-change-password', 'all-type-status'])
                <li class="side-nav-item category-title">
                    <span>{{ __('User Management') }}</span>
                </li>
                <li class="side-nav-item side-nav-dropdown {{ isActive(['admin.user.buyers.*']) }} @yield('buyer_menu')">
                    <a href="javascript:void(0);" class="dropdown-link">
                        <i data-lucide="users-round"></i><span>{{ __('Buyers') }}</span>
                        <span class="right-arrow"><i data-lucide="chevron-down"></i></span></a>
                    <ul class="dropdown-items">
                        @canany(['customer-list', 'customer-login', 'customer-mail-send', 'customer-basic-manage',
                            'customer-balance-add-or-subtract', 'customer-change-password', 'all-type-status'])
                            <li class="{{ isActive('admin.user.buyers.all') }}">
                                <a href="{{ route('admin.user.buyers.all') }}"><i
                                        data-lucide="user-search"></i>{{ __('All Buyers') }}</a>
                            </li>
                            <li class="{{ isActive('admin.user.buyers.new') }}">
                                <a href="{{ route('admin.user.buyers.new') }}"><i
                                        data-lucide="user-plus"></i>{{ __('Add New Buyer') }}</a>
                            </li>
                            <li class="{{ isActive('admin.user.buyers.disabled') }}">
                                <a href="{{ route('admin.user.buyers.disabled') }}"><i
                                        data-lucide="user-round-x"></i>{{ __('Disabled Buyer') }}</a>
                            </li>
                            <li class="{{ isActive('admin.user.buyers.closed') }}">
                                <a href="{{ route('admin.user.buyers.closed') }}"><i
                                        data-lucide="user-round-x"></i>{{ __('Closed Buyer') }}</a>
                            </li>
                        @endcanany
                    </ul>
                </li>

                <li class="side-nav-item side-nav-dropdown {{ isActive(['admin.user.merchants.*']) }} @yield('merchant_menu')">
                    <a href="javascript:void(0);" class="dropdown-link">
                        <i data-lucide="store"></i><span>{{ __('Merchants') }}</span>
                        <span class="right-arrow"><i data-lucide="chevron-down"></i></span></a>
                    <ul class="dropdown-items">
                        @canany(['customer-list', 'customer-login', 'customer-mail-send', 'customer-basic-manage',
                            'customer-balance-add-or-subtract', 'customer-change-password', 'all-type-status'])
                            <li class="{{ isActive('admin.user.merchants.all') }}">
                                <a href="{{ route('admin.user.merchants.all') }}"><i
                                        data-lucide="user-search"></i>{{ __('All Merchants') }}</a>
                            </li>
                            <li class="{{ isActive('admin.user.merchants.new') }}">
                                <a href="{{ route('admin.user.merchants.new') }}"><i
                                        data-lucide="user-plus"></i>{{ __('Add New Merchant') }}</a>
                            </li>
                            <li class="{{ isActive('admin.user.merchants.approved') }}">
                                <a href="{{ route('admin.user.merchants.approved') }}"><i
                                        data-lucide="badge-check"></i>{{ __('Approved Merchants') }}</a>
                            </li>
                            <li class="{{ isActive('admin.user.merchants.request') }}">
                                <a href="{{ route('admin.user.merchants.request') }}"><i
                                        data-lucide="timer"></i>{{ __('Merchant Requests') }}</a>
                            </li>
                            <li class="{{ isActive('admin.user.merchants.rejected') }}">
                                <a href="{{ route('admin.user.merchants.rejected') }}"><i
                                        data-lucide="shield-x"></i>{{ __('Rejected Merchants') }}</a>
                            </li>
                            <li class="{{ isActive('admin.user.merchants.disabled') }}">
                                <a href="{{ route('admin.user.merchants.disabled') }}"><i
                                        data-lucide="user-round-x"></i>{{ __('Disabled Merchant') }}</a>
                            </li>
                            <li class="{{ isActive('admin.user.merchants.closed') }}">
                                <a href="{{ route('admin.user.merchants.closed') }}"><i
                                        data-lucide="user-round-x"></i>{{ __('Closed Merchant') }}</a>
                            </li>
                        @endcanany
                    </ul>
                </li>

                @can('country-list')
                    <li class=" side-nav-item {{ isActive('admin.country.*') }}">
                        <a href="{{ route('admin.country.index') }}">
                            <i data-lucide="globe"></i>
                            <span>
                                {{ __('Countries') }}
                            </span>
                        </a>
                    </li>
                @endcan

            @endcanany

            @canany(['kyc-list', 'kyc-action', 'kyc-form-manage'])
                <li class="side-nav-item side-nav-dropdown {{ isActive(['admin.kyc*']) }}">
                    <a href="javascript:void(0);" class="dropdown-link"><i
                            data-lucide="check-square"></i><span>{{ __('Account Verification') }}</span><span
                            class="right-arrow"><i data-lucide="chevron-down"></i></span></a>
                    <ul class="dropdown-items">
                        @canany(['kyc-list', 'kyc-action'])
                            <li class="{{ isActive('admin.kyc.pending') }}">
                                <a href="{{ route('admin.kyc.pending') }}"><i
                                        data-lucide="airplay"></i>{{ __('Pending Verification') }}</a>
                            </li>
                            <li class="{{ isActive('admin.kyc.rejected') }}">
                                <a href="{{ route('admin.kyc.rejected') }}"><i
                                        data-lucide="file-warning"></i>{{ __('Rejected Verification') }}</a>
                            </li>
                            <li class="{{ isActive('admin.kyc.all') }}">
                                <a href="{{ route('admin.kyc.all') }}"><i
                                        data-lucide="contact"></i>{{ __('All Verification Logs') }}</a>
                            </li>
                        @endcanany
                        @can('kyc-form-manage')
                            <li class="{{ isActive('admin.kyc-form*') }}">
                                <a href="{{ route('admin.kyc-form.index') }}"><i
                                        data-lucide="check-square"></i>{{ __('Verification Options') }}</a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany

            {{-- ************************************************************* Staff Management
            ********************************************************* --}}
            @canany(['role-list', 'role-create', 'role-edit', 'staff-list', 'staff-create', 'staff-edit'])

                <li class="side-nav-item side-nav-dropdown {{ isActive(['admin.roles*', 'admin.staff*']) }}">
                    <a href="javascript:void(0);" class="dropdown-link"><i
                            data-lucide="users"></i><span>{{ __('Admin System Access') }}</span>
                        <span class="right-arrow"><i data-lucide="chevron-down"></i></span></a>
                    <ul class="dropdown-items">
                        @canany(['role-list', 'role-create', 'role-edit'])
                            <li class="{{ isActive('admin.roles*') }}">
                                <a href="{{ route('admin.roles.index') }}"><i
                                        data-lucide="contact"></i><span>{{ __('Manage Roles') }}</span></a>
                            </li>
                        @endcanany
                        @canany(['staff-list', 'staff-create', 'staff-edit'])
                            <li class="{{ isActive('admin.staff*') }}">
                                <a href="{{ route('admin.staff.index') }}"><i
                                        data-lucide="user-cog"></i><span>{{ __('Manage Staffs') }}</span></a>
                            </li>
                        @endcanany
                    </ul>
                </li>
            @endcanany



            {{-- product related modules starts --}}
            <li class="side-nav-item category-title">
                <span>{{ __('Products') }}</span>
            </li>
            @canany(['category-list', 'category-create', 'category-edit', 'category-delete', 'listing-list',
                'listing-create', 'listing-edit', 'listing-delete', 'coupon-list', 'coupon-edit', 'coupon-delete',
                'order-view', 'country-list', 'country-create', 'country-edit', 'country-delete', 'brand-list',
                'brand-create', 'brand-edit', 'brand-delete', 'provider-list', 'provider-create', 'provider-edit',
                'provider-delete', 'courier-list', 'courier-create', 'courier-edit', 'courier-delete'])

                @canany(['category-list', 'category-create', 'category-edit', 'category-delete'])
                    @can('category-list')
                        <li class="side-nav-item {{ isActive('admin.category*') }}">
                            <a href="{{ route('admin.category.index') }}"><i
                                    data-lucide="locate-fixed"></i><span>{{ __('Category') }}</span></a>
                        </li>
                    @endcan
                @endcanany
                @canany(['brand-list', 'brand-create', 'brand-edit', 'brand-delete'])
                    @can('brand-list')
                        <li class="side-nav-item {{ isActive('admin.brand*') }}">
                            <a href="{{ route('admin.brand.index') }}"><i
                                    data-lucide="tag"></i><span>{{ __('Brands') }}</span></a>
                        </li>
                    @endcan
                @endcanany
                @canany(['provider-list', 'provider-create', 'provider-edit', 'provider-delete'])
                    @can('provider-list')
                        <li class="side-nav-item {{ isActive('admin.provider*') }}">
                            <a href="{{ route('admin.provider.index') }}"><i
                                    data-lucide="store"></i><span>{{ __('Providers') }}</span></a>
                        </li>
                    @endcan
                @endcanany
                @canany(['courier-list', 'courier-create', 'courier-edit', 'courier-delete'])
                    @can('courier-list')
                        <li class="side-nav-item {{ isActive('admin.courier*') }}">
                            <a href="{{ route('admin.courier.index') }}"><i
                                    data-lucide="truck"></i><span>{{ __('Courier Partners') }}</span></a>
                        </li>
                    @endcan
                @endcanany

                @canany(['listing-list', 'listing-create', 'listing-edit', 'listing-delete'])
                    @can('listing-list')
                        <li class="side-nav-item {{ isActive('admin.listing*') }}">
                            <a href="{{ route('admin.listing.index') }}"><i
                                    data-lucide="logs"></i><span>{{ __('Listing Items') }}</span></a>
                        </li>
                    @endcan
                @endcanany



                @canany(['order-view'])
                    @can('order-view')
                        <li class="side-nav-item {{ isActive('admin.order*') }}">
                            <a href="{{ route('admin.order.index') }}"><i
                                    data-lucide="list-check"></i><span>{{ __('Orders') }}</span></a>
                        </li>
                    @endcan
                @endcanany

                @can('listing-edit')
                    <li class="side-nav-item {{ isActive('admin.reviews.index') }}">
                        <a href="{{ route('admin.reviews.index') }}">
                            <i data-lucide="star"></i>
                            <span>{{ __('Listing Reviews') }}</span>
                            @if ($pendingReviewsCount = \App\Models\ListingReview::pending()->count())
                                <span class="site-badge pending">{{ $pendingReviewsCount }}</span>
                            @endif
                        </a>
                    </li>
                @endcan

                @canany(['coupon-list', 'coupon-edit', 'coupon-delete'])
                    @can('coupon-list')
                        <li class="side-nav-item {{ isActive('admin.coupon*') }}">
                            <a href="{{ route('admin.coupon.index') }}"><i
                                    data-lucide="gift"></i><span>{{ __('Coupons') }}</span></a>
                        </li>
                    @endcan
                @endcanany

                <li class="side-nav-item {{ isActive('admin.banner*') }}">
                    <a href="{{ route('admin.banner.index') }}"><i
                            data-lucide="image"></i><span>{{ __('Banners') }}</span></a>
                </li>

            @endcan

            {{-- ************************************************************* Transactions
            ********************************************************* --}}
            @canany(['transaction-list', 'user-paybacks', 'bank-profit'])
                <li class="side-nav-item category-title">
                    <span>{{ __('Transactions') }}</span>
                </li>
                @can('transaction-list')
                    <li class="side-nav-item {{ isActive('admin.transactions') }}">
                        <a href="{{ route('admin.transactions') }}"><i
                                data-lucide="cast"></i><span>{{ __('Transactions') }}</span></a>
                    </li>
                @endcan

            @endcanany



            {{-- product related modules ends --}}
            {{-- ************************************************************* Essentials
            ********************************************************* --}}
            @canany(['automatic-gateway-manage', 'manual-gateway-manage', 'deposit-list', 'deposit-action',
                'withdraw-list', 'withdraw-method-manage', 'withdraw-action', 'referral-create', 'manage-referral',
                'referral-edit', 'referral-delete', 'manage-credit-limit', 'credit-limit-edit', 'credit-limit-create',
                'category-list', 'category-create', 'category-edit', 'category-delete', 'reward-earning-list',
                'reward-earning-create', 'reward-earning-edit', 'reward-earning-delete', 'reward-redeem-list',
                'reward-redeem-create', 'reward-redeem-edit', 'reward-redeem-delete', 'listing-list', 'listing-create',
                'listing-edit', 'listing-delete', 'coupon-list', 'coupon-edit', 'coupon-delete', 'order-view'])
                <li class="side-nav-item category-title">
                    <span>{{ __('Payment & Essentials') }}</span>
                </li>
                @canany(['automatic-gateway-manage', 'manual-gateway-manage', 'deposit-list', 'deposit-action'])
                    @can('automatic-gateway-manage')
                        <li class="side-nav-item {{ isActive('admin.gateway*') }}">
                            <a href="{{ route('admin.gateway.automatic') }}"><i
                                    data-lucide="door-open"></i><span>{{ __('Automatic Gateways') }}</span></a>
                        </li>
                    @endcan

                    <li class="side-nav-item side-nav-dropdown {{ isActive(['admin.deposit*']) }}">
                        <a href="javascript:void(0);" class="dropdown-link"><i
                                data-lucide="arrow-down-circle"></i><span>{{ __('Payment Settings') }}</span><span
                                class="right-arrow"><i data-lucide="chevron-down"></i></span></a>
                        <ul class="dropdown-items">

                            @can('automatic-gateway-manage')
                                <li class="{{ isActive('admin.deposit.method.list', 'auto') }}"><a
                                        href="{{ route('admin.deposit.method.list', 'auto') }}"><i
                                            data-lucide="workflow"></i>{{ __('Automatic Methods') }}</a></li>
                            @endcan

                            @can('manual-gateway-manage')
                                <li class="{{ isActive('admin.deposit.method.list', 'manual') }}"><a
                                        href="{{ route('admin.deposit.method.list', 'manual') }}"><i
                                            data-lucide="compass"></i>{{ __('Manual Methods') }}</a></li>
                            @endcan

                            @canany(['deposit-list', 'deposit-action'])
                                <li class="{{ isActive('admin.deposit.manual.pending') }}"><a
                                        href="{{ route('admin.deposit.manual.pending') }}"><i
                                            data-lucide="columns"></i>{{ __('Pending Payment') }}</a>
                                </li>
                                <li class="{{ isActive('admin.deposit.history') }}"><a
                                        href="{{ route('admin.deposit.history') }}"><i
                                            data-lucide="clipboard-check"></i>{{ __('Manual Payment History') }}</a></li>
                            @endcanany
                        </ul>
                    </li>
                @endcanany

                @canany(['withdraw-list', 'withdraw-method-manage', 'withdraw-action', 'withdraw-schedule'])
                    <li class="side-nav-item side-nav-dropdown  {{ isActive(['admin.withdraw*']) }}">
                        <a href="javascript:void(0);" class="dropdown-link"><i
                                data-lucide="landmark"></i><span>{{ __('Payout Settings') }}</span><span
                                class="right-arrow"><i data-lucide="chevron-down"></i></span></a>
                        <ul class="dropdown-items">
                            @can('withdraw-method-manage')
                                <li class="{{ isActive('admin.withdraw.method.list', 'auto') }}">
                                    <a href="{{ route('admin.withdraw.method.list', 'auto') }}"><i
                                            data-lucide="workflow"></i>{{ __('Automatic Methods') }}</a>
                                </li>
                                <li class="{{ isActive('admin.withdraw.method.list', 'manual') }}">
                                    <a href="{{ route('admin.withdraw.method.list', 'manual') }}"><i
                                            data-lucide="compass"></i>{{ __('Manual Methods') }}</a>
                                </li>
                            @endcan
                            @canany(['withdraw-list', 'withdraw-action'])
                                <li class="{{ isActive('admin.withdraw.pending') }}"><a
                                        href="{{ route('admin.withdraw.pending') }}"><i
                                            data-lucide="wallet"></i>{{ __('Pending Payouts') }}</a>
                                </li>
                            @endcanany
                            @can('withdraw-schedule')
                                <li class="{{ isActive('admin.withdraw.schedule') }}"><a
                                        href="{{ route('admin.withdraw.schedule') }}"><i
                                            data-lucide="alarm-clock"></i>{{ __('Payout Schedule') }}</a>
                                </li>
                            @endcan
                            @can('withdraw-list')
                                <li class="{{ isActive('admin.withdraw.history') }}"><a
                                        href="{{ route('admin.withdraw.history') }}"><i
                                            data-lucide="piggy-bank"></i>{{ __('Payout History') }}</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['referral-create', 'manage-referral', 'referral-edit', 'referral-delete'])
                    <li class="side-nav-item {{ isActive('admin.referral.*') }}">
                        <a href="{{ route('admin.referral.index') }}"><i
                                data-lucide="align-end-horizontal"></i><span>{{ __('Referral') }}</span></a>
                    </li>
                @endcanany

                @can('manage-card-application')
                    <li class="side-nav-item {{ isActive('admin.card-application.*') }}">
                        <a href="{{ route('admin.card-application.index') }}"><i
                                data-lucide="credit-card"></i><span>{{ __('Card Applications') }}</span></a>
                    </li>
                @endcan

                @canany(['manage-credit-limit', 'credit-limit-create', 'credit-limit-edit'])
                    <li class="side-nav-item {{ isActive('admin.credit-limit.*') }}">
                        <a href="{{ route('admin.credit-limit.index') }}"><i
                                data-lucide="credit-card"></i><span>{{ __('Credit Limits') }}</span></a>
                    </li>
                @endcanany

                @canany(['manage-credit-limit', 'credit-limit-create', 'credit-limit-edit'])
                    <li class="side-nav-item {{ isActive('admin.split.*') }}">
                        <a href="{{ route('admin.split.index') }}"><i
                                data-lucide="layers"></i><span>{{ __('Payment Splits') }}</span></a>
                    </li>
                @endcanany


            @endcanany



            {{-- ************************************************************* Site Essentials
            ********************************************************* --}}
            @canany(['landing-page-manage', 'page-manage', 'footer-manage', 'navigation-manage', 'custom-css', 'site-setting', 'email-setting', 'plugin-setting', 'language-setting'])
                <li class="side-nav-item category-title">
                    <span>{{ __('Settings & Appearance') }}</span>
                </li>

                {{-- ************************************************************* Site Settings
            ********************************************************* --}}
                @canany(['site-setting', 'email-setting', 'plugin-setting', 'page-manage', 'language-setting',
                    'sms-setting', 'push-notification-setting', 'notification-tune-setting'])
                    <li
                        class="side-nav-item side-nav-dropdown {{ isActive(['admin.settings*', 'admin.transfer-limit.*', 'admin.language*', 'admin.page.setting']) }}">
                        <a href="javascript:void(0);" class="dropdown-link"><i data-lucide="settings"></i>
                            <span>{{ __('Settings') }}</span><span class="right-arrow"><i
                                    data-lucide="chevron-down"></i></span></a>
                        <ul class="dropdown-items">
                            @can('site-setting')
                                <li class="{{ isActive('admin.settings.site') }}">
                                    <a href="{{ route('admin.settings.site') }}"><i
                                            data-lucide="settings-2"></i>{{ __('Site Settings') }}</a>
                                </li>
                            @endcan
                            @can('site-setting')
                                <li class="{{ isActive('admin.settings.transfer') }}">
                                    <a href="{{ route('admin.settings.transfer') }}"><i
                                            data-lucide="send"></i>{{ __('Transfer Settings') }}</a>
                                </li>
                                <li class="{{ isActive('admin.transfer-limit.*') }}">
                                    <a href="{{ route('admin.transfer-limit.index') }}"><i
                                            data-lucide="gauge"></i>{{ __('Transfer Limits') }}</a>
                                </li>
                            @endcan
                            @can('email-setting')
                                <li class="{{ isActive('admin.settings.mail') }}">
                                    <a href="{{ route('admin.settings.mail') }}"><i
                                            data-lucide="inbox"></i>{{ __('Email Settings') }}</a>
                                </li>
                            @endcan
                            @can('site-setting')
                                <li class="{{ isActive('admin.settings.plugin', 'ai') }}">
                                    <a href="{{ route('admin.settings.plugin', 'ai') }}"><i
                                            data-lucide="search-code"></i>{{ __('AI Provider Settings') }}</a>
                                </li>
                            @endcan
                            @can('site-setting')
                                <li class="{{ isActive('admin.settings.seo.meta') }}">
                                    <a href="{{ route('admin.settings.seo.meta') }}"><i
                                            data-lucide="search-code"></i>{{ __('SEO Meta Settings') }}</a>
                                </li>
                            @endcan
                            @can('language-setting')
                                <li class="{{ isActive('admin.language*') }}">
                                    <a href="{{ route('admin.language.index') }}"><i
                                            data-lucide="languages"></i><span>{{ __('Language Settings') }}</span></a>
                                </li>
                            @endcan
                            @can('page-manage')
                                <li class="side-nav-item {{ isActive('admin.page.setting') }}">
                                    <a href="{{ route('admin.page.setting') }}"><i
                                            data-lucide="layout"></i><span>{{ __('Register Fields Settings') }}</span></a>
                                </li>
                            @endcan

                            @can('plugin-setting')
                                <li class="{{ isActive('admin.settings.plugin', 'system') }}">
                                    <a href="{{ route('admin.settings.plugin', 'system') }}"><i
                                            data-lucide="toy-brick"></i>{{ __('Plugin Settings') }}</a>
                                </li>
                                @can('sms-setting')
                                    <li class="{{ isActive('admin.settings.plugin', 'sms') }}">
                                        <a href="{{ route('admin.settings.plugin', 'sms') }}"><i
                                                data-lucide="message-circle"></i>{{ __('SMS Settings') }}</a>
                                    </li>
                                @endcan
                                @can('push-notification-setting')
                                    <li class="{{ isActive('admin.settings.plugin', 'notification') }}">
                                        <a href="{{ route('admin.settings.plugin', 'notification') }}"><i
                                                data-lucide="bell-ring"></i>{{ __('Push Notification') }}</a>
                                    </li>
                                @endcan
                                @can('notification-tune-setting')
                                    <li class="{{ isActive('admin.settings.notification.tune') }}">
                                        <a href="{{ route('admin.settings.notification.tune') }}"><i
                                                data-lucide="volume-2"></i>{{ __('Notification Tune') }}</a>
                                    </li>
                                @endcan
                            @endcan

                        </ul>
                    </li>
                @endcanany

                {{-- ************************************************************* App Settings
            ********************************************************* --}}
                @canany('app-settings')
                    <li class="side-nav-item category-title">
                        <span>{{ __('App Settings') }}</span>
                    </li>
                    <li class="side-nav-item side-nav-dropdown {{ isActive(['admin.app.*']) }}">
                        <a href="javascript:void(0);" class="dropdown-link"><i data-lucide="settings"></i>
                            <span>{{ __('App Settings') }}</span><span class="right-arrow">
                                <i data-lucide="chevron-down"></i>
                            </span>
                        </a>
                        <ul class="dropdown-items">
                            <li class="{{ isActive('admin.app.splash.screen') }}">
                                <a href="{{ route('admin.app.splash.screen') }}">
                                    <i data-lucide="smartphone"></i>
                                    {{ __('Onboarding Screen') }}
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcanany

                @can('landing-page-manage')
                    {{-- site theme Management --}}
                    <li class="side-nav-item side-nav-dropdown  {{ isActive(['admin.theme*', 'admin.custom-css']) }}">
                        <a href="javascript:void(0);" class="dropdown-link"><i
                                data-lucide="palette"></i><span>{{ __('Appearance') }}</span><span class="right-arrow"><i
                                    data-lucide="chevron-down"></i></span>
                        </a>
                        <ul class="dropdown-items">
                            <li class="{{ isActive('admin.theme.site') }}">
                                <a href="{{ route('admin.theme.site') }}"><i
                                        data-lucide="roller-coaster"></i>{{ __('Site Theme') }}</a>
                            </li>
                        </ul>
                    </li>
                    {{-- end site theme Management --}}

                    <li
                        class="side-nav-item side-nav-dropdown  {{ isActive(['admin.page.section.section*', 'admin.page.section.management*', 'admin.footer-content']) }}">
                        <a href="javascript:void(0);" class="dropdown-link"><i
                                data-lucide="home"></i><span>{{ __('Landing Page') }}</span><span class="right-arrow"><i
                                    data-lucide="chevron-down"></i></span></a>
                        <ul class="dropdown-items">
                            @can('page-manage')
                                <li class="side-nav-item {{ isActive('admin.page.section.management*') }}">
                                    <a href="{{ route('admin.page.section.management') }}"><i
                                            data-lucide="list-end"></i><span>{{ __('Section Management') }}</span></a>
                                </li>
                            @endcan
                            @foreach ($landingSections as $section)
                                <li class="@if (request()->is('admin/page/section/' . $section->code)) active @endif">
                                    <a href="{{ route('admin.page.section.section', $section->code) }}"><i
                                            data-lucide="egg"></i>{{ $section->name }}</a>
                                </li>
                            @endforeach
                            @can('footer-manage')
                                <li class="side-nav-item {{ isActive('admin.footer-content') }}">
                                    <a href="{{ route('admin.footer-content') }}"><i
                                            data-lucide="list-end"></i><span>{{ __('Footer Contents') }}</span></a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('page-manage')
                    <li class="side-nav-item side-nav-dropdown {{ isActive(['admin.page.edit*', 'admin.page.create']) }}">
                        <a href="javascript:void(0);" class="dropdown-link"><i
                                data-lucide="layout-grid"></i><span>{{ __('Pages') }}</span><span class="right-arrow"><i
                                    data-lucide="chevron-down"></i></span></a>
                        <ul class="dropdown-items">
                            @foreach ($pages as $page)
                                <li class="@if (request()->is('admin/page/edit/' . $page->code)) active @endif">
                                    <a href="{{ route('admin.page.edit', $page->code) }}"><i
                                            data-lucide="egg"></i>{{ $page->title }}</a>
                                </li>
                            @endforeach
                            <li class="{{ isActive('admin.page.create') }}">
                                <a href="{{ route('admin.page.create') }}"><i
                                        data-lucide="egg"></i>{{ __('Add New Page') }}</a>
                            </li>
                        </ul>
                    </li>
                @endcan
                @can('navigation-manage')
                    <li
                        class="side-nav-item {{ isActive(['admin.navigation.*', 'admin.navigation.*'], classForArr: 'active') }}">
                        <a href="{{ route('admin.navigation.menu') }}"><i
                                data-lucide="menu"></i><span>{{ __('Site Navigations') }}</span></a>
                    </li>
                @endcan

            @endcanany

            {{-- ************************************************************* Support & Newsletter
            ********************************************************* --}}

            @canany(['subscriber-list', 'subscriber-mail-send', 'support-ticket-list', 'support-ticket-action',
                'email-template', 'sms-template', 'sms-template', 'push-notification-template'])
                <li class="side-nav-item category-title">
                    <span>{{ __('Support & Newsletter') }}</span>
                </li>

                @canany(['template-list', 'template-edit', 'template-update', 'template-delete'])
                    <li class="side-nav-item {{ isActive('admin.template*') }}">
                        <a href="{{ route('admin.template.index') }}">
                            <i data-lucide="mail"></i>
                            <span>
                                {{ __('Templates') }}
                            </span>
                        </a>
                    </li>
                @endcanany

                @canany(['subscriber-list', 'subscriber-mail-send', 'support-ticket-list', 'support-ticket-action'])
                    <li class="side-nav-item side-nav-dropdown {{ isActive(['admin.subscriber', 'admin.ticket*']) }}">
                        <a href="javascript:void(0);" class="dropdown-link">
                            <i data-lucide="wrench"></i><span>{{ __('Subscriber & Support') }}</span>
                            <span class="right-arrow"><i data-lucide="chevron-down"></i></span>
                        </a>
                        <ul class="dropdown-items">
                            @canany(['subscriber-list', 'subscriber-mail-send'])
                                <li class="{{ isActive('admin.subscriber') }}">
                                    <a href="{{ route('admin.subscriber') }}"><i
                                            data-lucide="mail-open"></i><span>{{ __('All Subscribers') }}</span></a>
                                </li>
                            @endcanany
                            @canany(['support-ticket-list', 'support-ticket-action'])
                                <li class="{{ isActive('admin.ticket*') }}">
                                    <a href="{{ route('admin.ticket.index') }}"><i
                                            data-lucide="wrench"></i><span>{{ __('Support Tickets') }}</span></a>
                                </li>
                            @endcanany
                        </ul>
                    </li>
                @endcanany
            @endcanany

            @canany(['manage-cron-job', 'cron-job-create', 'cron-job-edit', 'cron-job-logs', 'cron-job-run',
                'cron-job-delete', 'clear-cache', 'application-details'])
                <li class="side-nav-item category-title">
                    <span>{{ __('System') }}</span>
                </li>
                <li
                    class="side-nav-item side-nav-dropdown {{ isActive(['admin.clear-cache', 'admin.application-info', 'admin.cron.jobs.*']) }}">
                    <a href="javascript:void(0);" class="dropdown-link">
                        <i data-lucide="power"></i><span>{{ __('System') }}</span>
                        <span class="right-arrow"><i data-lucide="chevron-down"></i></span>
                    </a>
                    <ul class="dropdown-items">
                        @can('manage-cron-job')
                            <li class="{{ isActive('admin.cron.jobs.*') }}">
                                <a href="{{ route('admin.cron.jobs.index') }}"><i
                                        data-lucide="alarm-clock"></i><span>{{ __('Cron Jobs') }}</span></a>
                            </li>
                        @endcan
                        @can('clear-cache')
                            <li class="{{ isActive('admin.clear-cache') }}">
                                <a href="{{ route('admin.clear-cache') }}"><i
                                        data-lucide="trash-2"></i><span>{{ __('Clear Cache') }}</span></a>
                            </li>
                        @endcan
                        @can('application-details')
                            <li class="{{ isActive('admin.application-info') }}">
                                <a href="{{ route('admin.application-info') }}">
                                    <i data-lucide="app-window"></i>
                                    <span>{{ __('Application Details') }}</span>
                                    <span class="badge yellow-color">{{ config('app.version') }}</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany
        </ul>
    </div>
</div>
