@extends('backend.layouts.app')
@section('title')
    {{ __('Card Application Details') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Card Application Details') }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $statusValue = $cardApplication->status?->value ?? $cardApplication->status;
            $badgeClass = match ($statusValue) {
                'approved' => 'success',
                'rejected' => 'danger',
                'onhold' => 'primary text-white',
                default => 'pending',
            };
        @endphp

        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12">
                    <div class="site-card">
                        <div class="site-card-header">
                            <h3 class="title">
                                {{ __('Application #') . $cardApplication->id }}
                                <span class="site-badge {{ $badgeClass }}">{{ __(ucfirst($statusValue)) }}</span>
                            </h3>
                            @can('manage-card-application')
                                <div class="card-header-links">
                                    <a href="{{ route('admin.card-application.index') }}"
                                        class="card-header-link rounded-pill">{{ __('Back to List') }}</a>
                                    <button class="card-header-link rounded-pill card-app-action"
                                        data-route="{{ route('admin.card-application.approve', $cardApplication->id) }}"
                                        data-title="{{ __('Approve Application') }}" data-btn-text="{{ __('Approve') }}"
                                        data-btn-class="primary-btn" data-icon="check-circle">
                                        {{ __('Approve') }}
                                    </button>
                                    <button class="card-header-link rounded-pill card-app-action"
                                        data-route="{{ route('admin.card-application.hold', $cardApplication->id) }}"
                                        data-title="{{ __('Put On Hold') }}" data-btn-text="{{ __('On Hold') }}"
                                        data-btn-class="blue-btn" data-icon="pause-circle">
                                        {{ __('On Hold') }}
                                    </button>
                                    <button class="card-header-link rounded-pill card-app-action"
                                        data-route="{{ route('admin.card-application.reject', $cardApplication->id) }}"
                                        data-title="{{ __('Reject Application') }}" data-btn-text="{{ __('Reject') }}"
                                        data-btn-class="red-btn" data-icon="x-circle" data-show-card-toggle="1">
                                        {{ __('Reject') }}
                                    </button>
                                </div>
                            @endcan
                        </div>
                        <div class="site-card-body">
                            <div class="row">
                                <div class="col-xl-6 col-lg-6">
                                    <div class="site-card mb-3">
                                        <div class="site-card-header">
                                            <h4 class="title-small">{{ __('Submitted Data') }}</h4>
                                        </div>
                                        <div class="site-card-body">
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Full Name') }}</div>
                                                <div class="value">
                                                    {{ $cardApplication->first_name . ' ' . $cardApplication->last_name }}
                                                </div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Email') }}</div>
                                                <div class="value">{{ $cardApplication->email }}</div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Phone') }}</div>
                                                <div class="value">{{ $cardApplication->phone_number }}</div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Address') }}</div>
                                                <div class="value">{{ $cardApplication->address }}</div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('City') }}</div>
                                                <div class="value">{{ $cardApplication->city }}</div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('State') }}</div>
                                                <div class="value">{{ $cardApplication->state }}</div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Country') }}</div>
                                                <div class="value">{{ $cardApplication->country }}</div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Postal Code') }}</div>
                                                <div class="value">{{ $cardApplication->postal_code }}</div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Admin Note') }}</div>
                                                <div class="value">{{ $cardApplication->admin_note ?? '--' }}</div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Submitted At') }}</div>
                                                <div class="value">{{ $cardApplication->created_at }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-6 col-lg-6">
                                    <div class="site-card mb-3">
                                        <div class="site-card-header">
                                            <h4 class="title-small">{{ __('User Profile') }}</h4>
                                        </div>
                                        <div class="site-card-body">
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Username') }}</div>
                                                <div class="value">
                                                    @if ($cardApplication->user)
                                                        <a class="link"
                                                            href="{{ route('admin.user.edit', $cardApplication->user->id) }}">
                                                            {{ $cardApplication->user->username }}
                                                        </a>
                                                    @else
                                                        --
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Email') }}</div>
                                                <div class="value">{{ $cardApplication->user?->email ?? '--' }}</div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Phone') }}</div>
                                                <div class="value">{{ $cardApplication->user?->phone ?? '--' }}</div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('KYC') }}</div>
                                                <div class="value">
                                                    @if ($cardApplication->user?->kyc == 1)
                                                        <div class="site-badge success">{{ __('Verified') }}</div>
                                                    @else
                                                        <div class="site-badge pending">{{ __('Unverified') }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Status') }}</div>
                                                <div class="value">
                                                    @if ($cardApplication->user?->status == 1)
                                                        <div class="site-badge success">{{ __('Active') }}</div>
                                                    @elseif ($cardApplication->user?->status == 2)
                                                        <div class="site-badge pending">{{ __('Closed') }}</div>
                                                    @else
                                                        <div class="site-badge danger">{{ __('Disabled') }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Card Status') }}</div>
                                                <div class="value">
                                                    @if ($cardApplication->user?->card_status)
                                                        <div class="site-badge success">{{ __('Active') }}</div>
                                                    @else
                                                        <div class="site-badge danger">{{ __('Disabled') }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Registered At') }}</div>
                                                <div class="value">{{ $cardApplication->user?->created_at ?? '--' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @can('manage-card-application')
            <div class="modal fade" id="card-app-action-modal" tabindex="-1" aria-labelledby="cardAppActionModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered">
                    <div class="modal-content site-table-modal">
                        <div class="modal-body popup-body">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                            <div class="popup-body-text">
                                <h3 class="title mb-4" id="card-app-action-title">{{ __('Card Application Action') }}
                                </h3>

                                <form id="card-app-action-form" method="POST">
                                    @csrf
                                    <div class="site-input-groups">
                                        <label for="card_app_note" class="box-input-label">{{ __('Admin Note') }}</label>
                                        <textarea name="note" id="card_app_note" class="form-textarea" rows="4"
                                            placeholder="{{ __('Write a note (optional)...') }}"></textarea>
                                    </div>

                                    <div class="site-input-groups" id="card-status-toggle-wrap" style="display:none;">
                                        <label class="box-input-label">{{ __('Disable Card Status:') }}</label>
                                        <div class="switch-field">
                                            <input type="radio" id="disable-card-yes" name="disable_card_status"
                                                value="1">
                                            <label for="disable-card-yes">{{ __('Yes') }}</label>
                                            <input type="radio" id="disable-card-no" name="disable_card_status"
                                                value="0" checked>
                                            <label for="disable-card-no">{{ __('No') }}</label>
                                        </div>
                                    </div>

                                    <div class="action-btns">
                                        <button type="submit" id="card-app-action-submit"
                                            class="site-btn-sm primary-btn me-2">
                                            <i data-lucide="check-circle"></i>
                                            <span id="card-app-action-text">{{ __('Submit') }}</span>
                                        </button>
                                        <a href="#" class="site-btn-sm primary-btn" data-bs-dismiss="modal"
                                            aria-label="Close">
                                            <i data-lucide="x"></i>
                                            {{ __('Cancel') }}
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    </div>
@endsection
@section('script')
    <script>
        (function($) {
            "use strict";

            $('body').on('click', '.card-app-action', function() {
                const route = $(this).data('route');
                const title = $(this).data('title');
                const btnText = $(this).data('btn-text');
                const btnClass = $(this).data('btn-class');
                const icon = $(this).data('icon');
                const showCardToggle = $(this).data('show-card-toggle');

                $('#card-app-action-title').text(title);
                $('#card-app-action-form').attr('action', route);
                $('#card_app_note').val('');

                if (showCardToggle) {
                    $('#card-status-toggle-wrap').show();
                } else {
                    $('#card-status-toggle-wrap').hide();
                    $('#disable-card-no').prop('checked', true);
                }

                const $submit = $('#card-app-action-submit');
                $submit.attr('class', 'site-btn-sm ' + btnClass + ' me-2');
                $submit.find('i').attr('data-lucide', icon);
                $('#card-app-action-text').text(btnText);

                if (window.lucide) {
                    window.lucide.createIcons();
                }

                $('#card-app-action-modal').modal('toggle');
            });
        })(jQuery);
    </script>
@endsection
