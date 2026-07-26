@extends('backend.layouts.app')
@section('title', 'Edit Banner')
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Edit Banner') }}</h2>
                            <a href="{{ route('admin.banner.index') }}" class="title-btn"><i
                                    data-lucide="arrow-left"></i>{{ __('Back') }}</a>
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
                            <form action="{{ route('admin.banner.update', $banner->id) }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-xxl-12">
                                        <div class="site-input-groups">
                                            <label for="name" class="box-input-label">{{ __('Name') }} <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="name" class="box-input mb-0"
                                                value="{{ old('name', $banner->name) }}" required />
                                        </div>
                                    </div>
                                    <div class="col-xxl-12">
                                        <div class="site-input-groups">
                                            <label for="description" class="box-input-label">{{ __('Description') }}</label>
                                            <textarea name="description" class="box-input mb-0" rows="4">{{ old('description', $banner->description) }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-xxl-12">
                                        <div class="site-input-groups">
                                            <label for="category_id" class="box-input-label">{{ __('Category') }}</label>
                                            <select name="category_id" class="form-select">
                                                <option value="">{{ __('None') }}</option>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}" @selected(old('category_id', $banner->category_id) == $category->id)>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xxl-12">
                                        <div class="site-input-groups">
                                            <label class="box-input-label">{{ __('Image:') }}</label>
                                            <div class="wrap-custom-file">
                                                <input type="file" name="image" id="image"
                                                    accept=".gif, .jpg, .png, .webp" />
                                                <label for="image" @class(['file-ok' => !empty($banner->image)])
                                                    @if (!empty($banner->image)) style="background-image: url({{ asset($banner->image) }})" @endif>
                                                    <img class="upload-icon" src="{{ asset('global/materials/upload.svg') }}"
                                                        alt="" />
                                                    <span>{{ __('Upload') }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="action-btns">
                                    <button type="submit" class="site-btn-sm primary-btn me-2">
                                        <i data-lucide="check"></i> {{ __('Update Banner') }}
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

