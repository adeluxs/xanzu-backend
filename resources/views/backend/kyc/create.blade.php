@extends('backend.layouts.app')
@section('title')
    {{ __('Add New Verification') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-xl-8">
                        <div class="title-content">
                            <h2 class="title">{{ __('Add New Verification Form') }}</h2>
                            <a href="{{ route('admin.kyc-form.index') }}" class="title-btn"><i
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
                            <form action="{{ route('admin.kyc-form.store') }}" method="post" class="row"
                                enctype="multipart/form-data">
                                @csrf

                                <div class="col-xl-9">
                                    <div class="site-input-groups">
                                        <label class="box-input-label" for="">{{ __('Name:') }}</label>
                                        <input type="text" name="name" value="{{ old('name') }}" class="box-input"
                                            placeholder="Account Verification Type Name" required />
                                    </div>
                                </div>
                                {{-- for user --}}
                                <div class="col-xl-3">
                                    <div class="site-input-groups">
                                        <label class="box-input-label" for="">{{ __('User Type') }}</label>
                                        <select name="user_type" class="form-select" id="">
                                            <option value="buyer">{{ __('Buyer') }}</option>
                                            <option value="merchant">{{ __('Merchant') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="site-input-groups">
                                        <label class="box-input-label" for="">{{ __('Icon:') }}</label>
                                        <div class="wrap-custom-file">
                                            <input type="file" name="icon" id="kyc-icon" accept=".jpeg, .jpg, .png">
                                            <label for="kyc-icon">
                                                <img class="upload-icon"
                                                    src="{{ asset('global/materials/upload.svg') }}" alt="">
                                                <span>{{ __('Upload Icon') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 text-end">
                                    <a href="javascript:void(0)" id="generate"
                                        class="site-btn-xs primary-btn mb-3">{{ __('Add Field option') }}</a>
                                </div>

                                <div class="addOptions"></div>

                                <div class="col-xl-12">
                                    <div class="row">
                                        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                                            <div class="site-input-groups">
                                                <label class="box-input-label" for="">{{ __('Status:') }}</label>
                                                <div class="switch-field">
                                                    <input type="radio" id="active-status" name="status" checked=""
                                                        value="1" />
                                                    <label for="active-status">{{ __('Active') }}</label>
                                                    <input type="radio" id="deactivate-status" name="status"
                                                        value="0" />
                                                    <label for="deactivate-status">{{ __('Deactivate') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6">
                                            <div class="site-input-groups">
                                                <label class="box-input-label"
                                                    for="">{{ __('Required For Account Verification Verified User') }}
                                                    <i data-lucide="info" data-bs-toggle="tooltip" title=""
                                                        data-bs-original-title="Danger Action, All kyc verified users will be unverified and it must be filled this kyc."></i>
                                                </label>
                                                <div class="switch-field">
                                                    <input type="radio" id="active-yes" name="unverified_confirmation"
                                                        value="1" />
                                                    <label for="active-yes">{{ __('Yes') }}</label>
                                                    <input type="radio" id="deactivate-confirmation"
                                                        name="unverified_confirmation" checked="" value="0" />
                                                    <label for="deactivate-confirmation">{{ __('No') }}</label>
                                                </div>
                                            </div>
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
            var i = 0;
            "use strict";

            $("#generate").on('click', function() {
                ++i;
                var form = `<div class="mb-4">
                                    <div class="option-remove-row row g-3">
                                        <div class="col-12 col-lg-6">
                                            <div class="site-input-groups">
                                                <label class="box-input-label">{{ __('Field Name (key):') }}</label>
                                                <input name="fields[` + i + `][name]" class="box-input" type="text" value="" required placeholder="Field Name">
                                            </div>
                                        </div>

                                        <div class="col-12 col-lg-6">
                                            <div class="site-input-groups">
                                                <label class="box-input-label">{{ __('Title (shown to users):') }}</label>
                                                <input name="fields[` + i + `][title]" class="box-input" type="text" value="" placeholder="{{ __('Field Title') }}">
                                            </div>
                                        </div>

                                        <div class="col-12 col-lg-6">
                                            <div class="site-input-groups">
                                                <label class="box-input-label">{{ __('Description:') }}</label>
                                                <textarea name="fields[` + i + `][description]" class="form-textarea" rows="2" placeholder="{{ __('Helper text for users') }}"></textarea>
                                            </div>
                                        </div>

                                        <div class="col-12 col-lg-6">
                                            <div class="site-input-groups">
                                                <label class="box-input-label">{{ __('Instruction Image:') }}</label>
                                                <div class="wrap-custom-file">
                                                    <input type="file" name="fields[` + i +
                    `][instruction_image]" id="instruction-image-` + i + `" accept=".jpeg, .jpg, .png">
                                                    <label for="instruction-image-` + i + `">
                                                        <img class="upload-icon" src="{{ asset('assets/global/materials/upload.svg') }}" alt="">
                                                        <span>{{ __('Upload Image') }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-lg-6">
                                            <div class="site-input-groups">
                                                <label class="box-input-label">{{ __('Type:') }}</label>
                                                <select name="fields[` + i + `][type]" class="form-select form-select-lg mb-3">
                                                        <option value="text">{{ __('Input Text') }}</option>
                                                        <option value="textarea">{{ __('Textarea') }}</option>
                                                        <option value="file">{{ __('File upload') }}</option>
                                                        <option value="date">{{ __('Calendar') }}</option>
                                                        <option value="camera">{{ __('Camera') }}</option>
                                                        <option value="select">{{ __('Select') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-6">
                                            <div class="site-input-groups">
                                                <label class="box-input-label">{{ __('Validation:') }}</label>
                                                <select name="fields[` + i + `][validation]" class="form-select form-select-lg mb-3">
                                                        <option value="required">{{ __('Required') }}</option>
                                                        <option value="nullable">{{ __('Optional') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-12 d-flex align-items-end justify-content-end">
                                            <button class="delete-option-row delete_desc" type="button">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        </div>
                                    </div>`;
                $('.addOptions').append(form)
                imagePreview();
            });

            $(document).on('click', '.delete_desc', function() {
                $(this).closest('.option-remove-row').parent().remove();
            });
        });
    </script>
@endsection
