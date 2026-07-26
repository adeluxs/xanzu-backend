@extends('backend.layouts.app')
@section('title')
@section('style')
    <style>
        .site-table-modal .modal-body .form-select {
            margin-bottom: 21px !important;
        }
    </style>
@endsection
{{ __('Payment Splits for') }} {{ $creditLimit->level }}
@endsection
@section('content')
<div class="main-content">
    <div class="page-title">
        <div class="container-fluid">
            <div class="row">
                <div class="col">
                    <div class="title-content">
                        <h2 class="title">{{ __('Payment Splits for') }} {{ $creditLimit->level }}</h2>
                        <a href="{{ route('admin.credit-limit.index') }}" class="title-btn white-btn">
                            <i data-lucide="arrow-left"></i>{{ __('Back') }}
                        </a>
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
                        <div class="card-header-links">
                            @can('credit-limit-create')
                                <button class="card-header-link rounded-pill addSplit" type="button"
                                    data-credit-limit-id="{{ $creditLimit->id }}">
                                    <i data-lucide="plus"></i> {{ __('Add New Split') }}
                                </button>
                            @endcan
                        </div>
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
                                    @forelse ($creditLimit->splits as $split)
                                        <tr>
                                            <td>{{ $split->total_split ? $split->total_split . ' ' . setting('site_currency', 'global') : __('N/A') }}
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
                                                        data-split="{{ json_encode($split) }}"
                                                        data-credit-limit-id="{{ $creditLimit->id }}">
                                                        <i data-lucide="edit-3"></i>
                                                    </button>
                                                @endcan
                                                @can('credit-limit-delete')
                                                    <form
                                                        action="{{ route('admin.credit-limit.split.delete', [$creditLimit->id, $split->id]) }}"
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
        @include('backend.credit-limit.include.__add_split')
    @endcan
    <!-- Modal for Add Split-->

    <!-- Modal for Edit Split -->
    @can('credit-limit-edit')
        @include('backend.credit-limit.include.__edit_split')
    @endcan
    <!-- Modal for Edit Split-->

</div>
@endsection
@section('script')
<script>
    "use strict";

    // Add Split
    $('.addSplit').on('click', function(e) {
        e.preventDefault();
        var creditLimitId = $(this).data('credit-limit-id');

        var url = '{{ route('admin.credit-limit.split.store', ':id') }}';
        url = url.replace(':id', creditLimitId);

        $('#addSplitForm').attr('action', url);
        $('#addSplit').modal('show');
    });

    // Edit Split
    $('.editSplit').on('click', function(e) {
        e.preventDefault();
        var split = $(this).data('split');
        var creditLimitId = $(this).data('credit-limit-id');

        var url = '{{ route('admin.credit-limit.split.update', [':creditLimitId', ':splitId']) }}';
        url = url.replace(':creditLimitId', creditLimitId);
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
