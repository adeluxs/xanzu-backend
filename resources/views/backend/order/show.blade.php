@extends('backend.layouts.app')
@section('setting-title')
    {{ __('View Order') }}
@endsection
@section('title')
    {{ __('View Order') }}
@endsection

@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content d-flex justify-content-between">
                            <h2 class="title">{{ __('View Order: :orderNo', ['orderNo' => '#' . $order->order_number]) }}
                            </h2>
                            <a href="{{ route('admin.order.index') }}" class="title-btn">{{ __('Back') }}</a>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="site-card">
                        <div class="site-card-header">
                            <h3 class="title">{{ __('Order Information') }}</h3>


                        </div>
                        <div class="site-card-body">
                            <div class="row">
                                @if ($order->items->isNotEmpty())
                                    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                        <div class="site-card">
                                            <div class="site-card-header">
                                                <h4 class="title-small">{{ __('Sellers') }}</h4>
                                            </div>
                                            <div class="site-card-body">
                                                @foreach ($order->uniqueSellers() as $seller)
                                                    <div class="profile-text-data">
                                                        <div class="attribute">{{ __('Name') }}</div>
                                                        <div class="value"><a class="link"
                                                                    href="{{ route('admin.user.edit', $seller->id) }}">{{ $seller->username }}</a>
                                                        </div>
                                                    </div>
                                                    <div class="profile-text-data">
                                                        <div class="attribute">{{ __('Email') }}</div>
                                                        <div class="value">{{ $seller->email }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                    <div class="site-card">
                                        <div class="site-card-header">
                                            <h4 class="title-small">{{ __('Buyer Information') }}</h4>
                                        </div>
                                        <div class="site-card-body">
                                            @if ($order->buyer)
                                                <div class="profile-text-data">
                                                    <div class="attribute">{{ __('Name') }}</div>
                                                    <div class="value"><a class="link"
                                                            href="{{ route('admin.user.edit', $order->buyer_id ?? 0) }}">{{ $order->buyer?->username }}</a>
                                                    </div>
                                                </div>
                                                <div class="profile-text-data">
                                                    <div class="attribute">{{ __('Email') }}</div>
                                                    <div class="value">{{ $order->buyer->email }}</div>
                                                </div>
                                                <div class="profile-text-data">
                                                    <div class="attribute">{{ __('Phone') }}</div>
                                                    <div class="value">{{ $order->buyer->phone }}</div>
                                                </div>
                                            @else
                                                <p class="text-danger text-center">{{ __('Buyer not found') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                    <div class="site-card">
                                        <div class="site-card-header">
                                            <h4 class="title-small">{{ __('Order Information') }}</h4>
                                        </div>
                                        <div class="site-card-body">
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Order Number') }}</div>
                                                <div class="value">
                                                    {{ $order->order_number }}
                                                </div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Order Date') }}</div>
                                                <div class="value">{{ $order->created_at }}</div>
                                            </div>
                                            <div class="profile-text-data">
                                                <div class="attribute">{{ __('Order Status') }}</div>
                                                <div class="value">{!! bsToAdminBadges($order->status_badge) !!}</div>
                                            </div>
                                            @if (!empty($order->shipping_address))
                                                <div class="profile-text-data">
                                                    <div class="attribute">{{ __('Shipping Address') }}</div>
                                                    <div class="value">
                                                        @if (is_array($order->shipping_address))
                                                            {{ $order->shipping_address['address'] ?? collect($order->shipping_address)->filter()->implode(', ') }}
                                                        @else
                                                            {{ $order->shipping_address }}
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                            @if ($order->delivered_at)
                                                <div class="profile-text-data">
                                                    <div class="attribute">{{ __('Delivered At') }}</div>
                                                    <div class="value">{{ $order->delivered_at }}</div>
                                                </div>
                                            @endif

                                            @if ($hasPhysicalItems)
                                                <div class="profile-text-data">
                                                    <div class="attribute">{{ __('Courier Partner') }}</div>
                                                    <div class="value">
                                                        {{ $order->courierPartner?->name ?? __('Not selected') }}</div>
                                                </div>
                                                <div class="profile-text-data">
                                                    <div class="attribute">{{ __('Estimated Delivery') }}</div>
                                                    <div class="value">
                                                        @if ($order->estimated_delivery_from || $order->estimated_delivery_to)
                                                            {{ $order->estimated_delivery_from?->format('Y-m-d') ?? '-' }}
                                                            {{ __('to') }}
                                                            {{ $order->estimated_delivery_to?->format('Y-m-d') ?? '-' }}
                                                        @else
                                                            {{ __('Not set') }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="profile-text-data">
                                                    <div class="attribute">{{ __('Tracking Number') }}</div>
                                                    <div class="value">{{ $order->tracking_number ?? __('Not set') }}
                                                    </div>
                                                </div>
                                                <div class="profile-text-data">
                                                    <div class="attribute">{{ __('Tracking Link') }}</div>
                                                    <div class="value">
                                                        @if ($order->tracking_link)
                                                            <a href="{{ $order->tracking_link }}" target="_blank"
                                                                rel="noopener"
                                                                class="link">{{ __('Open Tracking Link') }}</a>
                                                        @else
                                                            {{ __('Not set') }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="profile-text-data">
                                                    <div class="attribute">{{ __('Delivery Note') }}</div>
                                                    <div class="value">{{ $order->delivery_note ?? __('Not set') }}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @can('order-update')
                                    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                                        <div class="site-card">
                                            <div class="site-card-header">
                                                <h4 class="title-small">{{ __('Update Order Status') }}</h4>
                                            </div>
                                            <form id="updateStatusForm"
                                                action="{{ route('admin.order.update-status', $order->id) }}" method="post">
                                                @csrf
                                                <div class="site-card-body">
                                                    <div class="col-md-12">
                                                        <div class="site-input-groups row">
                                                            <label class="box-input-label col-auto col-label"
                                                                for="">{{ __('Order Status') }}</label>
                                                            <select name="status" id="" class="form-select col">
                                                                @foreach ($orderStatus as $status)
                                                                    <option @selected($order->status == $status->value)
                                                                        value="{{ $status->value }}">
                                                                        {{ str($status->name)->headline() }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="site-input-groups row">
                                                            <label class="box-input-label col-auto col-label"
                                                                for="">{{ __('Payment Status') }}</label>
                                                            <select name="payment_status" id=""
                                                                class="form-select col">
                                                                @foreach ($paymentStatus as $status)
                                                                    <option @selected($order->payment_status == $status->value)
                                                                        value="{{ $status->value }}">{{ $status->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endcan
                                @if ($hasPhysicalItems)
                                    @can('order-update')
                                        <div class="col-xl-8 col-lg-6 col-md-12 col-sm-12">
                                            <div class="site-card">
                                                <div class="site-card-header">
                                                    <h4 class="title-small">{{ __('Order Delivery') }}</h4>
                                                </div>
                                                <form id="updateDeliveryForm"
                                                    action="{{ route('admin.order.update-delivery', $order->id) }}"
                                                    method="post">
                                                    @csrf
                                                    <div class="site-card-body">
                                                        <div class="col-md-12">
                                                            <div class="site-input-groups row">
                                                                <label class="box-input-label col-auto col-label"
                                                                    for="courier_partner_id">{{ __('Courier Partner') }}</label>
                                                                <select name="courier_partner_id" id="courier_partner_id"
                                                                    class="form-select col">
                                                                    <option value="">{{ __('Select Courier Partner') }}
                                                                    </option>
                                                                    @foreach ($courierPartners as $courierPartner)
                                                                        <option value="{{ $courierPartner->id }}"
                                                                            @selected((int) old('courier_partner_id', $order->courier_partner_id) === (int) $courierPartner->id)>
                                                                            {{ $courierPartner->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-12">
                                                            <div class="site-input-groups row">
                                                                <label class="box-input-label col-auto col-label"
                                                                    for="estimated_delivery_from">{{ __('Estimated Delivery From') }}</label>
                                                                <input type="date" id="estimated_delivery_from"
                                                                    class="box-input form-control-sm col"
                                                                    name="estimated_delivery_from"
                                                                    value="{{ old('estimated_delivery_from', optional($order->estimated_delivery_from)->format('Y-m-d')) }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-12">
                                                            <div class="site-input-groups row">
                                                                <label class="box-input-label col-auto col-label"
                                                                    for="estimated_delivery_to">{{ __('Estimated Delivery To') }}</label>
                                                                <input type="date" id="estimated_delivery_to"
                                                                    class="box-input form-control-sm col"
                                                                    name="estimated_delivery_to"
                                                                    value="{{ old('estimated_delivery_to', optional($order->estimated_delivery_to)->format('Y-m-d')) }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-12">
                                                            <div class="site-input-groups row">
                                                                <label class="box-input-label col-auto col-label"
                                                                    for="tracking_number">{{ __('Tracking Number') }}</label>
                                                                <input type="text" id="tracking_number"
                                                                    class="box-input form-control-sm col"
                                                                    name="tracking_number"
                                                                    value="{{ old('tracking_number', $order->tracking_number) }}"
                                                                    placeholder="{{ __('e.g. 1Z999AA10123456784') }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-12">
                                                            <div class="site-input-groups row">
                                                                <label class="box-input-label col-auto col-label"
                                                                    for="tracking_link">{{ __('Tracking Link') }}</label>
                                                                <input type="url" id="tracking_link"
                                                                    class="box-input form-control-sm col" name="tracking_link"
                                                                    value="{{ old('tracking_link', $order->tracking_link) }}"
                                                                    placeholder="{{ __('https://courier.example/track/...') }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-12">
                                                            <div class="site-input-groups row">
                                                                <label class="box-input-label col-auto col-label"
                                                                    for="delivery_note">{{ __('Delivery Note') }}</label>
                                                                <textarea id="delivery_note" class="form-textarea col" name="delivery_note" rows="3"
                                                                    placeholder="{{ __('Add admin-only delivery note') }}">{{ old('delivery_note', $order->delivery_note) }}</textarea>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-12 mt-2">
                                                            <button type="submit"
                                                                class="site-btn primary-btn">{{ __('Save Delivery') }}</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endcan
                                @endif
                                @if ($order->custom_fields)
                                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                                        <div class="site-card">
                                            <div class="site-card-header">
                                                <h4 class="title-small">{{ __('Custom Information') }}</h4>
                                            </div>
                                            <div class="site-card-body">
                                                @foreach ($order->custom_fields as $key => $value)
                                                    <div class="profile-text-data">
                                                        <div class="attribute">{{ str($key)->headline() }}</div>
                                                        @if (pathinfo($value, PATHINFO_EXTENSION) && file_exists(base_path('assets/' . $value)))
                                                            <div class="value">
                                                                <a href="{{ asset($value) }}" target="_blank"
                                                                    class="link">{{ __('View File') }}</a>
                                                            </div>
                                                        @else
                                                            <div class="value">{{ $value }}</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if ($order->isDeliveredOrCompleted())
                                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                                        <div class="site-card">
                                            <div class="site-card-header">
                                                <h4 class="title-small">{{ __('Delivered Item') }}</h4>
                                            </div>
                                            <div class="site-card-body">
                                                <div class="profile-text-data">
                                                    <div class="attribute">{{ __('Product') }}</div>
                                                    <div class="value">
                                                        {{ $order->listing?->product_name ?? '[Deleted]' }}</div>
                                                </div>
                                                <div class="profile-text-data">
                                                    <div class="attribute">{{ __('Delivered Item') }}</div>
                                                    <div class="value">
                                                        {{ $order->deliveredItemsText() }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if ($order->waitingDeliveryListings()->isNotEmpty())
                                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                                        <div class="site-card">
                                            <div class="site-card-header">
                                                <h4 class="title-small">{{ __('Waiting For Delivery') }}</h4>
                                            </div>
                                            <div class="site-card-body">
                                                @foreach ($order->waitingDeliveryListings() as $waitingItem)
                                                    @php
                                                        $deliveryItemsRoute = route(
                                                            'admin.listing.delivery-items',
                                                            [
                                                                'id' => $waitingItem->listing->id,
                                                                'order_id' => $order->id,
                                                            ],
                                                        );
                                                    @endphp
                                                    <div class="profile-text-data">
                                                        <div class="attribute">{{ __('Product') }}</div>
                                                        <div class="value">{{ $waitingItem->listing->product_name }}
                                                        </div>
                                                    </div>
                                                    <div class="profile-text-data">
                                                        <div class="attribute">{{ __('Delivery Items') }}</div>
                                                        <div class="value">
                                                            <a href="{{ $deliveryItemsRoute }}"
                                                                class="link">{{ __('Update Delivery Items') }}</a>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if ($order->items->isNotEmpty())
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                            <div class="site-card">
                                <div class="site-card-header d-flex">
                                    <h3 class="title">{{ __('Add / Update Review for an Item') }}</h3>
                                </div>
                                <div class="site-card-body">
                                    @php
                                        $firstReview = $order->firstItemReview();
                                    @endphp
                                    <form action="{{ route('admin.order.post-review', $order->id) }}" method="post">
                                        @csrf
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <div class="site-input-groups">
                                                    <label for=""
                                                        class="box-input-label">{{ __('Select Item') }}</label>
                                                    <div class="site-input-groups">
                                                        <select name="order_item_id" class="form-select">
                                                            @foreach ($order->items as $item)
                                                                <option value="{{ $item->id }}">
                                                                    {{ $item->listing?->product_name ?? __('[Deleted]') }}
                                                                    @if ($item->quantity > 1)
                                                                        x{{ $item->quantity }}
                                                                    @endif
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-2">
                                                <div class="site-input-groups">
                                                    <label for=""
                                                        class="box-input-label">{{ __('Rating') }}</label>
                                                    <div class="site-input-groups">
                                                        <select name="rating" class="form-select">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                <option value="{{ $i }}"
                                                                    @selected(old('rating', $firstReview?->rating) == $i)>{{ $i }}
                                                                </option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="site-input-groups">
                                                    <label for=""
                                                        class="box-input-label">{{ __('Details Message') }}</label>
                                                    <textarea name="review" class="form-textarea" maxlength="500" placeholder="{{ __('Details Message') }}">{{ old('review', $firstReview?->review ?? '') }}</textarea>
                                                </div>
                                            </div>

                                            <div class="col-12 mt-2">
                                                <button type="submit"
                                                    class="site-btn primary-btn">{{ __('Save Review') }}</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                        <div class="site-card">
                            <div class="site-card-header d-flex">
                                <h3 class="title">{{ __('Order Details') }}</h3>
                            </div>
                            <div class="site-card-body">
                                <div class="site-table table-responsive profile-text-data">
                                    <table class="table attribute mb-0">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Item Name') }}</th>
                                                <th>{{ __('Item Status') }}</th>
                                                <th>{{ __('Unit Price') }}</th>
                                                <th>{{ __('Quantity') }}</th>
                                                <th>{{ __('Subtotal') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($order->items as $item)
                                                <tr>
                                                    <td><a target="_blank" class="link"
                                                            href="{{ route('admin.listing.edit', $item?->listing?->id ?? 0) }}">{{ $item->product_name ?? ($item->listing?->product_name ?? '[Deleted]') }}</a>
                                                        @if (!empty($item->selected_attributes))
                                                            <div class="mt-1">
                                                                @foreach ($item->selected_attributes as $attribute)
                                                                    <div class="small text-muted">
                                                                        {{ $attribute['group'] ?? __('Attribute') }}:
                                                                        {{ $attribute['label'] ?? '-' }}
                                                                        ({{ amountWithCurrency((float) ($attribute['price'] ?? 0), setting('site_currency', 'global')) }})
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {!! bsToAdminBadges($item->status_badge) !!}
                                                    </td>
                                                    <td>
                                                        @if ($item->hasDiscount())
                                                            <del
                                                                class="text-danger">{{ $item->displayOriginalPrice() }}</del>
                                                            <br>
                                                            {{ $item->displayUnitPrice() }}
                                                        @else
                                                            {{ $item->displayUnitPrice() }}
                                                        @endif
                                                    </td>
                                                    <td>{{ $item->quantity }}</td>
                                                    <td>{{ amountWithCurrency($item->total_price, setting('site_currency', 'global')) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        <tfoot>
                                            <tr>
                                                <td colspan="4">{{ __('Subtotal') }}</td>
                                                <td>{{ amountWithCurrency($order->subtotal, setting('site_currency', 'global')) }}
                                                </td>
                                            </tr>
                                            @if ($order->discount_amount > 0)
                                                <tr>
                                                    <td colspan="4">{{ __('Coupon Discount') }}</td>
                                                    <td>-{{ amountWithCurrency($order->discount_amount, setting('site_currency', 'global')) }}
                                                    </td>
                                                </tr>
                                            @endif
                                            @if ($order->final_shipping_charge > 0)
                                                <tr>
                                                    <td colspan="4">{{ __('Shipping') }}</td>
                                                    <td>{{ amountWithCurrency($order->final_shipping_charge, setting('site_currency', 'global')) }}
                                                    </td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td colspan="4">{{ __('Gateway Charge') }}</td>
                                                <td>{{ amountWithCurrency($order->transaction->charge, setting('site_currency', 'global')) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="4">{{ __('Total') }}</td>
                                                <td>{{ amountWithCurrency($order->transaction->final_amount, setting('site_currency', 'global')) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="4"><strong>{{ __('Total Payable') }}</strong></td>
                                                <td>
                                                    <strong style="color:#000000;">
                                                        {{ amountWithCurrency($order->transactions->first()->pay_amount, $order->transactions->first()->pay_currency) }}
                                                    </strong>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>

                    @if ($order->is_bnpl && $order->bnplItemLoans)
                        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                            <div class="site-card">
                                <div class="site-card-header d-flex">
                                    <h3 class="title">{{ __('BNPL Management') }}</h3>
                                </div>
                                <div class="site-card-body">
                                    @php
                                        $loan = $order->bnplItemLoans;
                                    @endphp
                                    <div class="site-table table-responsive profile-text-data mb-4">
                                        <table class="table attribute mb-0">
                                            <tbody>
                                                <tr>
                                                    <td><strong>{{ __('Item') }}</strong></td>
                                                    <td>{{ $loan->orderItem?->listing?->product_name ?? '[Deleted]' }}
                                                    </td>
                                                    <td><strong>{{ __('Loan Status') }}</strong></td>
                                                    <td>{{ str($loan->status)->headline() }}</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>{{ __('Total Amount') }}</strong></td>
                                                    <td>{{ amountWithCurrency($loan->total_item_amount, setting('site_currency', 'global')) }}
                                                    </td>
                                                    <td><strong>{{ __('Outstanding') }}</strong></td>
                                                    <td>{{ amountWithCurrency($loan->remaining_due_amount, setting('site_currency', 'global')) }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="site-table table-responsive profile-text-data mb-4">
                                        <table class="table attribute mb-0">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Installment') }}</th>
                                                    <th>{{ __('Due Date') }}</th>
                                                    <th>{{ __('Principal') }}</th>
                                                    <th>{{ __('Interest') }}</th>
                                                    <th>{{ __('Late Fee') }}</th>
                                                    <th>{{ __('Total Due') }}</th>
                                                    <th>{{ __('Paid Amount') }}</th>
                                                    <th>{{ __('Status') }}</th>
                                                    <th>{{ __('Paid At') }}</th>
                                                    <th>{{ __('Update') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($loan->installments as $installment)
                                                    <tr>
                                                        <td>#{{ $installment->installment_no }}</td>
                                                        <td>{{ $installment->due_at?->format('Y-m-d h:i A') ?? '-' }}
                                                        </td>
                                                        <td>{{ amountWithCurrency($installment->principal_amount, setting('site_currency', 'global')) }}
                                                        </td>
                                                        <td>{{ amountWithCurrency($installment->interest_amount, setting('site_currency', 'global')) }}
                                                        </td>
                                                        <td>{{ amountWithCurrency($installment->late_fee_amount, setting('site_currency', 'global')) }}
                                                        </td>
                                                        <td>{{ amountWithCurrency($installment->total_due_amount, setting('site_currency', 'global')) }}
                                                        </td>
                                                        <td>{{ amountWithCurrency($installment->paid_amount, setting('site_currency', 'global')) }}
                                                        </td>
                                                        <td>{{ str($installment->status)->headline() }}</td>
                                                        <td>{{ $installment->paid_at?->format('Y-m-d H:i') ?? '-' }}
                                                        </td>
                                                        <td>
                                                            @can('order-update')
                                                                <form
                                                                    action="{{ route('admin.order.bnpl-installment.update', [$order->id, $installment->id]) }}"
                                                                    method="post" class="d-flex gap-2 align-items-center">
                                                                    @csrf
                                                                    <div class="site-input-groups">
                                                                        <select name="status" class="form-select">
                                                                            @foreach (['pending', 'processing', 'paid', 'partial', 'overdue', 'cancelled'] as $status)
                                                                                <option value="{{ $status }}"
                                                                                    @selected($installment->status === $status)>
                                                                                    {{ str($status)->headline() }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                        <input type="number" step="0.01" min="0"
                                                                            name="paid_amount"
                                                                            value="{{ (float) $installment->paid_amount }}"
                                                                            class="box-input form-control-sm"
                                                                            style="max-width:120px;"
                                                                            placeholder="{{ __('Paid') }}">
                                                                    </div>
                                                                    <button type="submit"
                                                                        class="site-btn-sm primary-btn">{{ __('Save') }}</button>
                                                                </form>
                                                            @else
                                                                -
                                                            @endcan
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>


    @endsection
    @section('script')
        <script>
            "use strict";
            $(document).on('change', '#updateStatusForm', function() {
                $('#updateStatusForm').submit();
            });
        </script>
    @endsection
