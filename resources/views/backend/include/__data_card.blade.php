@can('total-users')
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
        <div class="data-card">
            <div class="icon">
                <i data-lucide="users"></i>
            </div>
            <div class="content">
                <h4 class="count">{{ $data['total_buyers'] }}</h4>
                <p>{{ __('All Buyer') }}</p>
            </div>
            <a class="link" href="{{ route('admin.user.buyers.all') }}"><i data-lucide="external-link"></i></a>
        </div>
    </div>
@endcan
@can('total-users')
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
        <div class="data-card">
            <div class="icon">
                <i data-lucide="store"></i>
            </div>
            <div class="content">
                <h4 class="count">{{ $data['total_merchants'] }}</h4>
                <p>{{ __('All Merchants') }}</p>
            </div>
            <a class="link" href="{{ route('admin.user.merchants.all') }}"><i data-lucide="external-link"></i></a>
        </div>
    </div>
@endcan
@can('disabled-users')
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
        <div class="data-card">
            <div class="icon">
                <i data-lucide="user-round-x"></i>
            </div>
            <div class="content">
                <h4 class="count">{{ $data['disabled_user'] }}</h4>
                <p>{{ __('Disabled Accounts') }}</p>
            </div>
        </div>
    </div>
@endcan
@can('total-staff')
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
        <div class="data-card">
            <div class="icon">
                <i data-lucide="user-cog"></i>
            </div>
            <div class="content">
                <h4 class="count">{{ $data['total_staff'] }}</h4>
                <p>{{ __('Total Staff') }}</p>
            </div>
            <a class="link" href="{{ route('admin.staff.index') }}"><i data-lucide="external-link"></i></a>
        </div>
    </div>
@endcan
@can('total-deposits')
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
        <div class="data-card">
            <div class="icon">
                <i data-lucide="wallet"></i>
            </div>
            <div class="content">
                <h4>{{ $currencySymbol }}<span class="count">{{ $data['total_deposit'] }}</span></h4>
                <p>{{ __('Total Topup') }}</p>
            </div>
            <a class="link" href="{{ route('admin.deposit.history') }}"><i data-lucide="external-link"></i></a>
        </div>
    </div>
@endcan
@can('total-withdraw')
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
        <div class="data-card">
            <div class="icon">
                <i data-lucide="landmark"></i>
            </div>
            <div class="content">
                <h4>{{ $currencySymbol }}<span class="count">{{ $data['total_withdraw'] }}</span></h4>
                <p>{{ __('Total Withdraw') }}</p>
            </div>
            <a class="link" href="{{ route('admin.withdraw.history') }}"><i data-lucide="external-link"></i></a>
        </div>
    </div>
@endcan
@can('total-referral')
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
        <div class="data-card">
            <div class="icon">
                <i data-lucide="link"></i>
            </div>
            <div class="content">
                <h4 class="count">{{ $data['total_referral'] }}</h4>
                <p>{{ __('Total Referral') }}</p>
            </div>
        </div>
    </div>
@endcan
@can('category-list')
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
        <div class="data-card">
            <div class="icon">
                <i data-lucide="list-check"></i>
            </div>
            <div class="content">
                <h4 class="count">{{ $data['total_category'] }}</h4>
                <p>{{ __('All Categories') }}</p>
            </div>
            <a class="link" href="{{ route('admin.category.index') }}"><i data-lucide="external-link"></i></a>

        </div>
    </div>
@endcan
@can('listing-list')
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
        <div class="data-card">
            <div class="icon">
                <i data-lucide="logs"></i>
            </div>
            <div class="content">
                <h4 class="count">{{ $data['total_category'] }}</h4>
                <p>{{ __('Total Listings') }}</p>
            </div>
            <a class="link" href="{{ route('admin.listing.index') }}"><i data-lucide="external-link"></i></a>

        </div>
    </div>
@endcan
@can('coupon-list')
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
        <div class="data-card">
            <div class="icon">
                <i data-lucide="gift"></i>
            </div>
            <div class="content">
                <h4 class="count">{{ $data['total_coupons'] }}</h4>
                <p>{{ __('Coupons') }}</p>
            </div>
            <a class="link" href="{{ route('admin.coupon.index') }}"><i data-lucide="external-link"></i></a>

        </div>
    </div>
@endcan
@can('total-automatic-gateway')
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
        <div class="data-card">
            <div class="icon">
                <i data-lucide="webhook"></i>
            </div>
            <div class="content">
                <h4 class="count">{{ $data['total_gateway'] }}</h4>
                <p>{{ __('Automatic Gateways') }}</p>
            </div>
            <a class="link" href="{{ route('admin.gateway.automatic') }}"><i data-lucide="external-link"></i></a>
        </div>
    </div>
@endcan
@can('total-ticket')
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
        <div class="data-card">
            <div class="icon">
                <i data-lucide="help-circle"></i>
            </div>
            <div class="content">
                <h4 class="count">{{ $data['total_ticket'] }}</h4>
                <p>{{ __('All Tickets') }}</p>
            </div>
            <a class="link" href="{{ route('admin.ticket.index') }}"><i data-lucide="external-link"></i></a>
        </div>
    </div>
@endcan
