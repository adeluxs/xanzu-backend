<div class="">
    <div class="site-card">
        <div class="site-card-header">
            <h3 class="title">{{ __($fields['title']) }}</h3>
        </div>
        <div class="site-card-body">
            @include('backend.setting.site_setting.include.form.__open_action')
            <div class="site-input-groups row">
                <label for="" class="col-sm-4 col-label">{{ __('Flash Sale') }}</label>
                <div class="col-sm-8">
                    <div id="flash-sell-container">
                        <select name="is_flash_sale" class="form-select" id="is_flash_sale">
                            <option @selected(setting('is_flash_sale', 'sell')) value="1">{{ __('Enable') }}</option>
                            <option @selected(!setting('is_flash_sale', 'sell')) value="0">{{ __('Disabled') }}</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="site-input-groups row flash-sell-date {{ setting('is_flash_sale', 'sell') ? '' : 'd-none' }}">
                <label for="" class="col-sm-4 col-label">{{ __('Flash Sale Date') }}</label>
                <div class="col-sm-8">
                    <div id="flash-sell-date-container">
                        <input type="date" name="flash_sale_date" class="form-control" id="flash_sale_date"
                            value="{{ oldSetting('flash_sale_date', 'sell') }}">
                    </div>
                </div>
            </div>
            @include('backend.setting.site_setting.include.form.__close_action')
        </div>
    </div>
</div>
@push('single-script')
    <script>
        "use strict";
        $('#is_flash_sale').select2();
        $(document).on('change', '#is_flash_sale', function() {
            if ($(this).val() == 1) {
                $('.flash-sell-date').removeClass('d-none');
            } else {
                $('.flash-sell-date').addClass('d-none');
            }
        })
    </script>
@endpush
