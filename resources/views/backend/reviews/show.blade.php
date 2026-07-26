<div class="modal-body">
    <div class="row">
        <div class="col-lg-12">
            <div class="site-card">
                <div class="site-card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h3 class="title">
                            {{ __(':type #', ['type' => $review->parent_id ? 'Seller Reply' : 'Review']) }}{{ $review->id }}
                        </h3>
                        <div class="review-status">
                            @if ($review->status->value == 'pending')
                                <span class="site-badge rounded-pill warning">{{ __('Pending') }}</span>
                            @elseif($review->status->value == 'approved')
                                <span class="site-badge rounded-pill success">{{ __('Approved') }}</span>
                            @else
                                <span
                                    class="site-badge rounded-pill danger">{{ str($review->status->value)->headline() }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="site-card-body">

                    <div class="row">
                        <div class="col-md-6 single-profile-info">
                            <div class="profile-info-title">
                                <h6>{{ __('Order ID') }} <span>#{{ $review->order_id }}</span></h6>
                            </div>
                        </div>

                        @if (!$review->parent_id)
                            <div class="col-md-6 single-profile-info">
                                <div class="profile-info-title d-flex align-items-center">
                                    <h6 class="mb-0">{{ __('Rating') }} </h6>
                                    <div class="d-flex align-items-center ms-2">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <span
                                                style="color: {{ $i <= $review->rating ? '#ffc107' : '#dee2e6' }}; font-size: 18px;">★</span>
                                        @endfor
                                        <span class="ms-2 fw-bold">({{ $review->rating }}/5)</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="single-profile-info my-2">
                        <div class="profile-info-title">
                            <h6>{{ __('Review Text') }}</h6>
                        </div>
                        <div class="profile-info-value">
                            <div class="review-text-content p-3 bg-light rounded">
                                {{ $review->review ?? 'No review text provided.' }}
                            </div>
                        </div>
                    </div>

                    @php
                        $attachments = collect($review->attachments ?? [])->filter();
                    @endphp
                    @if ($attachments->isNotEmpty())
                        <div class="single-profile-info mb-2">
                            <div class="profile-info-title">
                                <h6>{{ __('Attachments') }}</h6>
                            </div>
                            <div class="profile-info-value">
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($attachments as $attachment)
                                        @php
                                            $attachmentUrl = str($attachment)->startsWith(['http://', 'https://'])
                                                ? $attachment
                                                : asset($attachment);
                                        @endphp
                                        <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener noreferrer"
                                            class="d-inline-block" title="{{ __('View attachment') }}">
                                            <img src="{{ $attachmentUrl }}" alt="{{ __('Attachment image') }}"
                                                style="width: 90px; height: 90px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb;">
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($review->admin_notes)
                        <div class="single-profile-info mb-2">
                            <div class="profile-info-title">
                                <h6>{{ __('Admin Notes') }}</h6>
                            </div>
                            <div class="profile-info-value">
                                <div class="admin-notes-content text-danger">
                                    {{ $review->admin_notes }}
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($review->flag_reason)
                        <div class="single-profile-info mb-2">
                            <div class="profile-info-title">
                                <h6>{{ __('Flag Reason') }}</h6>
                            </div>
                            <div class="profile-info-value">
                                <div class="flag-reason-content text-warning">
                                    {{ $review->flag_reason }}
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="single-profile-info">
                                <div class="profile-info-title">
                                    <h6>{{ __('Review Date') }}</h6>
                                </div>
                                <div class="profile-info-value">
                                    <span>{{ $review->created_at->format('M d, Y \a\t g:i A') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="single-profile-info">
                                <div class="profile-info-title">
                                    <h6>{{ __('Reviewed At') }}</h6>
                                </div>
                                <div class="profile-info-value">
                                    <span>{{ $review->reviewed_at ? $review->reviewed_at->format('M d, Y \a\t g:i A') : 'Not reviewed yet' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="review-actions border-top pt-3 mt-3">
                        <h6>{{ __('Review Actions') }}</h6>
                        <div class="d-flex gap-2">
                            @if ($review->status->value != 'approved')
                                <a href="{{ route('admin.reviews.approve', $review->id) }}"
                                    class="site-btn primary-btn site-btn-xs btn-sm">
                                    <i data-lucide="check"></i> {{ __('Approve Review') }}
                                </a>
                            @endif
                            @if ($review->status->value != 'rejected' && $review->status->value != 'flagged')
                                <button type="button" data-bs-dismiss="modal" aria-label="Close"
                                    class="site-btn site-btn-xs red-btn btn-sm rejectBtn"
                                    data-id="{{ $review->id }}">
                                    <i data-lucide="ban"></i> {{ __('Reject Review') }}
                                </button>
                            @endif
                            @if ($review->status->value == 'flagged')
                                <button type="submit" id="deleteBtn" data-id="{{ $review->id }}"
                                    class="site-btn site-btn-xs red-btn btn-sm">{{ __('Delete Review') }}</button>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Listing Information -->
        <div class="col-lg-12">
            <div class="site-card mb-4">
                <div class="site-card-header">
                    <h3 class="title">{{ __('Listing Information') }}</h3>
                </div>
                <div class="site-card-body">
                    @if ($review->listing)
                        <div class="listing-details row gy-2">
                            <div class="single-profile-info">
                                <div class="profile-info-title">
                                    <h6>{{ __('Product Name') }}</h6>
                                </div>
                                <div class="profile-info-value">
                                    <span>{{ Str::limit($review->listing->product_name, 50) }}</span>
                                </div>
                            </div>

                            <div class="single-profile-info">
                                <div class="profile-info-title">
                                    <h6>{{ __('Price') }}</h6>
                                </div>
                                <div class="profile-info-value">
                                    <span>${{ number_format($review->listing->price, 2) }}</span>
                                </div>
                            </div>

                            <div class="single-profile-info">
                                <div class="profile-info-title">
                                    <h6>{{ __('Category') }}</h6>
                                </div>
                                <div class="profile-info-value">
                                    <span>{{ $review->listing->category->name ?? 'N/A' }}</span>
                                </div>
                            </div>

                            <div class="single-profile-info">
                                <div class="profile-info-value">
                                    <a href="{{ route('admin.listing.edit', $review->listing->id) }}" target="_blank"
                                        class="site-btn site-btn-xs primary-btn btn-sm">
                                        <i data-lucide="external-link"></i> {{ __('View Listing') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="text-muted">{{ __('Listing not found') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Buyer Information Card -->
        <div class="col-lg-6">
            <div class="site-card mb-4">
                <div class="site-card-header">
                    <h3 class="title">{{ __('Buyer Information') }}</h3>
                </div>
                <div class="site-card-body">
                    @if ($review->buyer)
                        <div class="d-flex align-items-center mb-3">
                            <img src="{{ $review->buyer->avatar_path }}" alt="{{ $review->buyer->username }}"
                                class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                            <div>
                                <h6 class="mb-1">{{ $review->buyer->username }}</h6>
                                <small class="text-muted">{{ $review->buyer->email }}</small>
                            </div>
                        </div>
                        <div class="single-profile-info">

                            <div class="profile-info-value">
                                <a href="{{ route('admin.user.edit', $review->buyer_id) }}"
                                    class="site-btn site-btn-xs primary-btn btn-sm">
                                    <i data-lucide="user"></i> {{ __('View Profile') }}
                                </a>
                            </div>
                        </div>
                    @else
                        <p class="text-muted">{{ __('Buyer not found') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Seller Information Card -->
        <div class="col-lg-6">
            <div class="site-card mb-4">
                <div class="site-card-header">
                    <h3 class="title">{{ __('Seller Information') }}</h3>
                </div>
                <div class="site-card-body">
                    @if ($review->seller)
                        <div class="d-flex align-items-center mb-3">
                            <img src="{{ $review->seller->avatar_path }}" alt="{{ $review->seller->username }}"
                                class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                            <div>
                                <h6 class="mb-1">{{ $review->seller->username }}</h6>
                                <small class="text-muted">{{ $review->seller->email }}</small>
                            </div>
                        </div>

                        <div class="single-profile-info">

                            <div class="profile-info-value">
                                <a href="{{ route('admin.user.edit', $review->seller_id) }}"
                                    class="site-btn site-btn-xs primary-btn btn-sm">
                                    <i data-lucide="user"></i> {{ __('View Profile') }}
                                </a>
                            </div>
                        </div>
                    @else
                        <p class="text-muted">{{ __('Seller not found') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    #rejectModal {
        z-index: 1056 !important;
    }
</style>
