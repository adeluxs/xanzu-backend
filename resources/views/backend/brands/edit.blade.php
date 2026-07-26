@extends('backend.layouts.app')
@section('title', 'Edit Brand')
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Edit Brand') }}</h2>
                            <a href="{{ route('admin.brand.index') }}" class="title-btn"><i
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
                            <form action="{{ route('admin.brand.update', $brand->id) }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-xxl-12">
                                        <div class="site-input-groups">
                                            <label for="name" class="box-input-label">{{ __('Name') }} <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="name" class="box-input mb-0"
                                                value="{{ old('name', $brand->name) }}" required />
                                        </div>
                                    </div>
                                    <div class="col-xxl-6">
                                        <div class="site-input-groups">
                                            <label class="box-input-label" for="">{{ __('Status') }} <span
                                                    class="text-danger">*</span></label>
                                            <div class="switch-field same-type">
                                                <input type="radio" id="radio-active" name="status" value="1"
                                                    @checked(old('status', $brand->status) == 1) required />
                                                <label for="radio-active">{{ __('Active') }}</label>
                                                <input type="radio" id="radio-inactive" name="status" value="0"
                                                    @checked(old('status', $brand->status) == 0) required />
                                                <label for="radio-inactive">{{ __('Inactive') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xxl-6">
                                        <div class="site-input-groups">
                                            <label class="box-input-label" for="">{{ __('Is Popular') }} <span
                                                    class="text-danger">*</span></label>
                                            <div class="switch-field same-type">
                                                <input type="radio" id="radio-popular-active" name="is_popular"
                                                    value="1" @checked(old('is_popular', $brand->is_popular) == 1) required />
                                                <label for="radio-popular-active">{{ __('Yes') }}</label>
                                                <input type="radio" id="radio-popular-inactive" name="is_popular"
                                                    value="0" @checked(old('is_popular', $brand->is_popular) == 0) required />
                                                <label for="radio-popular-inactive">{{ __('No') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 mt-3">
                                        <div class="site-input-groups">
                                            <label for="image" class="box-input-label">{{ __('Image') }}</label>
                                            <div class="wrap-custom-file">
                                                <input type="file" name="image" id="image"
                                                    accept=".jpeg, .jpg, .png">
                                                <label for="image" class="file-ok"
                                                    style="background-image: url({{ asset($brand->image) }})">
                                                    <img class="upload-icon d-block"
                                                        src="{{ asset('assets/global/materials/upload.svg') }}"
                                                        alt="">
                                                    <span>{{ __('Upload Brand Image') }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xxl-12 mt-3">
                                        <div class="site-input-groups">
                                            <label for="description"
                                                class="box-input-label">{{ __('Description') }}</label>
                                            <textarea name="description" class="box-input mb-0" rows="4">{{ old('description', $brand->description) }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="action-btns mt-3">
                                    <button type="submit" class="site-btn-sm primary-btn me-2"><i data-lucide="check"></i>
                                        {{ __('Update Brand') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
