@extends('backend.layouts.app')
@section('title')
    {{ __('Add Seller Subscription') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-8">
                        <div class="title-content">
                            <h2 class="title">{{ __('Add Seller Subscription') }}</h2>
                            <a href="{{ route('admin.subscription.plan.index') }}" class="title-btn"><i
                                    data-lucide="arrow-left"></i>{{ __('Back') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-8">
                    <div class="site-card">
                        <div class="site-card-body">
                            <form action="{{ route('admin.subscription.plan.store') }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-xxl-4">
                                        <div class="site-input-groups row">
                                            <div class="col-sm-12 col-label">
                                                {{ __('Plan Logo Image') }}
                                            </div>
                                            <div class="col-xl-9 col-lg-9 col-md-9 col-sm-12">
                                                <div class="wrap-custom-file">
                                                    <input type="file" name="image" id="planImage"
                                                        accept=".gif, .jpg, .png" />
                                                    <label for="planImage" class="file-ok" style="background-image: url()">
                                                        <img class="upload-icon"
                                                            src="{{ asset('global/materials/upload.svg') }}"
                                                            alt="" />
                                                        <span>{{ __('Update Image') }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-xxl-4">
                                        <div class="site-input-groups">
                                            <label for="" class="box-input-label">{{ __('Name') }}</label>
                                            <input type="text" name="name" class="box-input mb-0"
                                                value="{{ old('name') }}" required />
                                        </div>
                                    </div>
                                    <div class="col-xxl-4">
                                        <div class="site-input-groups">
                                            <label for="" class="box-input-label">{{ __('Listing Limit') }}</label>
                                            <div class="input-group joint-input">
                                                <input type="number" name="listing_limit" class="form-control"
                                                    value="{{ old('listing_limit') }}" required />
                                                <span class="input-group-text">{{ __('Items') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4">
                                        <div class="site-input-groups">
                                            <label class="box-input-label" for="">{{ __('Price') }}</label>
                                            <div class="input-group joint-input">
                                                <input type="number" name="price" class="form-control"
                                                    value="{{ old('price') }}" />
                                                <span class="input-group-text">{{ $currency }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4">
                                        <div class="site-input-groups">
                                            <label class="box-input-label"
                                                for="">{{ __('Withdraw Limit') }}</label>
                                            <div class="input-group joint-input">
                                                <input type="number" name="withdraw_limit" class="form-control"
                                                    value="{{ old('withdraw_limit') }}" />
                                                <span class="input-group-text">{{ $currency }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4">
                                        <div class="site-input-groups">
                                            <label for="charge_type" class="box-input-label">{{ __('Commission:') }} <span
                                                    class="text-danger"></span></label>
                                            <div class="position-relative">
                                                <input type="text" class="box-input mb-0"
                                                    oninput="this.value = validateDouble(this.value)" name="charge_value"
                                                    value="0" required="">
                                                <div class="prcntcurr">
                                                    <select name="charge_type" class="form-select">
                                                        <option value="percentage">%</option>
                                                        <option value="amount" selected="">$</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4">
                                        <div class="site-input-groups">
                                            <label class="box-input-label" for="">{{ __('Validity') }}</label>
                                            <div class="input-group joint-input">
                                                <input type="number" name="validity" class="form-control"
                                                    value="{{ old('validity') }}" />
                                                <span class="input-group-text">{{ __('Days') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4">
                                        <div class="site-input-groups">
                                            <label class="box-input-label" for="">{{ __('Flash Sale Limit') }} <i
                                                    data-lucide="info" data-bs-toggle="tooltip"
                                                    data-bs-original-title="{{ __('Enter 0 if you don\'t need flash sale limit') }}"></i></label>
                                            <div class="input-group joint-input">
                                                <input type="number" name="flash_sale_limit" class="form-control"
                                                    value="{{ old('flash_sale_limit', 0) }}" />
                                                <span class="input-group-text">{{ __('Items') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4">
                                        <div class="site-input-groups">
                                            <label class="box-input-label"
                                                for="">{{ __('Referral Commission') }}</label>
                                            <select name="referral_level" class="form-select">
                                                <option selected disabled>{{ __('Select Commission Level') }}</option>
                                                <option value="0">{{ __('No Referral Commission') }}</option>
                                                @foreach ($levels as $level)
                                                    <option value="{{ $level->the_order }}" @selected(old('referral_level') == $level->the_order)>
                                                        {{ __('Upto :level Level', ['level' => $level->the_order]) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xl-4">
                                        <div class="site-input-groups">
                                            <label class="box-input-label" for="">{{ __('Featured') }}</label>
                                            <div class="switch-field same-type">
                                                <input type="radio" id="radio-five" name="featured" value="1"
                                                    @checked(old('featured') == 1) />
                                                <label for="radio-five">{{ __('Yes') }}</label>
                                                <input type="radio" id="radio-six" name="featured" value="0"
                                                    @checked(old('featured', 0) == 0) />
                                                <label for="radio-six">{{ __('No') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4" id="badgeArea">
                                        <div class="site-input-groups">
                                            <label for="" class="box-input-label">{{ __('Badge') }}</label>
                                            <input type="text" name="badge" class="box-input mb-0"
                                                value="{{ old('badge') }}" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-xxl-4">
                                        <a href="javascript:void(0)" id="generate"
                                            class="site-btn-xs primary-btn mb-3">{{ __('Add Field option') }}</a>
                                    </div>
                                    <div class="addOptions">
                                        @if (old('features'))
                                            @foreach (old('features') as $features)
                                                <div class="mb-4">
                                                    <div class="option-remove-row row">
                                                        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-12">
                                                            <div class="site-input-groups">
                                                                <input name="features[]" class="box-input" type="text"
                                                                    value="{{ $features }}" required
                                                                    placeholder="{{ __('Enter your feature') }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-xl-1 col-lg-6 col-md-6 col-sm-6 col-12">
                                                            <button class="delete-option-row delete_desc" type="button">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="mb-4">
                                                <div class="option-remove-row row">
                                                    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-12">
                                                        <div class="site-input-groups">
                                                            <input name="features[]" class="box-input" type="text"
                                                                value="" required placeholder="{{ __('Enter your feature') }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-xl-1 col-lg-6 col-md-6 col-sm-6 col-12">
                                                        <button class="delete-option-row delete_desc" type="button">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="action-btns">
                                    <button type="submit" class="site-btn-sm primary-btn me-2">
                                        <i data-lucide="check"></i>
                                        {{ __('Add Plan') }}
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
        (function($) {
            "use strict";

            toggleBadgeVisibility();

            function toggleBadgeVisibility() {
                var featured = $('input[name="featured"]:checked').val();
                if (featured === '1') {
                    $('#badgeArea').show();
                } else {
                    $('#badgeArea').hide();
                }
            }

            $('input[name="featured"]').on('change', function() {
                toggleBadgeVisibility();
            });

            $("#generate").on('click', function() {
                var form = `<div class="mb-4">
                          <div class="option-remove-row row">
                            <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-12">
                              <div class="site-input-groups">
                                <input name="features[]" class="box-input" type="text" value="" required placeholder="{{ __('Enter your feature') }}">
                              </div>
                            </div>
                            <div class="col-xl-1 col-lg-6 col-md-6 col-sm-6 col-12">
                              <button class="delete-option-row delete_desc" type="button">
                                <i class="fas fa-times"></i>
                              </button>
                            </div>
                            </div>
                          </div>`;
                $('.addOptions').append(form)
            })

            $(document).on('click', '.delete_desc', function() {
                $(this).closest('.option-remove-row').parent().remove();
            });

        })(jQuery);

        function validateDouble(value) {
            return value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');
        }
    </script>
@endsection
