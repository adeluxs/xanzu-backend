<form action="{{ request()->url() }}" method="get" id="filterForm">
    <div class="table-filter">
        <div class="filter">
            <div class="search">
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('Search') }}..." />
            </div>
            <button type="submit" class="apply-btn"><i data-lucide="search"></i>{{ __('Search') }}</button>
        </div>
        <div class="filter d-flex">
            @if (isset($status) && $status == true)
                <select class="form-select form-select-sm" aria-label=".form-select-sm example" name="status"
                    id="status">
                    <option value="" disabled selected>--{{ __('Status') }}--</option>
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>{{ __('All') }}
                    </option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('Active') }}
                    </option>
                    <option value="deactive" {{ request('status') == 'deactive' ? 'selected' : '' }}>
                        {{ __('Deactive') }}</option>
                </select>
            @endif
            <select class="form-select form-select-sm" aria-label=".form-select-sm example" name="order"
                id="order">
                <option value="asc" {{ request('order') == 'asc' ? 'selected' : '' }}>{{ __('ASC') }}</option>
                <option value="desc" {{ request('order') == 'desc' ? 'selected' : '' }}>{{ __('DESC') }}</option>
            </select>
            <select class="form-select form-select-sm show" aria-label=".form-select-sm example" name="perPage"
                id="perPage">
                <option value="15" {{ request('perPage') == 15 ? 'selected' : '' }}>{{ __('15') }}</option>
                <option value="30" {{ request('perPage') == 30 ? 'selected' : '' }}>{{ __('30') }}</option>
                <option value="45" {{ request('perPage') == 45 ? 'selected' : '' }}>{{ __('45') }}</option>
                <option value="60" {{ request('perPage') == 60 ? 'selected' : '' }}>{{ __('60') }}</option>
            </select>
            @if (isset($button))
                <a href="{{ $route }}" class="apply-btn"><i data-lucide="settings"></i>{{ $text }}</a>
            @endif

        </div>
    </div>
</form>
@push('single-script')
    <script>
        (function($) {
            "use strict";
            $('#perPage').on('change', function() {
                $('#filterForm').submit();
            });

            $('#order').on('change', function() {
                $('#filterForm').submit();
            });

            $('#status').on('change', function() {
                $('#filterForm').submit();
            });
        })(jQuery);
    </script>
@endpush
