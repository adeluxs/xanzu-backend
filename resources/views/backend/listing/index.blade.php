@extends('backend.layouts.app')
@section('title')
    {{ __('Listings') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Listings Item') }}</h2>
                            <div class="card-header-links">
                                <a href="{{ route('admin.listing.create') }}" class="title-btn">
                                    <i data-lucide="plus"></i>{{ __('Add Listing') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12">
                    <div class="site-card">
                        <div class="site-table table-responsive">
                            <form action="{{ request()->fullUrl() }}" method="get" id="filterForm">
                                <div class="table-filter">
                                    <div class="filter">
                                        <div class="search">
                                            <input type="text" id="search" name="search" value=""
                                                placeholder="{{ __('Search') }}...">
                                        </div>
                                        <button type="submit" class="apply-btn"><svg xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" data-lucide="search" class="lucide lucide-search">
                                                <circle cx="11" cy="11" r="8"></circle>
                                                <path d="m21 21-4.3-4.3"></path>
                                            </svg>{{ __('Search') }}</button>
                                    </div>
                                    <div class="filter d-flex">
                                        <select class="form-select form-select-sm show" aria-label=".form-select-sm example"
                                            name="perPage" id="perPage">
                                            <option @selected(request('perPage') == 15) value="15">{{ __('15') }}</option>
                                            <option @selected(request('perPage') == 30) value="30">{{ __('30') }}</option>
                                            <option @selected(request('perPage') == 45) value="45">{{ __('45') }}</option>
                                            <option @selected(request('perPage') == 60) value="60">{{ __('60') }}
                                            </option>
                                        </select>
                                        <select class="form-select form-select-sm" aria-label=".form-select-sm example"
                                            name="category" id="category">
                                            <option value="" disabled="" selected="">{{ __('Categories') }}
                                            </option>
                                            <option @selected(request('category') == 'all') value="all">{{ __('All') }}
                                            </option>
                                            @foreach ($categories as $category)
                                                <option @selected(request('category') == $category->id) value="{{ $category->id }}">
                                                    {{ ucwords($category->name) }}</option>
                                            @endforeach
                                        </select>
                                        <select class="form-select form-select-sm" aria-label=".form-select-sm example"
                                            name="status" id="status">
                                            <option value="" disabled="" selected="">{{ __('Status') }}
                                            </option>
                                            <option @selected(request('status') == 'all') value="all">{{ __('All') }}
                                            </option>
                                            @foreach (\App\Enums\ListingStatus::cases() as $status)
                                                <option @selected(request('status') == $status->value) value="{{ $status->value }}">
                                                    {{ str($status->value)->headline() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </form>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('Image') }}</th>
                                        <th scope="col">{{ __('Product Name') }}</th>
                                        <th scope="col">{{ __('Category') }}</th>
                                        <th scope="col">{{ __('Seller') }}</th>
                                        <th scope="col">{{ __('Discount') }}</th>
                                        <th scope="col">{{ __('Price') }}</th>
                                        <th scope="col">{{ __('Quantity') }}</th>
                                        <th scope="col">{{ __('Status') }}</th>
                                        <th scope="col">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($listings as $listing)
                                        <tr>
                                            <td>
                                                <img src="{{ asset($listing->thumbnail) }}"
                                                    alt="{{ $listing->product_name }}" class="img-fluid"
                                                    style="max-width: 50px; max-height: 50px;">
                                            </td>
                                            <td>
                                                <strong>
                                                    <a class="link"
                                                        href="{{ $listing->admin_url }}">{{ str($listing->product_name)->limit(40) }}
                                                        @if ($listing->is_trending)
                                                            <i data-lucide="flame"></i>
                                                        @endif

                                                    </a>
                                                </strong>
                                            </td>
                                            <td>{{ $listing->category->name }}</td>
                                            <td><a class="link"
                                                    href="{{ route('admin.user.edit', $listing->seller_id) }}">{{ $listing->seller?->username ?? __('Admin Product') }}</a>
                                            </td>
                                            <td>{{ $currencySymbol . $listing->discount_amount }}</td>
                                            <td>{{ $currencySymbol . $listing->price }}</td>
                                            <td>{{ $listing->quantity }}</td>
                                            <td>
                                                {!! bsToAdminBadges(badge: __($listing->status_badge)) !!}
                                            </td>
                                            <td>

                                                <a target="_blank" href="{{ route('admin.listing.view', $listing->id) }}"
                                                    class="round-icon-btn primary-btn viewDetailsBtn"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    data-bs-original-title="{{ __('Listing Details') }}">
                                                    <i data-lucide="scan-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.listing.edit', $listing->id) }}"
                                                    class="round-icon-btn primary-btn" data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    data-bs-original-title="{{ __('Edit Listing') }}">
                                                    <i data-lucide="edit"></i>
                                                </a>
                                                @can('listing-edit')
                                                    <a href="{{ route('admin.listing.trending.toggle', $listing->id) }}"
                                                        class="round-icon-btn {{ $listing->is_trending ? 'green-btn' : 'red-btn' }}"
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                        data-bs-original-title="{{ $listing->is_trending ? __('Remove from Trending') : __('Mark as Trending') }}">
                                                        <i data-lucide="flame"></i>
                                                    </a>
                                                @endcan
                                                @if ($listing->type->value !== 'physical')
                                                    <a href="{{ route('admin.listing.delivery-items', $listing->id) }}"
                                                        class="round-icon-btn primary-btn" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-bs-original-title="{{ __('Delivery Items') }}">
                                                        <i data-lucide="package"></i>
                                                    </a>
                                                @endif
                                                @can('listing-delete')
                                                    <a href="#" class="round-icon-btn red-btn" id="deleteBtn"
                                                        data-id="{{ $listing->id }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-bs-original-title="{{ __('Delete Listing') }}">
                                                        <i data-lucide="trash"></i>
                                                    </a>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <td colspan="10" class="text-center">{{ __('No Data Found!') }}</td>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="site-pagination">
                                <nav aria-label="...">
                                    <ul class="pagination">
                                        {{ $listings->links('backend.include.__pagination') }}
                                    </ul>
                                </nav>
                            </div>
                            @include('backend.listing.include.__delete_modal')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" role="dialog" aria-labelledby="viewDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDetailsModalLabel">{{ __('View Item Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div id="viewDetailsResponse">
                    ...
                </div>
                <div class="modal-footer">
                    <button type="button" class="site-btn primary-btn" data-bs-dismiss="modal">
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function($) {
            "use strict";

            // Delete Modal
            $('body').on('click', '#deleteBtn', function() {
                var id = $(this).data('id');
                var url = '{{ route('admin.listing.delete', ':id') }}';
                url = url.replace(':id', id);
                $('#deleteForm').attr('action', url);
                $('#deleteModal').modal('show');
            });

            $(document).on('change', '#filterForm select', function() {
                $('#filterForm').submit();
            })


            // view listing popup

            $(document).on('click', '.viewDetailsBtn', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');
                $.get(url, function(data) {
                    $('#viewDetailsResponse').html(data);
                    $('#viewDetailsModal').modal('show');
                });
            });


        })(jQuery);
    </script>
@endsection
