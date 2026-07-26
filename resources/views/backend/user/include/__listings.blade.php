@if (request('tab') == 'listings')
    <div @class([
        'tab-pane fade',
        'show active' => request('tab') == 'listings',
    ]) id="pills-deposit" role="tabpanel" aria-labelledby="pills-deposit-tab">
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="site-card">
                    <div class="site-card-header">
                        <h4 class="title">{{ __('Listings') }}</h4>
                    </div>
                    <div class="site-card-body table-responsive">

                        <div class="site-table">
                            <div class="table-filter">
                                <form action="" method="get">
                                    <input type="hidden" name="tab" value="listings">
                                    <div class="filter d-flex">
                                        <div class="search">
                                            <label for="">{{ __('Search:') }}</label>
                                            <input type="text" name="query" value="{{ request('query') }}" />
                                        </div>
                                        <button class="apply-btn" type="submit"><i
                                                data-lucide="search"></i>{{ __('Search') }}</button>
                                    </div>
                                </form>
                            </div>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('Product Name') }}</th>
                                        <th scope="col">{{ __('Category') }}</th>
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
                                            <td><strong>
                                                    <a class="link"
                                                        href="{{ route('admin.listing.edit', $listing->id) }}">{{ $listing->product_name }}</a>
                                                </strong></td>
                                            <td>{{ $listing->category->name }}</td>
                                            <td>{{ $currencySymbol . $listing->discount_amount }}</td>
                                            <td>{{ $currencySymbol . $listing->price }}</td>
                                            <td>{{ $listing->quantity }}</td>
                                            <td>
                                                {!! bsToAdminBadges($listing->status_badge) !!}
                                            </td>
                                            <td>
                                                @can('listing-delete')
                                                    <a href="#" class="round-icon-btn red-btn" id="deleteBtn"
                                                        data-id="{{ $listing->id }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top" data-bs-original-title="Delete Listing">
                                                        <i data-lucide="trash"></i>
                                                    </a>
                                                @endcan
                                                @can('listing-edit')
                                                    <a href="{{ route('admin.listing.approval.toggle', $listing->id) }}"
                                                        class="round-icon-btn {{ $listing->is_approved ? 'green-btn' : 'red-btn' }}"
                                                        data-id="{{ $listing->id }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-bs-original-title="{{ $listing->is_approved ? 'Unapproved' : 'Approve' }} Listing">
                                                        <i data-lucide="{{ $listing->is_approved ? 'check' : 'ban' }}"></i>
                                                    </a>
                                                @endcan
                                                @can('listing-edit')
                                                    <a href="{{ route('admin.listing.trending.toggle', $listing->id) }}"
                                                        class="round-icon-btn {{ $listing->is_trending ? 'green-btn' : 'red-btn' }}"
                                                        data-id="{{ $listing->id }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-bs-original-title="{{ $listing->is_trending ? 'Unset' : 'Set' }} {{ __('trending Listing') }}">
                                                        <i data-lucide="flame"></i>
                                                    </a>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <td colspan="8" class="text-center">{{ __('No Data Found!') }}</td>
                                    @endforelse
                                </tbody>
                            </table>
                            {{ $listings->links('backend.include.__pagination') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
