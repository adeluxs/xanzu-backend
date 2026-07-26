<div class="site-card mx-3 mt-3">
    <div class="site-card-header">
        <h4 class="title-small">{{ __('Listing Info') }}</h4>
        @if ($listing->type->value !== 'physical')
            <div class="card-header-links">
                <a href="{{ route('admin.listing.delivery-items', $listing->id) }}" class="site-btn-sm primary-btn">
                    <i data-lucide="package" class="me-1"></i>{{ __('Delivery Items') }}
                </a>
            </div>
        @endif
    </div>
    <div class="site-card-body">
        <div class="profile-text-data">
            <div class="attribute">{{ __('Product Name') }}</div>
            <div class="value">{{ $listing->product_name }}</div>
        </div>
        <div class="profile-text-data">
            <div class="attribute">{{ __('Category') }}</div>
            <div class="value">{{ $listing->category->name }}</div>
        </div>
        <div class="profile-text-data">
            <div class="attribute">{{ __('Seller') }}</div>
            <div class="value">
                <a class="link" href="{{ route('admin.user.edit', $listing->seller_id) }}">
                    {{ $listing->seller?->username ?? __('Deleted User') }}
                </a>
            </div>
        </div>
        <div class="profile-text-data">
            <div class="attribute">{{ __('Provider') }}</div>
            <div class="value">{{ $listing->provider?->name ?? '—' }}</div>
        </div>
        <div class="profile-text-data">
            <div class="attribute">{{ __('Price') }}</div>
            <div class="value">{{ $currencySymbol . $listing->price }}</div>
        </div>
        @if ($listing->discount_amount > 0)
            <div class="profile-text-data">
                <div class="attribute">{{ __('Discount') }}</div>
                <div class="value">{{ $currencySymbol . $listing->discount_amount }}</div>
            </div>
            <div class="profile-text-data">
                <div class="attribute">{{ __('Final Price') }}</div>
                <div class="value">{{ $currencySymbol . $listing->final_price }}</div>
            </div>
        @endif
        <div class="profile-text-data">
            <div class="attribute">{{ __('Quantity') }}</div>
            <div class="value">{{ $listing->quantity }}</div>
        </div>
        <div class="profile-text-data">
            <div class="attribute">{{ __('Listing Status') }}</div>
            <div class="value">{!! bsToAdminBadges($listing->status_badge) !!}</div>
        </div>
        <div class="profile-text-data">
            <div class="attribute">{{ __('Trending Status') }}</div>
            <div class="value">
                <div class="site-badge {{ $listing->is_trending ? 'success' : 'danger' }}">
                    {{ $listing->is_trending ? __('Trending') : __('Not Trending') }}
                </div>
            </div>
        </div>
        <div class="profile-text-data">
            <div class="attribute">{{ __('Feature Image') }}</div>
            <div class="value">
                <a href="{{ asset($listing->thumbnail) }}" target="_blank" class="link">
                    <img src="{{ asset($listing->thumbnail) }}" alt="{{ $listing->product_name }}"
                        class="img-thumbnail" width="100">
                </a>
            </div>
        </div>
        <div class="profile-text-data">
            <div class="attribute">{{ __('Images') }}</div>
            <div class="value d-flex flex-wrap gap-2">
                @foreach ($listing->images as $image)
                    <a href="{{ asset($image->image_path) }}" target="_blank" class="link">
                        {{ basename($image->image_path) }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="site-card mt-4 mx-3">
    <div class="site-card-header">
        <h4 class="title-small">{{ __('Listing Status Management') }}</h4>
    </div>
    <div class="site-card-body">
        <div class="row g-4">
            <div class="col-xxl-12">
                @can('listing-edit')
                    <div class="profile-text-data">
                        <div class="attribute">{{ __('Trending Toggle') }}</div>
                        <div class="value">
                            <a href="{{ route('admin.listing.trending.toggle', $listing->id) }}"
                                class="site-btn-sm {{ $listing->is_trending ? 'green-btn' : 'red-btn' }}">
                                <i data-lucide="{{ $listing->is_trending ? 'x-circle' : 'trending-up' }}"
                                    class="me-1"></i>
                                {{ $listing->is_trending ? __('Remove from Trending') : __('Mark as Trending') }}
                            </a>
                        </div>
                    </div>
                @endcan
            </div>
            <div class="col-xxl-12">
                <form action="{{ route('admin.listing.status.update', $listing->id) }}" method="POST">
                    @csrf
                    <div class="profile-text-data">
                        <div class="attribute">
                            <label for="status" class="mb-0">{{ __('Change Listing Status') }}</label>
                        </div>
                        <div class="value site-input-groups">
                            <select class="form-select" name="status" id="status">
                                @foreach (\App\Enums\ListingStatus::cases() as $status)
                                    <option value="{{ $status->value }}"
                                        {{ $listing->status == $status->value ? 'selected' : '' }}>
                                        {{ ucwords($status->value) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="site-btn-sm primary-btn">{{ __('Update Status') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
