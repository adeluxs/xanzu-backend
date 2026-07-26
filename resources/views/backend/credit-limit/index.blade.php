@extends('backend.layouts.app')
@section('title')
    {{ __('Credit Limits') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Credit Limits') }}</h2>
                            @can('credit-limit-create')
                                <a href="" class="title-btn" type="button" data-bs-toggle="modal"
                                    data-bs-target="#addNewCreditLimit">
                                    <i data-lucide="plus-circle"></i>{{ __('Add New') }}</a>
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
                        <div class="">
                            <div class="site-table table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col">{{ __('Level') }}</th>
                                            <th scope="col">{{ __('Min. Transactions') }}</th>
                                            <th scope="col">{{ __('KYC Required') }}</th>
                                            <th scope="col">{{ __('Credit Amount') }}</th>
                                            <th scope="col">{{ __('Status') }}</th>
                                            <th scope="col">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($creditLimits as $creditLimit)
                                            <tr>
                                                <td><strong>{{ $creditLimit->level }}</strong></td>
                                                <td>
                                                    <strong>{{ $creditLimit->minimum_transactions }}</strong>
                                                </td>
                                                <td>
                                                    @if ($creditLimit->is_kyc)
                                                        <div class="site-badge success">{{ __('Yes') }}</div>
                                                    @else
                                                        <div class="site-badge pending">{{ __('No') }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <strong>{{ $creditLimit->credit_amount . ' ' . setting('site_currency', 'global') }}</strong>
                                                </td>
                                                <td>
                                                    @if ($creditLimit->status)
                                                        <div class="site-badge success">{{ __('Active') }}</div>
                                                    @else
                                                        <div class="site-badge pending">{{ __('Disabled') }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @can('credit-limit-edit')
                                                        <button class="round-icon-btn primary-btn editCreditLimit"
                                                            type="button" data-credit-limit="{{ json_encode($creditLimit) }}">
                                                            <i data-lucide="edit-3"></i>
                                                        </button>
                                                    @endcan
                                                    @can('credit-limit-delete')
                                                        <form
                                                            action="{{ route('admin.credit-limit.destroy', $creditLimit->id) }}"
                                                            method="POST" class="d-inline"
                                                            onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="round-icon-btn red-btn delete">
                                                                <i data-lucide="trash-2"></i>
                                                            </button>
                                                        </form>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <td colspan="6" class="text-center">{{ __('No Data Found!') }}</td>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Modal for Add New Credit Limit -->
        @can('credit-limit-create')
            @include('backend.credit-limit.include.__add_new')
        @endcan
        <!-- Modal for Add New Credit Limit-->

        <!-- Modal for Edit Credit Limit -->
        @can('credit-limit-edit')
            @include('backend.credit-limit.include.__edit')
        @endcan
        <!-- Modal for Edit Credit Limit-->
    </div>
@endsection
@section('script')
    <script>
        "use strict";

        // Edit Credit Limit
        $('.editCreditLimit').on('click', function(e) {
            e.preventDefault();
            var creditLimit = $(this).data('credit-limit');

            var url = '{{ route('admin.credit-limit.update', ':id') }}';
            url = url.replace(':id', creditLimit.id);

            $('#creditLimitEditForm').attr('action', url);
            $('#editLevel').val(creditLimit.level);
            $('#editMinTransactions').val(creditLimit.minimum_transactions);
            $('#editCreditAmount').val(creditLimit.credit_amount);

            $('#editMinTransactions').prop('required', !creditLimit.is_kyc);

            if (creditLimit.is_kyc) {
                $('#editKycYes').attr('checked', true);
                $('#editKycNo').attr('checked', false);
            } else {
                $('#editKycNo').attr('checked', true);
                $('#editKycYes').attr('checked', false);
            }

            if (creditLimit.status) {
                $('#editStatusDisabled').attr('checked', false);
                $('#editStatusActive').attr('checked', true);
            } else {
                $('#editStatusActive').attr('checked', false);
                $('#editStatusDisabled').attr('checked', true);
            }

            $('#editCreditLimit').modal('show');
        });
    </script>
@endsection
