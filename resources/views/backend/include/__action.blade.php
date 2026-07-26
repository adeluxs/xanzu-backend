@canany(['deposit-action', 'withdraw-action', 'kyc-action', 'coupon-list', 'manage-card-application'])

    @php
        $pending_count = pending_count();
    @endphp
    @if (
        $pending_count['withdraw_count'] ||
            $pending_count['kyc_count'] ||
            $pending_count['deposit_count'] ||
            $pending_count['ticket_count'] ||
            $pending_count['listing_count'] ||
            $pending_count['card_application_count']
    )
        <div class="col-xl-12">
            <div class="admin-latest-announcements">
                <div class="content"><i data-lucide="zap"></i>{{ __('Explore the important Requests to review first') }}</div>
                <div class="content">
                    @can('withdraw-action')
                        @if ($pending_count['withdraw_count'])
                            <a href="{{ route('admin.withdraw.pending') }}" class="site-btn-xs red-btn"><i data-lucide="loader"
                                    class="spining-icon"></i>{{ __('Withdrawals') }}
                                ({{ $pending_count['withdraw_count'] }})</a>
                        @endif
                    @endcan

                    @can('kyc-action')
                        @if ($pending_count['kyc_count'])
                            <a href="{{ route('admin.kyc.pending') }}" class="site-btn-xs green-btn"><i data-lucide="loader"
                                    class="spining-icon"></i>{{ __('ID Verifications') }}
                                ({{ $pending_count['kyc_count'] }})</a>
                        @endif
                    @endcan

                    @can('deposit-action')
                        @if ($pending_count['deposit_count'])
                            <a href="{{ route('admin.deposit.manual.pending') }}" class="site-btn-xs primary-btn"><i
                                    data-lucide="loader" class="spining-icon"></i>{{ __('Manual Payments') }}
                                ({{ $pending_count['deposit_count'] }})</a>
                        @endif
                    @endcan
                    @canany(['support-ticket-list', 'support-ticket-action'])
                        @if ($pending_count['ticket_count'])
                            <a href="{{ route('admin.ticket.index') }}" class="site-btn-xs green-btn"><i data-lucide="loader"
                                    class="spining-icon"></i>{{ __('Tickets') }}
                                ({{ $pending_count['ticket_count'] }})
                            </a>
                        @endif
                    @endcan
                    @can('listing-edit')
                        @if ($pending_count['listing_count'])
                            <a href="{{ route('admin.listing.index', ['approval' => 'unapproved']) }}"
                                class="site-btn-xs primary-btn"><i data-lucide="loader"
                                    class="spining-icon"></i>{{ __('Listings') }} ({{ $pending_count['listing_count'] }})</a>
                        @endif
                    @endcan
                    @can('manage-card-application')
                        @if ($pending_count['card_application_count'])
                            <a href="{{ route('admin.card-application.index', ['status' => 'pending']) }}"
                                class="site-btn-xs blue-btn"><i data-lucide="loader"
                                    class="spining-icon"></i>{{ __('Card Applications') }}
                                ({{ $pending_count['card_application_count'] }})</a>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    @endif
@endcanany
