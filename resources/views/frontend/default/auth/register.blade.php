{{-- filepath: e:\laragon\www\xanzu\resources\views\frontend\accxone\auth\register.blade.php --}}
@extends('frontend::layouts.auth')

@section('title')
    {{ __('Register') }}
@endsection

@push('css')
    <link rel="stylesheet" href="{{ themeAsset('css/flatpickr.min.css', 'default') }}">
    <link rel="stylesheet" href="{{ themeAsset('css/flat-picker-color-select.css', 'default') }}">
@endpush


@section('content')
    <!-- Sign up area start -->
    <div class="auth-login-switcher">
        <nav>
            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                <button class="nav-link active" id="buyer-auth-tab" data-bs-toggle="tab" data-bs-target="#buyer-auth"
                    type="button" role="tab" aria-controls="buyer-auth"
                    aria-selected="true">{{ __('Buyer') }}</button>
                <button class="nav-link" id="seller-auth-tab" data-bs-toggle="tab" data-bs-target="#seller-auth"
                    type="button" role="tab" aria-controls="seller-auth"
                    aria-selected="false">{{ __('Seller') }}</button>
            </div>
        </nav>
        <div class="tab-content" id="nav-tabContent">
            {{-- Buyer Tab --}}
            <div class="tab-pane fade show active" id="buyer-auth" role="tabpanel" aria-labelledby="buyer-auth-tab"
                tabindex="0">
                <div class="auth-from-box">
                    <div class="auth-from-contents">
                        <h3>{{ $data['title'] }}</h3>
                        <p class="description">{{ __('Create your account to get started') }}</p>
                    </div>
                    <form action="{{ route('register.now') }}" method="POST" enctype="multipart/form-data" id="buyerForm">
                        @csrf
                        <input type="hidden" name="user_type" value="buyer">
                        <div class="row gy-20">
                            @if (getPageSetting('first_name_show'))
                                <div class="col-md-6">
                                    <div class="td-form-group">
                                        <label class="input-label"
                                            for="buyer-f-name">{{ __('First Name') }}<span>{{ getPageSetting('first_name_validation') ? '*' : '' }}</span></label>
                                        <div class="input-field">
                                            <input type="text" class="form-control" id="buyer-f-name" name="first_name"
                                                value="{{ old('first_name') }}" @required(getPageSetting('first_name_validation'))>
                                        </div>
                                        @error('first_name')
                                            <p class="feedback-invalid active">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            @if (getPageSetting('last_name_show'))
                                <div class="col-md-6">
                                    <div class="td-form-group">
                                        <label class="input-label"
                                            for="buyer-l-name">{{ __('Last Name') }}<span>{{ getPageSetting('last_name_validation') ? '*' : '' }}</span></label>
                                        <div class="input-field">
                                            <input type="text" class="form-control" id="buyer-l-name" name="last_name"
                                                value="{{ old('last_name') }}" @required(getPageSetting('last_name_validation'))>
                                        </div>
                                        @error('last_name')
                                            <p class="feedback-invalid active">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-6">
                                <div class="td-form-group">
                                    <label class="input-label"
                                        for="buyer-email">{{ __('Email Address') }}<span>*</span></label>
                                    <div class="input-field">
                                        <input type="email" class="form-control" id="buyer-email" name="email"
                                            value="{{ old('email') }}" required>
                                    </div>
                                    @error('email')
                                        <p class="feedback-invalid active">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            @if (getPageSetting('phone_show'))
                                <div class="col-md-6">
                                    <div class="td-form-group">
                                        <label class="input-label"
                                            for="buyer-phone">{{ __('Phone Number') }}<span>{{ getPageSetting('phone_validation') ? '*' : '' }}</span></label>
                                        <div class="input-field">
                                            <input type="text" class="form-control" id="buyer-phone" name="phone"
                                                value="{{ old('phone') }}" @required(getPageSetting('phone_validation'))>
                                        </div>
                                        @error('phone')
                                            <p class="feedback-invalid active">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-6">
                                <div class="td-form-group">
                                    <label class="input-label"
                                        for="buyer-username">{{ __('Username') }}<span>{{ getPageSetting('username_validation') ? '*' : '' }}</span></label>
                                    <div class="input-field">
                                        <input type="text" class="form-control" id="buyer-username" name="username"
                                            value="{{ old('username') }}" @required(getPageSetting('username_validation'))>
                                    </div>
                                    @error('username')
                                        <p class="feedback-invalid active">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            @if (getPageSetting('country_show'))
                                <div class="col-md-6">
                                    <div class="td-form-group">
                                        <label class="input-label"
                                            for="buyer-country">{{ __('Select Country') }}<span>{{ getPageSetting('country_validation') ? '*' : '' }}</span></label>
                                        <div class="select-input">
                                            <select class="form-select select2Icons form-control" id="buyer-country"
                                                name="country" @required(getPageSetting('country_validation'))>
                                                <option value="">{{ __('Select Country') }}</option>
                                                @foreach (getCountries() as $country)
                                                    <option @selected($location->country_code == $country['code'])
                                                        value="{{ $country['name'] . ':' . $country['dial_code'] }}"
                                                        data-dial_code="{{ $country['dial_code'] }}">
                                                        {{ $country['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('country')
                                            <p class="feedback-invalid active">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            @if (getPageSetting('gender_show'))
                                <div class="col-lg-6">
                                    <div class="td-form-group">
                                        <label class="input-label"
                                            for="buyer-gender">{{ __('Gender') }}<span>{{ getPageSetting('gender_validation') ? '*' : '' }}</span></label>
                                        <div class="select-input">
                                            <select class="defaultselect2" id="buyer-gender" name="gender"
                                                @required(getPageSetting('gender_validation'))>
                                                <option value="">{{ __('Select gender') }}</option>
                                                <option value="male" @selected(old('gender') == 'male')>{{ __('Male') }}
                                                </option>
                                                <option value="female" @selected(old('gender') == 'female')>{{ __('Female') }}
                                                </option>
                                                <option value="other" @selected(old('gender') == 'other')>{{ __('Other') }}
                                                </option>
                                            </select>
                                        </div>
                                        @error('gender')
                                            <p class="feedback-invalid active">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            @if (getPageSetting('referral_code_show'))
                                <div class="col-md-6">
                                    <div class="td-form-group">
                                        <label class="input-label"
                                            for="buyer-referral-code">{{ __('Referral Code') }}</label>
                                        <div class="input-field">
                                            <input type="text" class="form-control" id="buyer-referral-code"
                                                name="invite"
                                                value="{{ old('invite', $referralCode) ?? $referralCode }}">
                                        </div>
                                        @error('invite')
                                            <p class="feedback-invalid active">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            <div class="col-lg-6 col-md-6">
                                <div class="td-form-group has-right-icon">
                                    <label class="input-label"
                                        for="buyer-password">{{ __('Password') }}<span>*</span></label>
                                    <div class="input-field">
                                        <input type="password" class="form-control password-input" id="buyer-password"
                                            name="password" required>
                                        <span class="input-icon eyeicon"
                                            data-eye-open="{{ themeAsset('images/icon/eye.svg') }}"
                                            data-eye-close="{{ themeAsset('images/icon/eye-open.svg') }}">
                                            <img class="eye-img" src="{{ themeAsset('images/icon/eye.svg') }}"
                                                alt="eye">
                                        </span>
                                    </div>
                                    @error('password')
                                        <p class="feedback-invalid active">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-6">
                                <div class="td-form-group has-right-icon">
                                    <label class="input-label"
                                        for="buyer-confirm-password">{{ __('Confirm Password') }}<span>*</span></label>
                                    <div class="input-field">
                                        <input type="password" class="form-control password-input"
                                            id="buyer-confirm-password" name="password_confirmation" required>
                                        <span class="input-icon eyeicon"
                                            data-eye-open="{{ themeAsset('images/icon/eye.svg') }}"
                                            data-eye-close="{{ themeAsset('images/icon/eye-open.svg') }}">
                                            <img class="eye-img" src="{{ themeAsset('images/icon/eye.svg') }}"
                                                alt="eye">
                                        </span>
                                    </div>
                                    @error('password_confirmation')
                                        <p class="feedback-invalid active">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="auth-login-option mt-16 mb-30">
                            <div class="animate-custom">
                                <input class="inp-cbx" id="buyer-terms" name="terms" type="checkbox"
                                    style="display: none;" required>
                                <label class="cbx" for="buyer-terms">
                                    <span>
                                        <svg width="12px" height="9px" viewBox="0 0 12 9">
                                            <polyline points="1 5 4 8 11 1"></polyline>
                                        </svg>
                                    </span>
                                    <span>{{ __('I agree to the') }} <a class="td-underline-btn"
                                            href="{{ url('terms-and-conditions') }}">{{ __('Terms & Conditions') }}</a>
                                        {{ __('and') }} <a class="td-underline-btn"
                                            href="{{ url('privacy-policy') }}">{{ __('Privacy Policy') }}</a></span>
                                </label>
                            </div>
                            @error('terms')
                                <p class="feedback-invalid active">{{ $message }}</p>
                            @enderror
                        </div>

                        @if ($googleReCaptcha)
                            <div class="g-recaptcha mb-3" id="feedback-recaptcha"
                                data-sitekey="{{ json_decode($googleReCaptcha->data, true)['site_key'] }}">
                            </div>
                        @endif

                        <div class="auth-from-btns mt-30">
                            <button class="primary-button w-100" type="submit">
                                <span class="btn-text">{{ __('Sign Up') }}</span>
                            </button>
                        </div>
                    </form>

                    @if (setting('facebook_social_login', 'permission') || setting('google_social_login', 'permission'))
                        <div class="auth-social-login d-flex flex-column gap-3 mt-20">
                            @if (setting('google_social_login', 'permission'))
                                <a href="{{ route('social.login', ['provider' => 'google', 'user_type' => 'buyer']) }}"
                                    class="primary-button gray-border btn-md w-100 d-flex align-items-center"
                                    role="button">
                                    <span class="btn-icon">
                                        <svg width="23" height="23" viewBox="0 0 23 23" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M22.5395 11.7617C22.5395 10.9462 22.4663 10.1622 22.3304 9.40942H11.4995V13.8578H17.6886C17.422 15.2953 16.6118 16.5133 15.3938 17.3287V20.2142H19.1104C21.285 18.2122 22.5395 15.264 22.5395 11.7617Z"
                                                fill="#4285F4"></path>
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M11.4995 23.0003C14.6045 23.0003 17.2077 21.9705 19.1104 20.2142L15.3938 17.3287C14.364 18.0187 13.0467 18.4264 11.4995 18.4264C8.50425 18.4264 5.96902 16.4035 5.0647 13.6853H1.22266V16.6648C3.11493 20.4233 7.00402 23.0003 11.4995 23.0003Z"
                                                fill="#34A853"></path>
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M5.06523 13.6852C4.83523 12.9952 4.70455 12.2582 4.70455 11.5002C4.70455 10.7423 4.83523 10.0052 5.06523 9.31524V6.33569H1.22318C0.444318 7.88819 0 9.64456 0 11.5002C0 13.3559 0.444318 15.1123 1.22318 16.6648L5.06523 13.6852Z"
                                                fill="#FBBC05"></path>
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M11.4995 4.57386C13.1879 4.57386 14.7038 5.15409 15.8956 6.29364L19.194 2.99523C17.2024 1.13955 14.5992 0 11.4995 0C7.00402 0 3.11493 2.57705 1.22266 6.33545L5.0647 9.315C5.96902 6.59682 8.50425 4.57386 11.4995 4.57386Z"
                                                fill="#EA4335"></path>
                                        </svg>
                                    </span>
                                    <span class="btn-text">{{ __('Continue with Google') }}</span>
                                </a>
                            @endif
                            @if (setting('facebook_social_login', 'permission'))
                                <a href="{{ route('social.login', ['provider' => 'facebook', 'user_type' => 'buyer']) }}"
                                    class="primary-button gray-border btn-md w-100 d-flex align-items-center"
                                    role="button">
                                    <span class="btn-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <g clip-path="url(#clip0_2137_450)">
                                                <path
                                                    d="M24 12C24 17.9897 19.6116 22.9542 13.875 23.8542V15.4688H16.6711L17.2031 12H13.875V9.74906C13.875 8.79984 14.34 7.875 15.8306 7.875H17.3438V4.92188C17.3438 4.92188 15.9703 4.6875 14.6573 4.6875C11.9166 4.6875 10.125 6.34875 10.125 9.35625V12H7.07812V15.4688H10.125V23.8542C4.38844 22.9542 0 17.9897 0 12C0 5.37281 5.37281 0 12 0C18.6272 0 24 5.37281 24 12Z"
                                                    fill="#1877F2"></path>
                                                <path
                                                    d="M16.6711 15.4688L17.2031 12H13.875V9.74902C13.875 8.80003 14.3399 7.875 15.8306 7.875H17.3438V4.92188C17.3438 4.92188 15.9705 4.6875 14.6576 4.6875C11.9165 4.6875 10.125 6.34875 10.125 9.35625V12H7.07812V15.4688H10.125V23.8542C10.736 23.95 11.3621 24 12 24C12.6379 24 13.264 23.95 13.875 23.8542V15.4688H16.6711Z"
                                                    fill="white"></path>
                                            </g>
                                            <defs>
                                                <clipPath id="clip0_2137_450">
                                                    <rect width="24" height="24" fill="white"></rect>
                                                </clipPath>
                                            </defs>
                                        </svg>
                                    </span>
                                    <span class="btn-text">{{ __('Continue with Facebook') }}</span>
                                </a>
                            @endif
                        </div>
                    @endif

                    <div class="have-auth-accounts">
                        <p class="description">{{ __('Already have an account?') }} <a class="td-underline-btn btn-sm"
                                href="{{ route('login') }}">{{ __('Sign In') }}</a></p>
                    </div>
                </div>
            </div>

            {{-- Seller Tab --}}
            <div class="tab-pane fade" id="seller-auth" role="tabpanel" aria-labelledby="seller-auth-tab"
                tabindex="0">
                <div class="auth-from-box">
                    <div class="auth-from-contents">
                        <h3>{{ $data['title'] }}</h3>
                        <p class="description">{{ __('Create your account to get started') }}</p>
                    </div>
                    <form action="{{ route('register.now') }}" method="POST" enctype="multipart/form-data"
                        id="sellerForm">
                        @csrf
                        <input type="hidden" name="user_type" value="merchant">

                        <!-- Step 1 -->
                        <div class="seller-step step-1">
                            @if ($sellerKyc)
                                <div class="auth-step mb-25">
                                    <div class="divider-line"></div>
                                    <div class="step-title">{{ __('Step: 1/2') }}</div>
                                    <div class="divider-line"></div>
                                </div>
                            @endif

                            <div class="row g-20">
                                @if (getPageSetting('merchant_first_name_show'))
                                    <!-- First Name -->
                                    <div class="col-md-6">
                                        <div class="td-form-group">
                                            <label class="input-label"
                                                for="seller-f-name">{{ __('First Name') }}<span>{{ getPageSetting('merchant_first_name_validation') ? '*' : '' }}</span></label>
                                            <div class="input-field">
                                                <input type="text" class="form-control" id="seller-f-name"
                                                    name="first_name" value="{{ old('first_name') }}" @required(getPageSetting('merchant_first_name_validation'))>
                                            </div>
                                            @error('first_name')
                                                <p class="feedback-invalid active">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                @endif

                                @if (getPageSetting('merchant_last_name_show'))
                                    <!-- Last Name -->
                                    <div class="col-md-6">
                                        <div class="td-form-group">
                                            <label class="input-label"
                                                for="seller-l-name">{{ __('Last Name') }}<span>{{ getPageSetting('merchant_last_name_validation') ? '*' : '' }}</span></label>
                                            <div class="input-field">
                                                <input type="text" class="form-control" id="seller-l-name"
                                                    name="last_name" value="{{ old('last_name') }}" @required(getPageSetting('merchant_last_name_validation'))>
                                            </div>
                                            @error('last_name')
                                                <p class="feedback-invalid active">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                @endif

                                <!-- Email -->
                                <div class="col-md-6">
                                    <div class="td-form-group">
                                        <label class="input-label"
                                            for="seller-email">{{ __('Email') }}<span>*</span></label>
                                        <div class="input-field">
                                            <input type="email" class="form-control" id="seller-email" name="email"
                                                value="{{ old('email') }}" required>
                                        </div>
                                        @error('email')
                                            <p class="feedback-invalid active">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                @if (getPageSetting('merchant_phone_show'))
                                    <!-- Phone -->
                                    <div class="col-md-6">
                                        <div class="td-form-group">
                                            <label class="input-label"
                                                for="seller-phone">{{ __('Phone') }}<span>{{ getPageSetting('merchant_phone_validation') ? '*' : '' }}</span></label>
                                            <div class="input-field">
                                                <input type="text" class="form-control" id="seller-phone"
                                                    name="phone" value="{{ old('phone') }}" @required(getPageSetting('merchant_phone_validation'))>
                                            </div>
                                            @error('phone')
                                                <p class="feedback-invalid active">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                @endif

                                <!-- Username -->
                                <div class="col-md-6">
                                    <div class="td-form-group">
                                        <label class="input-label"
                                            for="seller-username">{{ __('Username') }}<span>*</span></label>
                                        <div class="input-field">
                                            <input type="text" class="form-control" id="seller-username"
                                                name="username" value="{{ old('username') }}" required>
                                        </div>
                                        @error('username')
                                            <p class="feedback-invalid active">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                @if (getPageSetting('merchant_country_show'))
                                    <!-- Country -->
                                    <div class="col-md-6">
                                        <div class="td-form-group">
                                            <label class="input-label"
                                                for="seller-country">{{ __('Country') }}<span>{{ getPageSetting('merchant_country_validation') ? '*' : '' }}</span></label>
                                            <div class="select-input">
                                                <select class="form-select select2Icons form-control" id="seller-country"
                                                    name="country" @required(getPageSetting('merchant_country_validation'))>
                                                    <option value="">{{ __('Select Country') }}</option>
                                                    @foreach (getCountries() as $country)
                                                        <option @selected($location->country_code == $country['code'])
                                                            value="{{ $country['name'] . ':' . $country['dial_code'] }}"
                                                            data-dial_code="{{ $country['dial_code'] }}">
                                                            {{ $country['name'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('country')
                                                <p class="feedback-invalid active">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                @endif

                                @if (getPageSetting('merchant_gender_show'))
                                    <!-- Gender -->
                                    <div class="col-md-6">
                                        <div class="td-form-group">
                                            <label class="input-label"
                                                for="seller-gender">{{ __('Gender') }}<span>{{ getPageSetting('merchant_gender_validation') ? '*' : '' }}</span></label>
                                            <div class="select-input">
                                                <select class="defaultselect2" id="seller-gender" name="gender"
                                                    @required(getPageSetting('merchant_gender_validation'))>
                                                    <option value="">{{ __('Select Gender') }}</option>
                                                    <option value="male" @selected(old('gender') == 'male')>
                                                        {{ __('Male') }}</option>
                                                    <option value="female" @selected(old('gender') == 'female')>
                                                        {{ __('Female') }}</option>
                                                    <option value="other" @selected(old('gender') == 'other')>
                                                        {{ __('Other') }}</option>
                                                </select>
                                            </div>
                                            @error('gender')
                                                <p class="feedback-invalid active">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                @endif

                                <!-- Password -->
                                <div class="col-lg-6 col-md-6">
                                    <div class="td-form-group has-right-icon">
                                        <label class="input-label"
                                            for="seller-password">{{ __('Password') }}<span>*</span></label>
                                        <div class="input-field">
                                            <input type="password" class="form-control password-input"
                                                id="seller-password" name="password" required>
                                            <span class="input-icon eyeicon"
                                                data-eye-close="{{ themeAsset('images/icon/eye-open.svg') }}">
                                                <img class="eye-img" src="{{ themeAsset('images/icon/eye.svg') }}"
                                                    alt="eye">
                                            </span>
                                        </div>
                                        @error('password')
                                            <p class="feedback-invalid active">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Confirm Password -->
                                <div class="col-lg-6 col-md-6">
                                    <div class="td-form-group has-right-icon">
                                        <label class="input-label"
                                            for="seller-confirm-password">{{ __('Confirm Password') }}<span>*</span></label>
                                        <div class="input-field">
                                            <input type="password" class="form-control password-input"
                                                id="seller-confirm-password" name="password_confirmation" required>
                                            <span class="input-icon eyeicon"
                                                data-eye-close="{{ themeAsset('images/icon/eye-open.svg') }}">
                                                <img class="eye-img" src="{{ themeAsset('images/icon/eye.svg') }}"
                                                    alt="eye">
                                            </span>
                                        </div>
                                        @error('password_confirmation')
                                            <p class="feedback-invalid active">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="auth-login-option mt-16 mb-30">
                                <div class="animate-custom">
                                    <input class="inp-cbx" id="seller-terms" name="terms" type="checkbox"
                                        style="display: none;" required>
                                    <label class="cbx" for="seller-terms">
                                        <span>
                                            <svg width="12px" height="9px" viewBox="0 0 12 9">
                                                <polyline points="1 5 4 8 11 1"></polyline>
                                            </svg>
                                        </span>
                                        <span>{{ __('I agree to the') }} <a class="td-underline-btn"
                                                href="{{ url('terms-and-conditions') }}">{{ __('Terms & Conditions') }}</a>
                                            {{ __('and') }} <a class="td-underline-btn"
                                                href="{{ url('privacy-policy') }}">{{ __('Privacy Policy') }}</a></span>
                                    </label>
                                </div>
                                @error('terms')
                                    <p class="feedback-invalid active">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="auth-from-btns mt-30">
                                @if ($sellerKyc)
                                    <button class="primary-button w-100 step-next" type="button">
                                        <span class="btn-text">{{ __('Next') }}</span>
                                    </button>
                                @else
                                    @if ($googleReCaptcha)
                                        <div class="g-recaptcha mb-3" id="seller-recaptcha"
                                            data-sitekey="{{ json_decode($googleReCaptcha->data, true)['site_key'] }}">
                                        </div>
                                    @endif
                                    <button class="primary-button w-100" type="submit">
                                        <span class="btn-text">{{ __('Sign Up') }}</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if ($sellerKyc)
                            <!-- Step 2 - KYC -->
                            <div class="seller-step step-2 d-none">
                                <div class="auth-step mb-25">
                                    <div class="divider-line"></div>
                                    <div class="step-title">{{ __('Step: 2/2') }}</div>
                                    <div class="divider-line"></div>
                                </div>

                                <div class="row g-20">
                                    @includeWhen($sellerKyc, 'frontend::auth.seller-kyc', [
                                        'kyc' => $sellerKyc,
                                    ])
                                </div>

                                @if ($googleReCaptcha)
                                    <div class="g-recaptcha mb-3 mt-20" id="seller-recaptcha-step2"
                                        data-sitekey="{{ json_decode($googleReCaptcha->data, true)['site_key'] }}">
                                    </div>
                                @endif

                                <div class="auth-from-btns mt-30">
                                    <div class="row gx-3">
                                        <div class="col-lg-6">
                                            <button class="primary-button border-btn w-100 step-prev" type="button">
                                                <span class="btn-text">{{ __('Back') }}</span>
                                            </button>
                                        </div>
                                        <div class="col-lg-6">
                                            <button class="primary-button w-100" type="submit">
                                                <span class="btn-text">{{ __('Submit') }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </form>

                    <div class="have-auth-accounts">
                        <p class="description">{{ __('Already have an account?') }} <a class="td-underline-btn btn-sm"
                                href="{{ route('login') }}">{{ __('Sign In') }}</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Sign up area end -->
@endsection

@push('js')
    <script src="{{ themeAsset('js/flatpickr.js', 'default') }}"></script>
    <script src="{{ themeAsset('js/flatpicker-activation.js', 'default') }}"></script>

    <script>
        $(document).ready(function() {
            // Next Button
            $(".step-next").on("click", function() {
                $(".step-1").addClass("d-none");
                $(".step-2").removeClass("d-none");
            });


            // Back Button
            $(".step-prev").on("click", function() {
                $(".step-2").addClass("d-none");
                $(".step-1").removeClass("d-none");
            });
        });
    </script>
@endpush
