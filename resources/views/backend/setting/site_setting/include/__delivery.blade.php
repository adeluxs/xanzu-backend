<div class="">
    <div class="site-card">
        <div class="site-card-header">
            <h3 class="title">{{ __($fields['title']) }}</h3>
        </div>
        @php
            $oldSetting = oldSetting('delivery_method', 'delivery');
            $deliveryMethod = is_array($oldSetting) ? $oldSetting : json_decode($oldSetting);
        @endphp

        <div class="site-card-body">
            @include('backend.setting.site_setting.include.form.__open_action')
            <div class="site-input-groups row">
                <label for="" class="col-sm-4 col-label">{{ __('Delivery Method') }}</label>
                <div class="col-sm-8">
                    <div id="delivery-method-container">
                        <select name="delivery_method[]" multiple id="delivery_method">
                            <option @selected(in_array('auto', $deliveryMethod)) value="auto">{{ __('Automatic') }}</option>
                            <option @selected(in_array('manual', $deliveryMethod)) value="manual">{{ __('Manual') }}</option>
                        </select>
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
        const choices = new Choices($('#delivery_method')[0], {
            removeItems: true,
            removeItemButton: true,
            removeItemButtonAlignLeft: true,
            shouldSort: true
        });
    </script>
@endpush
