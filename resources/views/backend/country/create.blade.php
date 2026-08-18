@extends('backend.layouts.app')
@section('title')
    {{ __('Add New Country') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-xl-8">
                        <div class="title-content">
                            <h2 class="title">{{ __('Add New Country') }}</h2>
                            <a href="{{ route('admin.country.index') }}" class="title-btn"><i
                                    data-lucide="corner-down-left"></i>{{ __('Back') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-xl-8">
                    <div class="site-card">
                        <div class="site-card-body">
                            <form action="{{ route('admin.country.store') }}" method="post" class="row"
                                enctype="multipart/form-data">
                                @csrf

                                <div class="col-xl-12">
                                    <div class="site-input-groups">
                                        <label class="box-input-label" for="">{{ __('Flag:') }}</label>
                                        <div class="wrap-custom-file">
                                            <input type="file" name="image" id="image"
                                                accept=".gif, .jpg, .png, .webp" />
                                            <label for="image">
                                                <img class="upload-icon" src="{{ asset('global/materials/upload.svg') }}"
                                                    alt="" />
                                                <span>{{ __('Upload') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-6">
                                    <div class="site-input-groups">
                                        <label class="box-input-label" for="">{{ __('Country:') }}</label>
                                        <select name="name" id="country" class="form-control form-select">
                                            <option value="" selected>{{ __('Select Country') }}</option>
                                            @foreach (getCountries() as $country)
                                                <option value="{{ $country['name'] }}"
                                                    data-country-code="{{ $country['code'] }}"
                                                    data-dial-code="{{ $country['dial_code'] }}"
                                                    @selected(old('name') == $country['name'])>
                                                    {{ $country['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-xl-6">
                                    <div class="site-input-groups">
                                        <label class="box-input-label" for="country-code">{{ __('Country Code:') }}</label>
                                        <input type="text" class="box-input" id="country-code"
                                            value="{{ old('code') }}" readonly />
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="site-input-groups">
                                        <label class="box-input-label" for="currency-code">{{ __('Currency Code:') }}</label>
                                        <input type="text" class="box-input text-uppercase" id="currency-code" name="currency_code"
                                            value="{{ old('currency_code') }}" maxlength="10" placeholder="{{ __('e.g. NGN, USD, GBP') }}" required />
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="site-input-groups">
                                        <label class="box-input-label" for="dial-code">{{ __('Dial Code:') }}</label>
                                        <input type="text" class="box-input" id="dial-code"
                                            value="{{ old('dial_code') }}" readonly />
                                    </div>
                                </div>

                                <div class="col-xl-6 col-lg-4 col-md-6 col-sm-6">
                                    <div class="site-input-groups">
                                        <label class="box-input-label" for="">{{ __('Conversion Rate:') }}</label>
                                        <div class="input-group joint-input">
                                            <span class="input-group-text">
                                                1 {{ $currency }} =
                                            </span>
                                            <input type="text" name="own_rate" data-validate="decimal"
                                                class="form-control" value="{{ old('own_rate') }}">
                                            <span class="input-group-text" id="target-currency">
                                                {{ old('currency_code') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-6 mt-3">
                                    <div class="site-input-groups">
                                        <label class="box-input-label" for="">{{ __('Status:') }}</label>
                                        <div class="switch-field">
                                            <input type="radio" id="status-active" name="status" value="1"
                                                @checked(old('status', 1) == 1) />
                                            <label for="status-active">{{ __('Active') }}</label>
                                            <input type="radio" id="status-inactive" name="status" value="0"
                                                @checked(old('status') === '0') />
                                            <label for="status-inactive">{{ __('Inactive') }}</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-12">
                                    <button type="submit" class="site-btn primary-btn w-100">
                                        {{ __('Save Changes') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(document).ready(function(e) {
            "use strict";

            $('#country').on('change', function() {
                var countryCode = $(this).find(':selected').data('country-code');
                var dialCode = $(this).find(':selected').data('dial-code');
                $('#country-code').val(countryCode || '');
                $('#dial-code').val(dialCode || '');
            });
            $('#country').trigger('change');

            $('#currency-code').on('input', function() {
                var currencyCode = ($(this).val() || '').toUpperCase();
                $(this).val(currencyCode);
                $('#target-currency').text(currencyCode);
            });
        });
    </script>
@endsection
