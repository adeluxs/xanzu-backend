@if (request('tab') == 'purchase')
    <div @class([
        'tab-pane fade',
        'show active' => request('tab') == 'purchase',
    ]) id="pills-deposit" role="tabpanel" aria-labelledby="pills-deposit-tab">
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="site-card">
                    <div class="site-card-header">
                        <h4 class="title">{{ __('Purchase') }}</h4>
                        <div class="card-header-info">{{ __('Total Purchase:') }}
                            {{ $user->total_amount_purchased ?? 0 }} {{ $currency }}, {{ __('Total Products:') }}
                            {{ ($user->total_purchased ?? 0) . ' ' . __('Items') }}</div>
                    </div>
                    <div class="site-card-body table-responsive">

                        <div class="site-table">
                            <div class="table-filter">
                                <form action="" method="get">
                                    <input type="hidden" name="tab" value="purchase">
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
                                        @include('backend.filter.th', [
                                            'label' => 'Date',
                                            'field' => 'created_at',
                                        ])
                                        @include('backend.filter.th', [
                                            'label' => 'Description',
                                            'field' => 'description',
                                        ])
                                        @include('backend.filter.th', [
                                            'label' => 'Amount',
                                            'field' => 'final_amount',
                                        ])
                                        @include('backend.filter.th', [
                                            'label' => 'Listing',
                                            'field' => 'listing',
                                        ])
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($purchases as $earn)
                                        <tr>
                                            <td>{{ $earn->created_at }}</td>
                                            <td>{{ $earn->description }}</td>
                                            <td>
                                                @include('backend.transaction.include.__txn_amount_ajax', [
                                                    'amount' => $earn->final_amount,
                                                    'type' => $earn->type,
                                                ])
                                            </td>
                                            <td>
                                                <a class="link"
                                                    href="{{ route('admin.listing.edit', $earn?->order?->listing?->id) }}">{{ $earn?->order?->listing?->product_name ?? '' }}</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <td colspan="7" class="text-center">{{ __('No Data Found!') }}</td>
                                    @endforelse
                                </tbody>
                            </table>
                            {{ $purchases->links('backend.include.__pagination') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
