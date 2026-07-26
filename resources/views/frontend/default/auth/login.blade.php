@extends('frontend::layouts.auth', ['bodyClass' => 'login-page'])

@section('title')
    {{ __('Login') }}
@endsection
@push('css')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ themeAsset('css/style.css') }}">
    <title>{{ __('Login') . ' - ' . setting('site_title') }}</title>
    <link rel="icon" type="{{ mime_content_type(base_path('assets/' . setting('site_favicon'))) }}"
        href="{{ asset(setting('site_favicon')) }}">
@endpush
@section('content')
    <main class="login-card">
        <h1>{{ __('Log in') }}</h1>

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="email">{{ __('Email') }}</label>
                <div class="form-control">
                    <input class="form-control-input" id="email" name="email" type="email"
                        placeholder="{{ __('Enter Email') }}" value="{{ old('email') }}" required>
                </div>
                @error('email')
                    <p class="feedback-invalid d-block mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">{{ __('Password') }}</label>
                <div class="form-control form-control-with-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round"
                        stroke-linejoin="round" aria-hidden="true">
                        <rect x="5" y="11" width="14" height="9" rx="2"></rect>
                        <path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                    </svg>
                    <input class="form-control-input" id="password" name="password" type="password"
                        placeholder="{{ __('Enter password') }}" required>
                    <button class="toggle-password" type="button" aria-label="{{ __('Show password') }}"
                        aria-pressed="false" id="xanzuTogglePassword">
                        <svg class="eye-icon eye-open" viewBox="0 0 24 24" fill="none" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                            <circle cx="12" cy="12" r="2.5"></circle>
                            <path d="M4 20 20 4"></path>
                        </svg>
                        <svg class="eye-icon eye-closed" viewBox="0 0 24 24" fill="none" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                            <circle cx="12" cy="12" r="2.5"></circle>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="feedback-invalid d-block mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-meta">
                <label class="remember-row" for="auth_remind">
                    <input id="auth_remind" name="remember" type="checkbox" {{ old('remember') ? 'checked' : '' }}>
                    <span>{{ __('Remember me') }}</span>
                </label>
            </div>

            @if ($googleReCaptcha)
                <div class="g-recaptcha mb-3" id="feedback-recaptcha"
                    data-sitekey="{{ json_decode($googleReCaptcha->data, true)['site_key'] }}">
                </div>
            @endif

            <button class="submit-btn" type="submit">{{ __('Sign In') }}</button>
        </form>
    </main>
@endsection
@section('script')
    @if ($googleReCaptcha)
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
    <script>
        (function() {
            const passwordInput = document.getElementById('password');
            const togglePasswordButton = document.getElementById('xanzuTogglePassword');

            if (!passwordInput || !togglePasswordButton) {
                return;
            }

            togglePasswordButton.addEventListener('click', function() {
                const shouldShow = passwordInput.type === 'password';
                passwordInput.type = shouldShow ? 'text' : 'password';
                this.classList.toggle('is-visible', shouldShow);
                this.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
                this.setAttribute('aria-label', shouldShow ? '{{ __('Hide password') }}' :
                    '{{ __('Show password') }}');
            });
        })();
    </script>
@endsection
