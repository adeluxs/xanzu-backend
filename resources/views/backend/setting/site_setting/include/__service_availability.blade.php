<div class="">
    <div class="site-card">
        <div class="site-card-header">
            <h3 class="title">{{ __($fields['title']) }}</h3>
        </div>
        <div class="site-card-body">
            <p class="paragraph mb-4">
                <i data-lucide="shield-alert"></i>
                {!! __('<strong>Full HTTP suspension:</strong> Customer web, mobile API and administrator panel access will all be stopped. After saving, restore access only from the server terminal with <code>php artisan service:access restore</code>. Health/status checks and payment callbacks remain available.') !!}
            </p>

            @include('backend.setting.site_setting.include.form.__open_action')

            <div class="site-input-groups row">
                <div class="col-sm-4 col-label pt-0">{{ __('Suspend All HTTP Access') }}</div>
                <div class="col-sm-8">
                    <input type="hidden" name="service_suspended" value="0" />
                    <div class="switch-field same-type m-0">
                        <input onchange="fieldActiveToggle('service_suspended','.service-suspension-inputs')"
                            type="radio" id="service-suspended-enable" name="service_suspended" value="1"
                            @checked(oldSetting('service_suspended', $section)) />
                        <label for="service-suspended-enable">{{ __('Enable') }}</label>
                        <input onchange="fieldActiveToggle('service_suspended','.service-suspension-inputs')"
                            type="radio" id="service-suspended-disable" name="service_suspended" value="0"
                            @checked(! oldSetting('service_suspended', $section)) />
                        <label for="service-suspended-disable">{{ __('Disabled') }}</label>
                    </div>
                </div>
            </div>

            <div class="site-input-groups row service-suspension-inputs {{ oldSetting('service_suspended', $section) ? '' : 'd-none' }}">
                <div class="col-sm-4 col-label">{{ __('Suspension Message') }}</div>
                <div class="col-sm-8">
                    <textarea name="service_suspension_message" maxlength="500"
                        class="form-textarea @error('service_suspension_message') has-error @enderror">{{ oldSetting('service_suspension_message', $section) }}</textarea>
                    @error('service_suspension_message')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            @include('backend.setting.site_setting.include.form.__close_action')
        </div>
    </div>
</div>
