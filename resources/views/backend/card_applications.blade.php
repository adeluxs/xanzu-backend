@extends('backend.layouts.app')
@section('title')
    {{ __('Card Applications') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Card Applications') }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 col-md-12">
                    <div class="site-card">
                        <div class="site-table table-responsive">
                            @include('backend.include.__card_application_filter')
                            <table class="table">
                                <thead>
                                    <tr>
                                        @include('backend.filter.th', [
                                            'label' => 'Date',
                                            'field' => 'created_at',
                                        ])
                                        @include('backend.filter.th', [
                                            'label' => 'User',
                                            'field' => 'user',
                                        ])
                                        <th>{{ __('Name') }}</th>
                                        @include('backend.filter.th', [
                                            'label' => 'Email',
                                            'field' => 'email',
                                        ])
                                        <th>{{ __('Phone') }}</th>
                                        @include('backend.filter.th', [
                                            'label' => 'Status',
                                            'field' => 'status',
                                        ])
                                        <th>{{ __('Admin Note') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($applications as $application)
                                        @php
                                            $statusValue = $application->status?->value ?? $application->status;
                                            $badgeClass = match ($statusValue) {
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                'onhold' => 'primary text-white',
                                                default => 'pending',
                                            };
                                        @endphp
                                        <tr>
                                            <td>{{ $application->created_at }}</td>
                                            <td>
                                                @include('backend.transaction.include.__user', [
                                                    'id' => $application->user_id,
                                                    'name' => $application->user?->username ?? '--',
                                                ])
                                            </td>
                                            <td>{{ $application->first_name . ' ' . $application->last_name }}</td>
                                            <td>{{ $application->email }}</td>
                                            <td>{{ $application->phone_number }}</td>
                                            <td>
                                                <div class="site-badge {{ $badgeClass }}">
                                                    {{ __(ucfirst($statusValue)) }}
                                                </div>
                                            </td>
                                            <td>{{ $application->admin_note ?? '--' }}</td>
                                            <td>
                                                @can('manage-card-application')
                                                    <a href="{{ route('admin.card-application.show', $application->id) }}"
                                                        class="round-icon-btn primary-btn" type="button">
                                                        <i data-lucide="eye"></i>
                                                    </a>
                                                    <button class="round-icon-btn primary-btn card-app-action" type="button"
                                                        data-route="{{ route('admin.card-application.approve', $application->id) }}"
                                                        data-title="{{ __('Approve Application') }}"
                                                        data-btn-text="{{ __('Approve') }}" data-btn-class="primary-btn"
                                                        data-icon="check-circle">
                                                        <i data-lucide="check-circle"></i>
                                                    </button>
                                                    <button class="round-icon-btn blue-btn card-app-action" type="button"
                                                        data-route="{{ route('admin.card-application.hold', $application->id) }}"
                                                        data-title="{{ __('Put On Hold') }}"
                                                        data-btn-text="{{ __('On Hold') }}" data-btn-class="blue-btn"
                                                        data-icon="pause-circle">
                                                        <i data-lucide="pause-circle"></i>
                                                    </button>
                                                    <button class="round-icon-btn red-btn card-app-action" type="button"
                                                        data-route="{{ route('admin.card-application.reject', $application->id) }}"
                                                        data-title="{{ __('Reject Application') }}"
                                                        data-btn-text="{{ __('Reject') }}" data-btn-class="red-btn"
                                                        data-icon="x-circle" data-show-card-toggle="1">
                                                        <i data-lucide="x-circle"></i>
                                                    </button>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <td colspan="8" class="text-center">{{ __('No Data Found!') }}</td>
                                    @endforelse
                                </tbody>
                            </table>

                            {{ $applications->links('backend.include.__pagination') }}
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
                                            <input type="radio" id="disable-card-no" name="disable_card_status" value="0"
                                                checked>
                                            <label for="disable-card-no">{{ __('No') }}</label>
                                        </div>
                                    </div>

                                    <div class="action-btns">
                                        <button type="submit" id="card-app-action-submit" class="site-btn-sm primary-btn me-2">
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
