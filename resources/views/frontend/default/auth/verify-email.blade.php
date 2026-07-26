@extends('frontend::layouts.auth')
@section('title')
    {{ __('Verify Email') }}
@endsection
@section('content')
    <div class="auth-from-box">
        <div class="auth-from-contents">
            <h3>{{ __('Verify Email') }}</h3>
            <p>{{ __('Enter your registered email address, and we\'ll send you a link to verify your email.') }}</p>
        </div>
        <form action="{{ route('verification.send') }}" method="POST">
            @csrf
            <div class="auth-from-btns mt-30">
                <button type="submit" class="primary-button w-100">
                    <span class="btn-text">{{ __('Resend Link') }}</span>
                </button>
            </div>
        </form>

        <div class="auth-from-bottom-contents">
            <div class="have-auth-accounts">
                <p class="description">{{ __('Want to logout?') }} <a class="td-underline-btn"
                        href="{{ route('logout') }}">{{ __('Logout') }}</a></p>
            </div>
        </div>
    </div>
@endsection
