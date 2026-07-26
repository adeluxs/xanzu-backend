@extends('frontend::layouts.auth')
@section('title')
    {{ __('Forgot password') }}
@endsection
@section('content')
    <div class="auth-from-box">
        <div class="auth-from-contents">
            <h3>{{ $data['title'] }}</h3>
            <p class="description">{{ $data['description'] }}</p>
        </div>
        <form action="{{ route('password.email') }}" method="POST">
            @csrf
            <div class="row gy-20">
                <!-- Email -->
                <div class="col-lg-12">
                    <div class="td-form-group">
                        <label class="input-label" for="email">{{ __('Email address') }}<span>*</span></label>
                        <div class="input-field">
                            <input type="email" class="form-control" id="email" name="email"
                                value="{{ old('email') }}" required>
                        </div>
                        @error('email')
                            <p class="feedback-invalid active">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="auth-from-btns mt-30">
                <button type="submit" class="primary-button w-100">
                    <span class="btn-text">{{ __('Send Reset Link') }}</span>
                </button>
            </div>
        </form>
        <div class="auth-from-bottom-contents">
            <div class="have-auth-accounts">
                <p class="description">{{ __('Remembered your password?') }} <a class="td-underline-btn"
                        href="{{ route('login') }}">{{ __('Sign In') }}</a></p>
            </div>
        </div>
    </div>
@endsection
