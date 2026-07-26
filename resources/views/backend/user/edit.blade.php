@extends('backend.layouts.app')
@section('title')
    {{ __('Customer Details') }}
@endsection
@section($user->user_type . '_menu', 'show')
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Details of') . ' ' . $user->first_name . ' ' . $user->last_name }}
                            </h2>
                            <a href="{{ route('admin.user.index') }}" class="title-btn"><i
                                    data-lucide="corner-down-left"></i>{{ __('Back') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-xxl-3 col-xl-6 col-lg-8 col-md-6 col-sm-12">
                    <div class="profile-card">
                        <div class="top">
                            <div class="avatar">
                                <div class="avatar-face">
                                    @if (null != $user->avatar)
                                        <div class="avatar-face">
                                            <img class="avatar-img" src="{{ $user->avatar_path }}"
                                                alt="{{ $user->full_name }}" />
                                        </div>
                                    @elseif (isset($first_name, $last_name))
                                        <div class="avatar-text">{{ $user->avatar_text }}</div>
                                    @else
                                        <div class="avatar-text"><img class="avatar-img" src="{{ $user->avatar_path }}"
                                                alt="{{ $user->full_name }}" /></div>
                                    @endif
                                    <div class="my-2">
                                        @if ($user->user_type == 'merchant')
                                            <div class="site-badge success">{{ __('Merchant') }}</div>
                                        @else
                                            <div class="site-badge success">{{ __('Buyer') }}</div>
                                        @endif
                                    </div>



                                </div>
                            </div>
                            <div class="title-des">
                                <h4>{{ $user->first_name . ' ' . $user->last_name }}</h4>
                                <p>{{ ucwords($user->city) }}@if ($user->city != '')
                                        ,
                                    @endif{{ $user->country }}</p>
                                @if ($user->activities->count() > 0)
                                    @php
                                        $lastLogin = $user->activities->sortByDesc('created_at')->first();
                                        $lastLoginDateTime = optional($lastLogin)->created_at->format('d-m-Y H:i:s');
                                    @endphp
                                    <p>{{ __('Last Login:') }} {{ $lastLoginDateTime }}</p>
                                @else
                                    <p>{{ __('This user has not logged in yet.') }}</p>
                                @endif
                            </div>
                            <div class="btns">
                                @can('customer-mail-send')
                                    <span type="button" data-bs-toggle="modal" data-bs-target="#sendEmail"><a
                                            href="javascript:void(0);" class="site-btn-round blue-btn" data-bs-toggle="tooltip"
                                            title="" data-bs-original-title="Send Email"><i
                                                data-lucide="mail"></i></a></span>
                                @endcan
                                @can('customer-balance-add-or-subtract')
                                    <span data-bs-toggle="modal" data-bs-target="#addSubBal">
                                        <a href="javascript:void(0);" type="button" class="site-btn-round primary-btn"
                                            data-bs-toggle="tooltip" title="" data-bs-placement="top"
                                            data-bs-original-title="Fund Add or Subtract">
                                            <i data-lucide="wallet"></i></a></span>
                                @endcan
                                @can('customer-basic-manage')
                                    <a href="#" class="site-btn-round red-btn" id="deleteModal" data-bs-toggle="modal"
                                        data-bs-target="#delete" title="Delete User"><i data-lucide="trash-2"></i></a>

                                    <!-- Modal for Popup Box -->
                                    @include('backend.user.include.__delete_popup', ['id' => $user->id])
                                    <!-- Modal for Popup Box End-->
                                @endcan
                            </div>
                        </div>
                        <div class="site-card">
                            <div class="site-card-body">
                                <div class="row">
                                    <div class="col-xl-12">
                                        <div class="admin-user-balance-card">
                                            <div class="wallet-name">
                                                <div class="name">{{ __('Account Balance') }}</div>
                                                <div class="chip-icon">
                                                    <img class="chip" src="{{ asset('backend/materials/chip.png') }}"
                                                        alt="" />
                                                </div>
                                            </div>
                                            <div class="wallet-info">
                                                <div class="wallet-id">{{ setting('site_currency', 'global') }}</div>
                                                <div class="balance">
                                                    {{ setting('currency_symbol', 'global') . $user->balance }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Status Update -->
                        @can('all-type-status')
                            @include('backend.user.include.__status_update')
                        @endcan
                        <!-- User Status Update End-->

                    </div>
                </div>


                <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                    <div class="row">
                        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                            <div class="data-card">
                                <div class="icon">
                                    <i data-lucide="list"></i>
                                </div>
                                <div class="content">
                                    <h4>{{ $currencySymbol }}<span class="count">{{ $user->balance ?? 0 }}</span> </h4>
                                    <p>{{ __('Account Balance') }}</p>
                                </div>
                            </div>
                        </div>
                        @if ($user->is_seller)
                            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                                <div class="data-card">
                                    <div class="icon">
                                        <i data-lucide="list"></i>
                                    </div>
                                    <div class="content">
                                        <h4><span class="count">{{ $statistics['total_listings'] ?? 0 }}</span> </h4>
                                        <p>{{ __('Total Listings') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                                <div class="data-card">
                                    <div class="icon">
                                        <i data-lucide="ticket"></i>
                                    </div>
                                    <div class="content">
                                        <h4><span class="count">{{ $statistics['total_coupons'] ?? 0 }}</span> </h4>
                                        <p>{{ __('Total Coupons') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                                <div class="data-card">
                                    <div class="icon">
                                        <i data-lucide="shopping-cart"></i>
                                    </div>
                                    <div class="content">
                                        <h4>{{ $currencySymbol }}<span
                                                class="count">{{ $user->total_amount_sold }}</span> </h4>
                                        <p>{{ __('Total Sold') }} </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                                <div class="data-card">
                                    <div class="icon">
                                        <i data-lucide="box"></i>
                                    </div>
                                    <div class="content">
                                        <h4><span class="count">{{ $user->total_sold ?? 0 }}</span> </h4>
                                        <p>{{ __('Sold Products') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                                <div class="data-card">
                                    <div class="icon">
                                        <i data-lucide="star"></i>
                                    </div>
                                    <div class="content">
                                        <h4><span class="count">{{ $user->order_success_rate }}</span>% </h4>
                                        <p>{{ __('Success Rate') }}</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- buyer --}}
                            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                                <div class="data-card">
                                    <div class="icon">
                                        <i data-lucide="credit-card"></i>
                                    </div>
                                    <div class="content">
                                        <h4>{{ $currencySymbol }}<span
                                                class="count">{{ $user->credit_limit_amount }}</span>
                                        </h4>
                                        <p>{{ __('Credit Limit') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                                <div class="data-card">
                                    <div class="icon">
                                        <i data-lucide="credit-card"></i>
                                    </div>
                                    <div class="content">
                                        <h4>{{ $currencySymbol }}<span
                                                class="count">{{ $user->used_credit_limit_amount }}</span>
                                        </h4>
                                        <p>{{ __('Used Credit Limit') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                                <div class="data-card">
                                    <div class="icon">
                                        <i data-lucide="credit-card"></i>
                                    </div>
                                    <div class="content">
                                        <h4>{{ $currencySymbol }}<span
                                                class="count">{{ $user->remaining_credit_limit_amount }}</span>
                                        </h4>
                                        <p>{{ __('Remaining Credit Limit') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                                <div class="data-card">
                                    <div class="icon">
                                        <i data-lucide="award"></i>
                                    </div>
                                    <div class="content">
                                        <h4>{{ $user->creditLimit?->level ?? __('N/A') }}</h4>
                                        <p>{{ __('Credit Limit Level') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                                <div class="data-card">
                                    <div class="icon">
                                        <i data-lucide="ticket-check"></i>
                                    </div>
                                    <div class="content">
                                        <h4>{{ $currencySymbol }}<span
                                                class="count">{{ $user->total_amount_purchased }}</span>
                                        </h4>
                                        <p>{{ __('Total Purchased') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                                <div class="data-card">
                                    <div class="icon">
                                        <i data-lucide="ticket-check"></i>
                                    </div>
                                    <div class="content">
                                        <h4><span class="count">{{ $user->total_purchased ?? 0 }}</span>
                                        </h4>
                                        <p>{{ __('Purchased Products') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                            <div class="data-card">
                                <div class="icon">
                                    <i data-lucide="arrow-left-right"></i>
                                </div>
                                <div class="content">
                                    <h4><span class="count">{{ $statistics['total_transactions'] }}</span> </h4>
                                    <p>{{ __('Total Transactions') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                            <div class="data-card">
                                <div class="icon">
                                    <i data-lucide="plus-circle"></i>
                                </div>
                                <div class="content">
                                    <h4>{{ $currencySymbol }}<span class="count">{{ $statistics['total_topup'] }}</span>
                                    </h4>
                                    <p>{{ __('Total :type', ['type' => $user->is_seller ? 'Balance' : 'Topup']) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                            <div class="data-card">
                                <div class="icon">
                                    <i data-lucide="box"></i>
                                </div>
                                <div class="content">
                                    <h4>{{ $currencySymbol }}<span
                                            class="count">{{ $statistics['total_withdraw'] }}</span>
                                    </h4>
                                    <p>{{ __('Total Withdraw') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                            <div class="data-card">
                                <div class="icon">
                                    <i data-lucide="alert-triangle"></i>
                                </div>
                                <div class="content">
                                    <h4 class="count">{{ $statistics['all_referral'] }}</h4>
                                    <p>{{ __('Total Referral') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                            <div class="data-card">
                                <div class="icon">
                                    <i data-lucide="message-circle"></i>
                                </div>
                                <div class="content">
                                    <h4 class="count">{{ $statistics['total_tickets'] }}</h4>
                                    <p>{{ __('Tickets') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="site-tab-bars">
                        <ul class="nav nav-pills" id="pills-tab" role="tablist">
                            @canany(['customer-basic-manage', 'customer-change-password'])
                                <li class="nav-item" role="presentation">
                                    <a href="{{ route('admin.user.edit', $user->id) }}"
                                        class="nav-link {{ !request()->has('tab') ? 'active' : '' }}"><i
                                            data-lucide="user"></i>{{ __('Information') }}</a>
                                </li>
                            @endcanany

                            <li class="nav-item" role="presentation">
                                <a href="{{ route('admin.user.edit', ['user' => $user->id, 'tab' => 'earnings']) }}"
                                    class="nav-link {{ request('tab') == 'earnings' ? 'active' : '' }}"><i
                                        data-lucide="credit-card"></i>{{ __('Earnings') }}</a>
                            </li>
                            @if ($user->is_seller)
                                <li class="nav-item" role="presentation">
                                    <a href="{{ route('admin.user.edit', ['user' => $user->id, 'tab' => 'listings']) }}"
                                        class="nav-link {{ request('tab') == 'listings' ? 'active' : '' }}"><i
                                            data-lucide="list"></i>{{ __('Listings') }}</a>
                                </li>
                            @else
                                {{-- purchase --}}
                                <li class="nav-item" role="presentation">
                                    <a href="{{ route('admin.user.edit', ['user' => $user->id, 'tab' => 'purchase']) }}"
                                        class="nav-link {{ request('tab') == 'purchase' ? 'active' : '' }}"><i
                                            data-lucide="shopping-cart"></i>{{ __('Purchase') }}</a>
                                </li>
                            @endif


                            @can('transaction-list')
                                <li class="nav-item" role="presentation">
                                    <a href="{{ route('admin.user.edit', ['user' => $user->id, 'tab' => 'transactions']) }}"
                                        class="nav-link {{ request('tab') == 'transactions' ? 'active' : '' }}"><i
                                            data-lucide="cast"></i>{{ __('Transactions') }}</a>
                                </li>
                            @endcan

                            <li class="nav-item" role="presentation">
                                <a href="{{ route('admin.user.edit', ['user' => $user->id, 'tab' => 'referral']) }}"
                                    class="nav-link {{ request('tab') == 'referral' ? 'active' : '' }}"><i
                                        data-lucide="network"></i>{{ __('Referral') }}</a>
                            </li>

                            @canany(['support-ticket-list', 'support-ticket-action'])
                                <li class="nav-item" role="presentation">
                                    <a href="{{ route('admin.user.edit', ['user' => $user->id, 'tab' => 'ticket']) }}"
                                        class="nav-link {{ request('tab') == 'ticket' ? 'active' : '' }}"><i
                                            data-lucide="wrench"></i>{{ __('Ticket') }}</a>
                                </li>
                            @endcanany
                        </ul>
                    </div>

                    <div class="tab-content" id="pills-tabContent">
                        <!-- basic Info -->
                        @canany(['customer-basic-manage', 'customer-change-password'])
                            @include('backend.user.include.__basic_info')
                        @endcanany

                        <!-- Earnings -->
                        @include('backend.user.include.__earnings')

                        <!-- Listings -->
                        @includeWhen($user->is_seller, 'backend.user.include.__listings')

                        <!-- Purchase -->
                        @includeWhen(!$user->is_seller, 'backend.user.include.__purchase')

                        <!-- transaction -->
                        @can('transaction-list')
                            @include('backend.user.include.__transactions')
                        @endcan

                        <!-- Referral Tree -->
                        @include('backend.user.include.__referral_tree')

                        <!-- ticket -->
                        @canany(['support-ticket-list', 'support-ticket-action'])
                            @include('backend.user.include.__ticket')
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Send Email -->
    @can('customer-mail-send')
        @include('backend.user.include.__mail_send', [
            'name' => $user->first_name . ' ' . $user->last_name,
            'id' => $user->id,
        ])
    @endcan
    <!-- Modal for Send Email-->

    <!-- Modal for Add or Subtract Balance -->
    @can('customer-balance-add-or-subtract')
        @include('backend.user.include.__balance')
    @endcan
    <!-- Modal for Add or Subtract Balance End-->
@endsection

@section('script')
    <script>
        "use strict"

        $('#branch_id').select2();
        $('#country').select2();

        // Delete
        $('body').on('click', '#deleteModal', function() {
            var id = "{{ $user->id }}";

            var url = '{{ route('admin.user.destroy', ':id') }}';
            url = url.replace(':id', id);
            $('#deleteForm').attr('action', url);
            $('#delete').modal('toggle')
        });
    </script>
@endsection
