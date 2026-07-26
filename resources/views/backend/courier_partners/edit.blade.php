@extends('backend.layouts.app')
@section('title', 'Edit Courier Partner')
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Edit Courier Partner') }}</h2>
                            <a href="{{ route('admin.courier.index') }}" class="title-btn"><i
                                    data-lucide="arrow-left"></i>Back</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-6">
                    <div class="site-card">
                        <div class="site-card-body">
                            <form action="{{ route('admin.courier.update', $courierPartner->id) }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-xxl-12">
                                        <div class="site-input-groups">
                                            <label for="name" class="box-input-label">{{ __('Name') }} <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="name" class="box-input mb-0"
                                                value="{{ old('name', $courierPartner->name) }}" required />
                                        </div>
                                    </div>
                                    <div class="col-xxl-6">
                                        <div class="site-input-groups">
                                            <label class="box-input-label" for="">{{ __('Status') }} <span
                                                    class="text-danger">*</span></label>
                                            <div class="switch-field same-type">
                                                <input type="radio" id="radio-active" name="status" value="1"
                                                    @checked(old('status', $courierPartner->status) == 1) required />
                                                <label for="radio-active">{{ __('Active') }}</label>
                                                <input type="radio" id="radio-inactive" name="status" value="0"
                                                    @checked(old('status', $courierPartner->status) == 0) required />
                                                <label for="radio-inactive">{{ __('Inactive') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xxl-6">
                                        <div class="site-input-groups">
                                            <label for="short_description"
                                                class="box-input-label">{{ __('Short Description') }}</label>
                                            <input type="text" name="short_description" class="box-input mb-0"
                                                value="{{ old('short_description', $courierPartner->short_description) }}"
                                                placeholder="{{ __('Short summary for admin listing') }}" />
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 mt-3">
                                        <div class="site-input-groups">
                                            <label for="logo" class="box-input-label">{{ __('Logo') }}</label>
                                            <div class="wrap-custom-file">
                                                <input type="file" name="logo" id="logo"
                                                    accept=".jpeg, .jpg, .png">
                                                <label for="logo" class="{{ $courierPartner->logo ? 'file-ok' : '' }}"
                                                    style="{{ $courierPartner->logo ? 'background-image: url(' . asset($courierPartner->logo) . ')' : '' }}">
                                                    <img class="upload-icon {{ $courierPartner->logo ? 'd-block' : '' }}"
                                                        src="{{ asset('assets/global/materials/upload.svg') }}"
                                                        alt="">
                                                    <span>{{ __('Upload Courier Logo') }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xxl-12 mt-3">
                                        <div class="site-input-groups">
                                            <label for="admin_note" class="box-input-label">{{ __('Admin Note') }}</label>
                                            <textarea name="admin_note" class="box-input mb-0" rows="4">{{ old('admin_note', $courierPartner->admin_note) }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="action-btns mt-3">
                                    <button type="submit" class="site-btn-sm primary-btn me-2"><i data-lucide="check"></i>
                                        {{ __('Update Courier Partner') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
