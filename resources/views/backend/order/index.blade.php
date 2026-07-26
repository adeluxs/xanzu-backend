@extends('backend.layouts.app')
@section('setting-title')
    {{ __('Orders') }}
@endsection
@section('title')
    {{ __('Orders') }}
@endsection

@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('All Orders') }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                    <div class="site-card">
                        <div class="site-card-header d-flex">
                            <h3 class="title">{{ __('Orders') }}</h3>
                        </div>
                        <div class="">
                            <div class="site-table table-responsive">
                                <form action="{{ request()->fullUrl() }}" method="get" id="filterForm">
                                    <div class="table-filter">
                                        <div class="filter">
                                            <div class="search">
                                                <input type="text" id="search" name="search" value=""
                                                    placeholder="Search...">
                                            </div>
                                            <button type="submit" class="apply-btn"><svg xmlns="http://www.w3.org/2000/svg"
                                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" data-lucide="search"
                                                    class="lucide lucide-search">
                                                    <circle cx="11" cy="11" r="8"></circle>
                                                    <path d="m21 21-4.3-4.3"></path>
                                                </svg>{{ __('Search') }}</button>
                                        </div>
                                        <div class="filter d-flex">
                                            <select class="form-select form-select-sm show"
                                                aria-label=".form-select-sm example" name="type" id="type">
                                                <option value="">{{ __('All') }}</option>
                                                <option value="bnpl">{{ __('BNPL') }}</option>
                                                <option value="non-bnpl">{{ __('Non-BNPL') }}</option>
                                            </select>
                                            <select class="form-select form-select-sm show"
                                                aria-label=".form-select-sm example" name="perPage" id="perPage">
                                                <option value="15">{{ __('15') }}</option>
                                                <option value="30">{{ __('30') }}</option>
                                                <option value="45">{{ __('45') }}</option>
                                                <option value="60">{{ __('60') }}</option>
                                            </select>
                                            <select class="form-select form-select-sm" aria-label=".form-select-sm example"
                                                name="status" id="status">
                                                <option value="" disabled="" selected="">{{ __('Status') }}
                                                </option>
                                                <option value="all">{{ __('All') }}</option>
                                                @foreach (\App\Enums\OrderStatus::cases() as $status)
                                                    <option value="{{ $status->value }}">
                                                        {{ str($status->value)->headline() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </form>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            @include('backend.filter.th', [
                                                'label' => 'Order Number & Date',
                                                'field' => 'order_number',
                                            ])
                                            <th>{{ __('Seller') }}</th>
                                            <th>{{ __('Buyer') }}</th>
                                            <th>{{ __('Product') }}</th>
                                            <th>{{ __('Category') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            @include('backend.filter.th', [
                                                'label' => 'Total Price',
                                                'field' => 'total_price',
                                            ])
                                            <th>{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($orders as $order)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold mb-n15"># {{ $order->order_number }}</div>
                                                    <small>{{ $order->order_date }}</small>
                                                </td>
                                                <td>
                                                    <a class="link"
                                                        href="{{ route('admin.user.edit', $order->seller_id ?? 0) }}">{{ $order?->seller?->username ?? __('Admin Product') }}</a>
                                                </td>
                                                <td>
                                                    <a class="link"
                                                        href="{{ route('admin.user.edit', $order->buyer_id ?? 0) }}">{{ $order?->buyer?->username ?? __('Deleted Customer') }}</a>
                                                </td>
                                                <td>
                                                    @foreach ($order->items as $item)
                                                        <a class="link"
                                                            href="{{ route('admin.listing.edit', $item->listing?->id ?? 0) }}">{{ $item->listing?->product_name ?? ($item->product_name ?? '[Deleted]') }}</a>
                                                        @if (!$loop->last)
                                                            ,
                                                        @endif
                                                    @endforeach
                                                </td>
                                                <td>
                                                    @foreach ($order->items->unique('category_id') as $item)
                                                        <a class="link"
                                                            href="{{ route('admin.category.edit', $item->listing?->category->id ?? 0) }}">{{ $item->listing?->category->name ?? '' }}</a>
                                                        @if (!$loop->last)
                                                            ,
                                                        @endif
                                                    @endforeach
                                                </td>
                                                <td>
                                                    {!! bsToAdminBadges($order->status_badge) !!}
                                                </td>
                                                <td>
                                                    {{ $currencySymbol . number_format($order->total_price, 2) }}
                                                </td>
                                                <td>
                                                    <a data-bs-toggle="tooltip" title="{{ __('View Order Details') }}"
                                                        class="round-icon-btn primary-btn"
                                                        href="{{ route('admin.order.show', $order->id) }}"><i
                                                            class="" data-lucide="eye"></i></a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center">{{ __('No Data Found!') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                {{ $orders->links('backend.include.__pagination') }}
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        "use strict";

        $(document).on('change', '#filterForm select', function() {
            $('#filterForm').submit();
        })
    </script>
@endsection
