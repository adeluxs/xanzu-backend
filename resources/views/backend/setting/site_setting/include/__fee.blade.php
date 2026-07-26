<div class="">
    <div class="site-card">
        <div class="site-card-header">
            <h3 class="title">{{ __($fields['title']) }}</h3>
        </div>
        <div class="site-card-body">
            @include('backend.setting.site_setting.include.form.__open_action')

            <div class="site-input-groups row mb-0">
                <div class="col-sm-4 col-label">{{ __('User Bonus') }}</div>
                <div class="col-sm-8">
                    <div class="form-row">
                        <div class="col-xl-6 col-sm-12 col-12">
                            <div class="site-input-groups">
                                <label for="" class="box-input-label">{{ __('Referral Bonus:') }}</label>
                                <div class="input-group joint-input">
                                    <input type="text" class="form-control" name="referral_bonus"
                                        value="{{ oldSetting('referral_bonus', 'fee') }}">
                                    <span class="input-group-text">{{ setting('site_currency', 'global') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-sm-12 col-12">
                            <div class="site-input-groups">
                                <label for="" class="box-input-label">{{ __('Signup Bonus:') }}</label>
                                <div class="input-group joint-input">
                                    <input type="text" class="form-control" name="signup_bonus"
                                        value="{{ oldSetting('signup_bonus', 'fee') }}">
                                    <span class="input-group-text">{{ setting('site_currency', 'global') }}</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="site-input-groups row" id="referral_level_free_user">
                <label for="" class="col-sm-4 col-label">{{ __('Referral Level for Buyer') }}</label>
                <div class="col-sm-8">
                    <div class="input-group joint-input">
                        <input type="text" class="form-control" name="referral_level_free_user"
                            value="{{ setting('referral_level_free_user', 'fee') }}">
                        <span class="input-group-text">{{ __('Levels') }}</span>
                    </div>
                </div>
            </div>
            <div class="site-input-groups row">
                <label class="col-sm-4 col-label">{{ __('Shipping Charge') }}</label>
                <div class="col-sm-8">
                    <div class="site-input-groups position-relative mb-0">
                        <div class="position-relative">
                            <input type="number" name="shipping_charge" class="box-input"
                                value="{{ setting('shipping_charge', 'fee') }}">
                            <div class="prcntcurr prcntcurr-large">
                                <select name="shipping_charge_type" class="form-select">
                                    <option value="fixed" @selected(setting('shipping_charge_type', 'fee') == 'fixed')>{{ $currencySymbol }}</option>
                                    <option value="percentage" @selected(setting('shipping_charge_type', 'fee') == 'percentage')>{{ __('%') }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('backend.setting.site_setting.include.form.__close_action')
        </div>
    </div>
</div>
