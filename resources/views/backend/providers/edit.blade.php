@extends('backend.layouts.app')
@section('title', 'Edit Provider')
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Edit Provider') }}</h2>
                            <a href="{{ route('admin.provider.index') }}" class="title-btn"><i
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
                            <form action="{{ route('admin.provider.update', $provider->id) }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-xxl-12">
                                        <div class="site-input-groups">
                                            <label for="name" class="box-input-label">{{ __('Name') }} <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="name" class="box-input mb-0"
                                                value="{{ old('name', $provider->name) }}" required />
                                        </div>
                                    </div>
                                    <div class="col-xxl-6">
                                        <div class="site-input-groups">
                                            <label class="box-input-label" for="">{{ __('Status') }} <span
                                                    class="text-danger">*</span></label>
                                            <div class="switch-field same-type">
                                                <input type="radio" id="radio-active" name="status" value="1"
                                                    @checked(old('status', $provider->status) == 1) required />
                                                <label for="radio-active">{{ __('Active') }}</label>
                                                <input type="radio" id="radio-inactive" name="status" value="0"
                                                    @checked(old('status', $provider->status) == 0) required />
                                                <label for="radio-inactive">{{ __('Inactive') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xxl-6">
                                        <div class="site-input-groups">
                                            <label for="website_url" class="box-input-label">{{ __('Website URL') }}</label>
                                            <input type="url" name="website_url" class="box-input mb-0"
                                                value="{{ old('website_url', $provider->website_url) }}"
                                                placeholder="{{ __('https://www.example.com') }}" />
                                        </div>
                                    </div>
                                    <div class="col-xxl-6">
                                        <div class="site-input-groups">
                                            <label for="platform" class="box-input-label">{{ __('Platform') }}</label>
                                            <select name="platform" class="form-control form-select">
                                                <option value="">{{ __('Select Platform') }}</option>
                                                @foreach (\App\Enums\ProviderPlatform::cases() as $platform)
                                                    <option value="{{ $platform->value }}" @selected(old('platform', $provider->platform ?? \App\Enums\ProviderPlatform::WORDPRESS_WOOCOMMERCE->value) === $platform->value)>
                                                        {{ ucwords(str_replace(['-', '_'], ' ', $platform->value)) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xxl-6">
                                        <div class="site-input-groups">
                                            <label for="platform_host"
                                                class="box-input-label">{{ __('Platform Host') }}</label>
                                            <input type="text" name="platform_host" class="box-input mb-0"
                                                value="{{ old('platform_host', $provider->platform_host) }}"
                                                placeholder="{{ __('https://your-store.com') }}" />
                                        </div>
                                    </div>
                                    <div class="col-xxl-6">
                                        <div class="site-input-groups">
                                            <label for="api_key" class="box-input-label">{{ __('API Key') }}</label>
                                            <input type="text" name="api_key" class="box-input mb-0"
                                                value="{{ old('api_key', $provider->api_key) }}" />
                                        </div>
                                    </div>
                                    <div class="col-xxl-6">
                                        <div class="site-input-groups">
                                            <label for="api_secret" class="box-input-label">{{ __('API Secret') }}</label>
                                            <input type="text" name="api_secret" class="box-input mb-0"
                                                value="{{ old('api_secret', $provider->api_secret) }}" />
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 mt-3">
                                        <div class="site-input-groups">
                                            <label for="image" class="box-input-label">{{ __('Image') }}</label>
                                            <div class="wrap-custom-file">
                                                <input type="file" name="image" id="image"
                                                    accept=".jpeg, .jpg, .png">
                                                <label for="image" class="file-ok"
                                                    style="background-image: url({{ asset($provider->image) }})">
                                                    <img class="upload-icon d-block"
                                                        src="{{ asset('assets/global/materials/upload.svg') }}"
                                                        alt="">
                                                    <span>{{ __('Upload Provider Logo') }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xxl-12 mt-3">
                                        <div class="site-input-groups">
                                            <label for="cover_image"
                                                class="box-input-label">{{ __('Cover Image') }}</label>
                                            <div class="wrap-custom-file">
                                                <input type="file" name="cover_image" id="cover_image"
                                                    accept=".jpeg, .jpg, .png">
                                                <label for="cover_image"
                                                    class="{{ $provider->cover_image ? 'file-ok' : '' }}"
                                                    style="{{ $provider->cover_image ? 'background-image: url(' . asset($provider->cover_image) . ')' : '' }}">
                                                    <img class="upload-icon {{ $provider->cover_image ? 'd-block' : '' }}"
                                                        src="{{ asset('assets/global/materials/upload.svg') }}"
                                                        alt="">
                                                    <span>{{ __('Upload Cover Image') }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xxl-12 mt-3">
                                        <div class="site-input-groups">
                                            <label for="description"
                                                class="box-input-label">{{ __('Description') }}</label>
                                            <textarea name="description" class="box-input mb-0" rows="4">{{ old('description', $provider->description) }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="action-btns mt-3">
                                    <button type="submit" class="site-btn-sm primary-btn me-2"><i
                                            data-lucide="check"></i>
                                        {{ __('Update Provider') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
