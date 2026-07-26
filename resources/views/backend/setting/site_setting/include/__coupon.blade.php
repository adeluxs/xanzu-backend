<div class="">
    <div class="site-card">
        <div class="site-card-header">
            <h3 class="title">{{ __($fields['title']) }}</h3>
        </div>
        <div class="site-card-body">
            @include('backend.setting.site_setting.include.form.__open_action')
            <div class="site-input-groups row">
                <div class="col-sm-4 col-label pt-0">{{ __('Coupon Approval') }}</div>
                <div class="col-sm-8">
                    <div class="form-switch ps-0">
                        {{-- <input class="form-check-input" type="hidden" value="1" name="{{$field['name']}}"/> --}}
                        <div class="switch-field same-type m-0">
                            <input type="radio" id="active-coupon-0" name="{{ 'coupon_approval' }}" value="1"
                                @if (oldSetting('coupon_approval', 'coupon') == '1') checked @endif />
                            <label for="active-coupon-0">{{ __('Enabled') }}</label>
                            <input type="radio" id="disable-coupon-1" name="{{ 'coupon_approval' }}" value="0"
                                @if (oldSetting('coupon_approval', 'coupon') == '0') checked @endif />
                            <label for="disable-coupon-1">{{ __('Disabled') }}</label>
                        </div>
                    </div>
                </div>
            </div>
            @include('backend.setting.site_setting.include.form.__close_action')
        </div>
    </div>
</div>
@push('single-script')
@endpush
