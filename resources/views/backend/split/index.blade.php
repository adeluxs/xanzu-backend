@extends('backend.layouts.app')
@section('title')
    {{ __('Payment Splits') }}
@endsection
@section('style')
    <style>
        .site-table-modal .modal-body .form-select {
            margin-bottom: 21px !important;
        }
    </style>
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Payment Splits') }}</h2>
                            @can('credit-limit-create')
                                <a href="" class="title-btn" type="button" data-bs-toggle="modal"
                                    data-bs-target="#addSplit">
                                    <i data-lucide="plus-circle"></i>{{ __('Add New Split') }}
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12">
                    <div class="site-card">
                        <div class="site-card-header">
                            <h3 class="title">{{ __('Splits List') }}</h3>
                        </div>
                        <div class="site-card-body">
                            <div class="site-table table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Total Split') }}</th>
                                            <th>{{ __('Payment Interval') }}</th>
                                            <th>{{ __('Interest Rate') }}</th>
                                            <th>{{ __('Delay Fine') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($splits as $split)
                                            <tr>
                                                <td>{{ $split->total_split ? $split->total_split . ' ' . __('Times') : __('N/A') }}
                                                </td>
                                                <td>{{ $split->payment_interval_amount }}
                                                    {{ ucfirst($split->payment_interval_type) }}(s)
                                                </td>
                                                <td>{{ $split->interest_rate_amount }}{{ $split->interest_rate_type == 'percentage' ? '%' : ' ' . setting('site_currency', 'global') }}
                                                    ({{ ucfirst($split->interest_rate_type) }})
                                                </td>
                                                <td>{{ $split->delay_fine_amount }}{{ $split->delay_fine_type == 'percentage' ? '%' : ' ' . setting('site_currency', 'global') }}
                                                    ({{ ucfirst($split->delay_fine_type) }})
                                                </td>
                                                <td>
                                                    @if ($split->status)
                                                        <div class="site-badge success">
                                                            {{ __('Active') }}</div>
                                                    @else
                                                        <div class="site-badge pending">
                                                            {{ __('Disabled') }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @can('credit-limit-edit')
                                                        <button class="round-icon-btn primary-btn editSplit" type="button"
                                                            data-split="{{ json_encode($split) }}">
                                                            <i data-lucide="edit-3"></i>
                                                        </button>
                                                    @endcan
                                                    @can('credit-limit-delete')
                                                        <form action="{{ route('admin.split.destroy', $split->id) }}"
                                                            method="POST" class="d-inline"
                                                            onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="round-icon-btn red-btn">
                                                                <i data-lucide="trash-2"></i>
                                                            </button>
                                                        </form>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">{{ __('No Data Found!') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Add Split -->
        @can('credit-limit-create')
            @include('backend.split.include.__add_split')
        @endcan
        <!-- Modal for Add Split-->

        <!-- Modal for Edit Split -->
        @can('credit-limit-edit')
            @include('backend.split.include.__edit_split')
        @endcan
        <!-- Modal for Edit Split-->

    </div>
@endsection
@section('script')
    <script>
        "use strict";

        // Edit Split
        $('.editSplit').on('click', function(e) {
            e.preventDefault();
            var split = $(this).data('split');

            var url = '{{ route('admin.split.update', ':splitId') }}';
            url = url.replace(':splitId', split.id);

            $('#editSplitForm').attr('action', url);
            $('#editSplitTotalSplit').val(split.total_split);
            $('#editSplitPaymentIntervalAmount').val(split.payment_interval_amount);
            $('#editSplitPaymentIntervalType').val(split.payment_interval_type);
            $('#editSplitInterestRateAmount').val(split.interest_rate_amount);
            $('#editSplitInterestRateType').val(split.interest_rate_type);
            $('#editSplitDelayFineAmount').val(split.delay_fine_amount);
            $('#editSplitDelayFineType').val(split.delay_fine_type);

            if (split.status) {
                $('#editSplitStatusDisabled').attr('checked', false);
                $('#editSplitStatusActive').attr('checked', true);
            } else {
                $('#editSplitStatusActive').attr('checked', false);
                $('#editSplitStatusDisabled').attr('checked', true);
            }

            $('#editSplit').modal('show');
        });
    </script>
@endsection
