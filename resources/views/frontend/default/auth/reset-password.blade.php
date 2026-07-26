@extends('frontend::layouts.auth')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    <!-- Reset Password start -->
    <div class="auth-content auth-content-2">
        <div class="auth-content-inside">
            <div class="logo-container">
                <div class="logo">
                    <x-luminous.logo />
                </div>
            </div>

            <div class="auth-header auth-header-2">
                <h3>{{ $data['title'] }}</h3>
                <p>{{ $data['description'] }}</p>
            </div>

            <div class="auth-forms">
                <form action="{{ route('password.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">
                    <input type="hidden" name="email" value="{{ old('email', $request->email) }}">

                    <div class="row gy-20">
                        <!-- Password Input -->
                        <div class="col-lg-12">
                            <div class="td-form-group has-right-icon">
                                <label class="input-label" for="password">{{ __('Password') }}<span>*</span></label>
                                <div class="input-field">
                                    <input type="password" class="form-control password-input" id="password"
                                        name="password" required>
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

                        <!-- Confirm Password Input -->
                        <div class="col-lg-12">
                            <div class="td-form-group has-right-icon">
                                <label class="input-label" for="password_confirmation">{{ __('Confirm Password') }}<span>*</span></label>
                                <div class="input-field">
                                    <input type="password" class="form-control password-input"
                                        id="password_confirmation" name="password_confirmation" required>
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

                    <div class="auth-from-btns mt-30">
                        <button type="submit" class="primary-button w-100">
                            <span class="btn-text">{{ __('Reset Password') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="auth-from-bottom-contents">
                <div class="have-auth-accounts">
                    <p class="description">{{ __('Don\'t have an account?') }} <a class="td-underline-btn"
                            href="{{ frontendUrl('register') }}">{{ __('Sign Up') }}</a></p>
                </div>
            </div>
        </div>
    </div>
    <!-- Reset Password end -->
@endsection
