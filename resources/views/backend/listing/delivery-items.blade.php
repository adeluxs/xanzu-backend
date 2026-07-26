@extends('backend.layouts.app')
@section('title')
    {{ __('Listing Delivery Items') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Delivery Items') }} - {{ $listing->product_name }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 col-lg-12 col-md-12 col-12">
                    <div class="site-card">
                        <div class="site-card-header">
                            <h3 class="title">{{ __('Listing Delivery Items') }}</h3>
                            <div class="card-header-links">
                                <a href="{{ route('admin.listing.edit', $listing->id) }}" class="site-btn-xs primary-btn">
                                    <i data-lucide="edit"></i>{{ __('Edit Listing') }}
                                </a>
                                <a href="{{ route('admin.listing.index') }}" class="site-btn-xs primary-btn">
                                    <i data-lucide="list"></i>{{ __('All Listings') }}
                                </a>
                            </div>
                        </div>
                        <div class="site-card-body">
                            <form method="POST"
                                action="{{ route('admin.listing.delivery-items.store', [
                                    'id' => $listing->id,
                                    'order_id' => request()->order_id,
                                ]) }}">
                                @csrf
                                <div class="row g-4">
                                    @foreach ($listing->deliveryItems as $item)
                                        @if (request('order_id') && $order && $loop->iteration > $order->quantity)
                                            @continue
                                        @endif
                                        <div class="col-lg-6">
                                            <div class="site-input-groups">
                                                <label class="box-input-label">
                                                    {{ __('Delivery Item No.') }} {{ $loop->iteration }}
                                                    @if ($item->is_used)
                                                        <span class="badge bg-warning text-dark">{{ __('Used') }}</span>
                                                    @endif
                                                </label>
                                                <textarea @readonly($item->is_used) required name="delivery_items[{{ $item->id }}]" class="form-textarea" rows="3"
                                                    placeholder="{{ __('Enter Delivery Items') }}">{{ $item->data }}</textarea>
                                            </div>
                                        </div>
                                    @endforeach

                                    @if ($listing->deliveryItems->count() === 0)
                                        <div class="col-12">
                                            <div class="alert alert-info">
                                                {{ __('No delivery items found for this listing.') }}
                                            </div>
                                        </div>
                                    @endif

                                    @if ($listing->deliveryItems->count() > 0)
                                        <div class="col-12">
                                            <button type="submit" class="site-btn primary-btn">
                                                {{ __('Update Delivery Items') }}
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
