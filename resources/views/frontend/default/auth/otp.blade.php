@extends('frontend::layouts.auth')
@section('title')
    {{ __('Verify OTP') }}
@endsection
@section('content')
    <!-- verification area start -->
    <style>
        input.control-form {
            width: 40px !important;
        }

        .text-white {
            color: white;
        }
    </style>
    <section class="verification-area">
        <div class="auth-wrapper">
            <div class="contents-inner">
                <div class="content">
                    <div class="top-content">
                        <x-luminous.logo />

                        <h3 class="title text-white">{{ __('OTP Verification') }}</h3>
                        <p class="description">{{ __('Enter your 4 digit OTP') }}</p>

                        @if (session('error'))
                            <div class="alert alert-danger">
                                <p>{{ session('error') }}</p>
                            </div>
                        @endif
                        @if (session('success'))
                            <div class="alert alert-success">
                                <p>{{ __('Enter OTP code sent to') }} <strong>{{ auth()->user()->phone }}</strong></p>
                            </div>
                        @endif
                    </div>
                    <form action="{{ route('otp.verify.post') }}" method="POST">
                        @csrf
                        <input type="hidden" name="phone" value="{{ auth()->user()->phone }}">
                        <div class="inputs">
                            <div class="verification my-3">
                                <input type="tel" name="otp[]" maxlength="1" pattern="[0-9]" class="control-form">
                                <input type="tel" name="otp[]" maxlength="1" pattern="[0-9]" class="control-form">
                                <input type="tel" name="otp[]" maxlength="1" pattern="[0-9]" class="control-form">
                                <input type="tel" name="otp[]" maxlength="1" pattern="[0-9]" class="control-form">
                            </div>
                        </div>
                        <div class="inputs">
                            <button type="submit"
                                class="primary-button primary-button-blue">{{ __('Verify & Proceed') }}</button>
                        </div>
                    </form>
                    <div class="bottom-content">
                        <p class="description mt-1">
                        <p>{{ __('Don\'t receive code ?') }} <a
                                href="{{ route('otp.resend') }}">{{ __('Resend again') }}</a></p>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- verification area end -->
@endsection
@push('js')
    <script>
        (function($) {
            'use strict';

            // password hide show
            const form = document.querySelector('form')
            const inputs = form.querySelectorAll('input')
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
