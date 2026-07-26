@extends('frontend::layouts.auth')
@section('title')
    {{ __('2FA Security') }}
@endsection

@section('content')
    <!-- auth area start -->
    <div class="auth-from-box">
        <div class="auth-from-contents text-center">
            <h3>{{ __('OTP Verification') }}</h3>
            <p class="description">
                {!! __('Enter the 6-digit code generated on your Authenticator App.<br>Code refreshes every 30 seconds.') !!}
            </p>
        </div>
        <form id="otp-form" class="twoFAform" action="{{ route('home') }}" method="POST">
            @csrf
            <div class="td-form-group">
                <div class="otp-verification" id="twoStepsForm">
                    <input type="text" name="one_time_password[]" maxlength="1" class="control-form" pattern="[0-9]"
                        autocomplete="off" autofocus required>
                    <input type="text" name="one_time_password[]" maxlength="1" class="control-form" pattern="[0-9]"
                        autocomplete="off" required>
                    <input type="text" name="one_time_password[]" maxlength="1" class="control-form" pattern="[0-9]"
                        autocomplete="off" required>
                    <input type="text" name="one_time_password[]" maxlength="1" class="control-form" pattern="[0-9]"
                        autocomplete="off" required>
                    <input type="text" name="one_time_password[]" maxlength="1" class="control-form" pattern="[0-9]"
                        autocomplete="off" required>
                    <input type="text" name="one_time_password[]" maxlength="1" class="control-form" pattern="[0-9]"
                        autocomplete="off" required>
                </div>
                <input type="hidden" name="otp">
                @error('one_time_password')
                    <p class="feedback-invalid active text-center">{{ $message }}</p>
                @enderror
            </div>
            <div class="auth-from-btns mt-30">
                <button class="primary-button w-100" type="submit">
                    <span class="btn-text">{{ __('Verify') }}</span>
                </button>
            </div>
        </form>
        <div class="have-auth-accounts">
            <p class="description">{{ __('Need help?') }} <a class="td-underline-btn btn-sm"
                    href="{{ frontendUrl('contact') }}">{{ __('Contact Support') }}</a></p>
        </div>
    </div>
    <!-- auth area end -->
@endsection

@push('js')
    <script>
        (function($) {
            'use strict';

            // password hide show
            const form = document.querySelector('.twoFAform')
            const inputs = form.querySelectorAll('input')
            console.log(inputs, form)
            const KEYBOARDS = {
                backspace: 8,
                arrowLeft: 37,
                arrowRight: 39,
            }

            function handleInput(e) {
                const input = e.target
                const nextInput = input.nextElementSibling
                if (nextInput && input.value) {
                    nextInput.focus()
                    if (nextInput.value) {
                        nextInput.select()
                    }
                }
            }

            function handlePaste(e) {
                e.preventDefault()
                const paste = e.clipboardData.getData('text')
                inputs.forEach((input, i) => {
                    input.value = paste[i] || ''
                })
            }

            function handleBackspace(e) {
                const input = e.target
                if (input.value) {
                    input.value = ''
                    return
                }

                input.previousElementSibling.focus()
            }

            function handleArrowLeft(e) {
                const previousInput = e.target.previousElementSibling
                if (!previousInput) return
                previousInput.focus()
            }

            function handleArrowRight(e) {
                const nextInput = e.target.nextElementSibling
                if (!nextInput) return
                nextInput.focus()
            }

            form.addEventListener('input', handleInput)
            inputs[0].addEventListener('paste', handlePaste)

            inputs.forEach(input => {
                input.addEventListener('focus', e => {
                    setTimeout(() => {
                        e.target.select()
                    }, 0)
                })

                input.addEventListener('keydown', e => {
                    switch (e.keyCode) {
                        case KEYBOARDS.backspace:
                            handleBackspace(e)
                            break
                        case KEYBOARDS.arrowLeft:
                            handleArrowLeft(e)
                            break
                        case KEYBOARDS.arrowRight:
                            handleArrowRight(e)
                            break
                        default:
                    }
                })
            })

        })(jQuery);
    </script>
@endpush
