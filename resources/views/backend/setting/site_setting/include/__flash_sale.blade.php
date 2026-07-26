<div class="">
    <div class="site-card">
        <div class="site-card-header">
            <h3 class="title">{{ __($fields['title']) }}</h3>
        </div>
        @php
            $flashSellStatus = json_decode(oldSetting('flash_sale_status', 'flash_sale'));
            $flashSellStartDate = oldSetting('flash_sale_start_date', 'flash_sale');
            $flashSellEndDate = oldSetting('flash_sale_end_date', 'flash_sale');
        @endphp
        <div class="site-card-body">
            @include('backend.setting.site_setting.include.form.__open_action')

            <div class="site-input-groups row">
                <div class="col-sm-4 col-label pt-0">{{ __('Flash Sale Status') }}
                </div>

                <div class="col-sm-8">
                    <div class="form-switch ps-0">
                        <input class="form-check-input" type="hidden" value="0" name="flash_sale_status" />
                        <div class="switch-field same-type m-0">
                            <input onchange="fieldActiveToggle('flash_sale_status','.flash-sale-input')" type="radio"
                                id="active-flash_sale_status" name="flash_sale_status" value="1"
                                @if (oldSetting('flash_sale_status', $section)) checked @endif />
                            <label for="active-flash_sale_status">{{ __('Enable') }}</label>
                            <input onchange="fieldActiveToggle('flash_sale_status','.flash-sale-input')" type="radio"
                                id="disable-flash_sale_status" name="flash_sale_status" value="0"
                                @if (!oldSetting('flash_sale_status', $section)) checked @endif />
                            <label for="disable-flash_sale_status">{{ __('Disabled') }}</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="site-input-groups flash-sale-input row {{ $flashSellStatus == 1 ? '' : 'd-none' }}">
                <label for="" class="col-sm-4 col-label">
                    {{ __('Flash Sale Start Date') }}
                </label>
                <div class="col-sm-8">
                    <div class="input-group joint-input">
                        <input type="datetime-local" name="flash_sale_start_date" class=" form-control "
                            value="{{ $flashSellStartDate }}">
                    </div>
                </div>
            </div>

            <div class="site-input-groups flash-sale-input row {{ $flashSellStatus == 1 ? '' : 'd-none' }}">
                <label for="" class="col-sm-4 col-label">
                    {{ __('Flash Sale End Date') }}
                </label>
                <div class="col-sm-8">
                    <div class="input-group joint-input">
                        <input type="datetime-local" name="flash_sale_end_date" class=" form-control "
                            value="{{ $flashSellEndDate }}">
                    </div>
                </div>
            </div>

            @include('backend.setting.site_setting.include.form.__close_action')
        </div>
    </div>
</div>
@push('single-script')
@endpush
