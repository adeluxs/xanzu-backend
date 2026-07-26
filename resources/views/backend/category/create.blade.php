@extends('backend.layouts.app')
@section('title')
    {{ __('Add Category') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-xl-7">
                        <div class="title-content">
                            <h2 class="title">{{ __('Add Category') }}</h2>
                            <a href="{{ route('admin.category.index') }}" class="title-btn"><i
                                    data-lucide="arrow-left"></i>{{ __('Back') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-7">
                    <div class="site-card">
                        <div class="site-card-body">
                            <form action="{{ route('admin.category.store') }}" method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="site-input-groups">
                                            <label for="" class="box-input-label">{{ __('Image') }} <span
                                                    class="text-danger">*</span></label>
                                            <div class="wrap-custom-file">
                                                <input type="file" name="image" id="image" accept=".gif, .jpg, .png">
                                                <label for="image">
                                                  <img class="upload-icon" src="{{ asset('global/materials/upload.svg') }}" alt="">
                                                  <span>{{ __('Select Category') }}</span>
                                                </label>
                                              </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-xxl-12">
                                        <div class="site-input-groups">
                                            <label for="" class="box-input-label">{{ __('Name') }} <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="name" class="box-input mb-0" value="{{ old('name') }}"
                                                required />
                                        </div>
                                    </div>

                                    <div class="col-xxl-6">
                                        <div class="site-input-groups">
                                            <label class="box-input-label" for="">{{ __('Status') }} <span
                                                    class="text-danger">*</span></label>
                                            <div class="switch-field same-type">
                                                <input type="radio" id="radio-active" name="status" value="1"
                                                    @checked(old('status', 1) == 1) required />
                                                <label for="radio-active">{{ __('Active') }}</label>
                                                <input type="radio" id="radio-inactive" name="status" value="0"
                                                    @checked(old('status') == 0) required />
                                                <label for="radio-inactive">{{ __('Inactive') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xxl-6">
                                        <div class="site-input-groups">
                                            <label class="box-input-label" for="">{{ __('Is Trending Category') }} <span
                                                    class="text-danger">*</span></label>
                                            <div class="switch-field same-type">
                                                <input type="radio" id="is_trending-active" name="is_trending"
                                                    value="1" @checked(old('is_trending', $category?->is_trending ?? 1) == 1) required />
                                                <label for="is_trending-active">{{ __('Yes') }}</label>
                                                <input type="radio" id="is_trending-inactive" name="is_trending"
                                                    value="0" @checked(old('is_trending', $category?->is_trending ?? 0) == 0) required />
                                                <label for="is_trending-inactive">{{ __('No') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="action-btns">
                                    <button type="submit" class="site-btn-sm primary-btn me-2">
                                        <i data-lucide="check"></i>
                                        {{ __('Add Category') }}
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