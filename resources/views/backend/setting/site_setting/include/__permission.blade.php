<div class="">
    <div class="site-card">
        <div class="site-card-header">
            <h3 class="title">{{ __('Permission Settings') }}</h3>
        </div>
        <div class="site-card-body">
            @include('backend.setting.site_setting.include.form.__open_action')

            @foreach ($fields['elements'] as $key => $field)
                @if ($field['name'] == 'default_mode')
                    <div class="site-input-groups row">
                        <div class="col-sm-4 col-label pt-0">{{ __($field['label']) }}</div>

                        <div class="col-sm-8">
                            <div class="form-switch ps-0">
                                <input class="form-check-input" type="hidden" value="dark"
                                    name="{{ $field['name'] }}" />
                                <div class="switch-field same-type m-0">
                                    <input type="radio" id="active-{{ $key }}" name="{{ $field['name'] }}"
                                        value="light" @if (oldSetting($field['name'], $section) == 'light') checked @endif />
                                    <label for="active-{{ $key }}">{{ __('Light') }}</label>
                                    <input type="radio" id="disable-{{ $key }}" name="{{ $field['name'] }}"
                                        value="dark" @if (oldSetting($field['name'], $section) == 'dark') checked @endif />
                                    <label for="disable-{{ $key }}">{{ __('Dark') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif($field['name'] == 'language_switcher')
                    <div class="site-input-groups row">
                        <div class="col-sm-4 col-label pt-0">{{ __($field['label']) }}</div>

                        <div class="col-sm-8">
                            <div class="form-switch ps-0">
                                <input class="form-check-input" type="hidden" value="0"
                                    name="{{ $field['name'] }}" />
                                <div class="switch-field same-type m-0">
                                    <input type="radio" id="active-{{ $key }}" name="{{ $field['name'] }}"
                                        value="1" @if (oldSetting($field['name'], $section)) checked @endif />
                                    <label for="active-{{ $key }}">{{ __('Show') }}</label>

                                    <input type="radio" id="disable-{{ $key }}" name="{{ $field['name'] }}"
                                        value="0" @if (!oldSetting($field['name'], $section)) checked @endif />
                                    <label for="disable-{{ $key }}">{{ __('Hide') }}</label>

                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="site-input-groups row">
                        <div class="col-sm-4 col-label pt-0">{{ __($field['label']) }}

                            @if ($field['name'] == 'coupon_approval')
                                <i data-lucide="info" data-bs-toggle="tooltip" title=""
                                    data-bs-original-title="If coupon Approval is enabled, then admin need to approve coupon before it can be used."></i>
                            @elseif($field['name'] == 'sitemap_enable')
                                <i data-lucide="info" data-bs-toggle="tooltip" title=""
                                    data-bs-original-title="If sitemap is enabled, then sitemap will be generated automatically."></i>
                            @endif

                        </div>

                        <div class="col-sm-8">
                            <div class="form-switch ps-0">
                                <input class="form-check-input" type="hidden" value="0"
                                    name="{{ $field['name'] }}" />
                                <div class="switch-field same-type m-0">
                                    <input type="radio" id="active-{{ $key }}" name="{{ $field['name'] }}"
                                        value="1" @if (oldSetting($field['name'], $section)) checked @endif />
                                    <label for="active-{{ $key }}">{{ __('Enable') }}</label>
                                    <input type="radio" id="disable-{{ $key }}" name="{{ $field['name'] }}"
                                        value="0" @if (!oldSetting($field['name'], $section)) checked @endif />
                                    <label for="disable-{{ $key }}">{{ __('Disabled') }}</label>
                                </div>
                            </div>
                            <div class="w-100">
                                @if ($field['name'] == 'sitemap_enable')
                                    <code>{{ __('Sitemap URL is :url', ['url' => frontendUrl('sitemap.xml')]) }}</code>
                                @endif

                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
            @include('backend.setting.site_setting.include.form.__close_action')
        </div>
    </div>
</div>
