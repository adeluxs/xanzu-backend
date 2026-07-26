@extends('backend.layouts.app')
@section('title')
    {{ __('Review Management') }}
@endsection
@section('style')
    <style>
        #deleteModal {
            z-index: 1056 !important;
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
                            <h2 class="title">{{ __('Review Management') }}</h2>
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
                                            <input type="text" id="search" name="search"
                                                value="{{ request('search') }}" placeholder="{{ __('Search') }}...">
                                        </div>
                                        <button type="submit" class="apply-btn">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" data-lucide="search"
                                                class="lucide lucide-search">
                                                <circle cx="11" cy="11" r="8"></circle>
                                                <path d="m21 21-4.3-4.3"></path>
                                            </svg>{{ __('Search') }}
                                        </button>
                                    </div>
                                    <div class="filter d-flex">
                                        <select class="form-select form-select-sm show" aria-label=".form-select-sm example"
                                            name="perPage" id="perPage">
                                            <option @selected(request('perPage') == 15) value="15">{{ __('15') }}</option>
                                            <option @selected(request('perPage') == 30) value="30">{{ __('30') }}</option>
                                            <option @selected(request('perPage') == 45) value="45">{{ __('45') }}</option>
                                            <option @selected(request('perPage') == 60) value="60">{{ __('60') }}</option>
                                        </select>
                                        <select class="form-select form-select-sm" aria-label=".form-select-sm example"
                                            name="status" id="status">
                                            <option value="" disabled="" selected="">{{ __('Review Status') }}
                                            </option>
                                            <option @selected(request('status') == 'all') value="all">{{ __('All') }}
                                            </option>
                                            <option @selected(request('status') == 'pending') value="pending">{{ __('Pending') }}
                                            </option>
                                            <option @selected(request('status') == 'approved') value="approved">{{ __('Approved') }}
                                            </option>
                                            <option @selected(request('status') == 'rejected') value="rejected">{{ __('Rejected') }}
                                            </option>
                                            <option @selected(request('status') == 'flagged') value="flagged">{{ __('Flagged') }}
                                            </option>

                                        </select>
                                        <select class="form-select form-select-sm" aria-label=".form-select-sm example"
                                            name="rating" id="rating">
                                            <option value="" disabled="" selected="">{{ __('Rating') }}
                                            </option>
                                            <option @selected(request('rating') == 'all') value="all">{{ __('All') }}
                                            </option>
                                            @for ($i = 5; $i >= 1; $i--)
                                                <option @selected(request('rating') == $i) value="{{ $i }}">
                                                    {{ $i }} {{ __('Stars') }}</option>
                                            @endfor
                                        </select>

                                    </div>
                                </div>
                            </form>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('Listing') }}</th>
                                        <th scope="col">{{ __('Buyer') }}</th>
                                        <th scope="col">{{ __('Seller') }}</th>
                                        @include('backend.filter.th', [
                                            'label' => 'Rating',
                                            'field' => 'rating',
                                        ])
                                        <th scope="col">{{ __('Review Text') }}</th>
                                        <th scope="col">{{ __('Status') }}</th>
                                        @include('backend.filter.th', [
                                            'label' => 'Date',
                                            'field' => 'created_at',
                                        ])
                                        <th scope="col">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reviews as $review)
                                        <tr>
                                            <td>
                                                <strong>

                                                    <a target="_blank" href="{{ $review->listing?->admin_url }}"
                                                        class="link" data-bs-toggle="tooltip" data-bs-placement="top"
                                                        data-bs-original-title="{{ __('View Listing') }}">
                                                        {{ Str::limit($review->listing?->product_name, 30) }}
                                                    </a>
                                                </strong>
                                            </td>
                                            <td>
                                                <a class="link" href="{{ route('admin.user.edit', $review->buyer_id) }}">
                                                    {{ $review->buyer?->username }}
                                                </a>
                                            </td>
                                            <td>
                                                <a class="link"
                                                    href="{{ route('admin.user.edit', $review->seller_id) }}">
                                                    {{ $review->seller?->username }}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <span
                                                            style="color: {{ $i <= $review->rating ? '#ffc107' : '#dee2e6' }};">★</span>
                                                    @endfor
                                                    <span class="ms-1">({{ $review->rating }})</span>
                                                </div>
                                            </td>
                                            <td>{{ Str::limit($review->review ?? 'No review text', 40) }}</td>
                                            <td>
                                                @if ($review->status->value == 'pending')
                                                    <span
                                                        class="site-badge rounded-pill pending">{{ __('Pending') }}</span>
                                                @elseif($review->status->value == 'approved')
                                                    <span
                                                        class="site-badge rounded-pill success">{{ __('Approved') }}</span>
                                                @else
                                                    <span
                                                        class="site-badge rounded-pill danger">{{ __(str($review->status->value)->headline()->value()) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $review->created_at->format('M d, Y') }}</td>
                                            <td>
                                                @can('listing-delete')
                                                    <a href="#" class="round-icon-btn red-btn" id="deleteBtn"
                                                        data-id="{{ $review->id }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-bs-original-title="{{ __('Delete Review') }}">
                                                        <i data-lucide="trash"></i>
                                                    </a>
                                                @endcan
                                                @can('listing-edit')
                                                    @if ($review->status->value == 'pending')
                                                        <a href="{{ route('admin.reviews.approve', $review->id) }}"
                                                            class="round-icon-btn green-btn" data-id="{{ $review->id }}"
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            data-bs-original-title="{{ __('Approve Review') }}">
                                                            <i data-lucide="check"></i>
                                                        </a>
                                                        <a href="#" class="round-icon-btn red-btn rejectBtn"
                                                            data-id="{{ $review->id }}" data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            data-bs-original-title="{{ __('Reject Review') }}">
                                                            <i data-lucide="ban"></i>
                                                        </a>
                                                    @endif
                                                @endcan

                                                <a target="_blank" href="{{ route('admin.reviews.show', $review->id) }}"
                                                    class="round-icon-btn primary-btn viewDetailsBtn"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    data-bs-original-title="{{ __('Review Details') }}">
                                                    <i data-lucide="scan-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <td colspan="8" class="text-center">{{ __('No Data Found!') }}</td>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="site-pagination">
                                <nav aria-label="...">
                                    <ul class="pagination">
                                        {{ $reviews->links('backend.include.__pagination') }}
                                    </ul>
                                </nav>
                            </div>
                            @include('backend.reviews.include.__delete_modal')
                            @include('backend.reviews.include.__reject_modal')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" role="dialog" aria-labelledby="viewDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDetailsModalLabel">{{ __('View Review Details') }}</h5>
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
                var url = '{{ route('admin.reviews.delete', ':id') }}';
                url = url.replace(':id', id);
                $('#deleteForm').attr('action', url);
                $('#viewDetailsModal').modal('hide');
                $('#deleteModal').modal('show');
            });

            // Reject Modal
            $('body').on('click', '.rejectBtn', function() {
                var id = $(this).data('id');
                var url = '{{ route('admin.reviews.reject', ':id') }}';
                url = url.replace(':id', id);
                $('#rejectForm').attr('action', url);
                $('#rejectModal').modal('show');
            });

            $(document).on('change', '#filterForm select', function() {
                $('#filterForm').submit();
            });

            // View review details popup
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
