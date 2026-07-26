@extends('backend.layouts.app')
@section('title')
    {{ __('Edit Country') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-xl-8">
                        <div class="title-content">
                            <h2 class="title">{{ __('Edit Country') }}</h2>
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
                            <form action="{{ route('admin.country.update', $country->id) }}" method="post" class="row"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="col-xl-12">
                                    <div class="site-input-groups">
                                        <label class="box-input-label" for="">{{ __('Flag:') }}</label>
                                        <div class="wrap-custom-file">
                                            <input type="file" name="image" id="image"
                                                accept=".gif, .jpg, .png, .webp" />
                                            <label for="image" class="file-ok"
                                                style="background-image: url({{ asset($country->image) }})">
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
                                            @foreach (getCountries() as $countryTwo)
                                                <option value="{{ $countryTwo['name'] }}"
                                                    data-currency-code="{{ $countryTwo['code'] }}"
                                                    data-dial-code="{{ $countryTwo['dial_code'] }}"
                                                    @selected($country->name == $countryTwo['name'])>
                                                    {{ $countryTwo['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-xl-6">
                                    <div class="site-input-groups">
                                        <label class="box-input-label" for="">{{ __('Currency Code:') }}</label>
                                        <input type="text" class="box-input" name="currency_code"
                                            value="{{ $country->currency_code }}" required readonly />
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="site-input-groups">
                                        <label class="box-input-label" for="">{{ __('Dial Code:') }}</label>
                                        <input type="text" class="box-input" name="dial_code"
                                            value="{{ $country->dial_code }}" required readonly />
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
                                                class="form-control" value="{{ $country->own_rate }}">
                                            <span class="input-group-text" id="target-currency">
                                                {{ $country->currency_code }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-6 mt-3">
                                    <div class="site-input-groups">
                                        <label class="box-input-label" for="">{{ __('Status:') }}</label>
                                        <div class="switch-field">
                                            <input type="radio" id="status-active" name="status" value="1"
                                                @checked(old('status', $country->status) == 1) />
                                            <label for="status-active">{{ __('Active') }}</label>
                                            <input type="radio" id="status-inactive" name="status" value="0"
                                                @checked(old('status', $country->status) == 0) />
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
                var currencyCode = $(this).find(':selected').data('currency-code');
                var dialCode = $(this).find(':selected').data('dial-code');
                $('input[name="currency_code"]').val(currencyCode);
                $('input[name="dial_code"]').val(dialCode);

                $('#target-currency').text(currencyCode);
            });
        });
    </script>
@endsection
