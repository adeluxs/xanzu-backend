@extends('backend.layouts.app')
@section('title')
    {{ __('Countries') }}
@endsection
@push('style')
    <style>
        .site-table .table tbody tr td .table-description {
            display: flex;
            align-items: center;
        }

        .site-table .table tbody tr td .table-description .icon {
            height: 45px;
            width: 45px;
            line-height: 42px;
            border-radius: 50%;
            color: #5e3fc9;
            text-align: center;
            margin-right: 15px;
        }

        .site-table .table tbody tr td .table-description .description {
            line-height: 1.6;
        }
    </style>
@endpush
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Countries') }}</h2>
                            @can('country-create')
                                <a href="{{ route('admin.country.create') }}" class="title-btn"><i
                                        data-lucide="plus-circle"></i>{{ __('Add New') }}</a>
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
                        <div class="site-card-body">
                            <div class="site-table table-responsive">
                                <form action="{{ request()->fullUrl() }}" method="get" id="filterForm">
                                    <div class="table-filter">
                                        <div class="filter">
                                            <div class="search">
                                                <input type="text" id="search" name="search"
                                                    value="{{ request('search') }}" placeholder="{{ __('Search') }}...">
                                            </div>
                                            <button type="submit" class="apply-btn">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    data-lucide="search" class="lucide lucide-search">
                                                    <circle cx="11" cy="11" r="8"></circle>
                                                    <path d="m21 21-4.3-4.3"></path>
                                                </svg>{{ __('Search') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col">{{ __('Name') }}</th>
                                            <th>{{ __('Conversion Rate') }}</th>
                                            <th scope="col">{{ __('Status') }}</th>
                                            <th scope="col">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($countries as $country)
                                            <tr>
                                                <td>
                                                    <div class="table-description d-flex align-items-center">
                                                        <div class="icon">
                                                            <img src="{{ asset($country->image) }}"
                                                                alt="{{ $country->name }}"
                                                                class="avatar avatar-round me-2">
                                                        </div>
                                                        <div class="description">
                                                            <strong>{{ $country->name }}</strong>
                                                            <div class="date fst-italic">{{ $country->currency_code }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="site-badge primary">
                                                        1 {{ $currency }} = {{ $country->own_rate }}
                                                        {{ $country->currency_code }}
                                                    </div>
                                                </td>
                                                <td>
                                                    @if ($country->status)
                                                        <div class="site-badge success">{{ __('Active') }}</div>
                                                    @else
                                                        <div class="site-badge pending">{{ __('Disabled') }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @can('country-edit')
                                                        <a href="{{ route('admin.country.edit', $country->id) }}"
                                                            class="round-icon-btn primary-btn">
                                                            <i data-lucide="edit-3"></i>
                                                        </a>
                                                    @endcan

                                                    @can('country-delete')
                                                        <button type="button" data-id="{{ $country->id }}"
                                                            data-name="{{ $country->name }}"
                                                            class="round-icon-btn red-btn delete-btn">
                                                            <i data-lucide="trash-2"></i>
                                                        </button>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <td colspan="8" class="text-center">{{ __('No Data Found!') }}</td>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            {{ $countries->links('backend.include.__pagination') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Delete Country -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="CountryModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content site-table-modal">
                    <div class="modal-body popup-body">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <div class="popup-body-text centered">
                            <form method="post" id="deleteForm">
                                @method('DELETE')
                                @csrf
                                <div class="info-icon">
                                    <i data-lucide="alert-triangle"></i>
                                </div>
                                <div class="title">
                                    <h4>{{ __('Are you sure?') }}</h4>
                                </div>
                                <p>
                                    {{ __('You want to delete') }} <strong class="name"></strong>
                                </p>
                                <div class="action-btns">
                                    <button type="submit" class="site-btn-sm primary-btn me-2">
                                        <i data-lucide="check"></i>
                                        {{ __(' Confirm') }}
                                    </button>
                                    <a href="" class="site-btn-sm red-btn" type="button" class="btn-close"
                                        data-bs-dismiss="modal" aria-label="Close">
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
        <!-- Modal for Delete Country-->
    @endsection
    @section('script')
        <script>
            $('.delete-btn').on('click', function(e) {
                "use strict";
                e.preventDefault();

                var id = $(this).data('id');
                var name = $(this).data('name');

                var url = '{{ route('admin.country.destroy', ':id') }}';
                url = url.replace(':id', id);
                $('#deleteForm').attr('action', url)

                $('.name').html(name);
                $('#deleteModal').modal('show');
            })
        </script>
    @endsection
