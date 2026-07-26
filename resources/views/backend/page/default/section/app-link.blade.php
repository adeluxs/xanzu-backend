@extends('backend.layouts.app')
@section('title')
    {{ __('App Link Section') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-xl-12">
                        <div class="title-content">
                            <h2 class="title">{{ __('App Link Section') }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="site-tab-bars">
                <ul class="nav nav-pills" id="pills-tab" role="tablist">
                    @foreach ($languages as $language)
                        <li class="nav-item" role="presentation">
                            <a href="" class="nav-link {{ $loop->first ? 'active' : '' }}"
                                id="pills-informations-tab" data-bs-toggle="pill"
                                data-bs-target="#{{ $language->locale }}" type="button" role="tab"
                                aria-controls="pills-informations" aria-selected="true"><i
                                    data-lucide="languages"></i>{{ $language->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="tab-content" id="pills-tabContent">
                @foreach ($groupData as $key => $value)
                    @php
                        $data = new Illuminate\Support\Fluent($value);
                    @endphp

                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $key }}"
                        role="tabpanel" aria-labelledby="pills-informations-tab">
                        <div class="site-card">
                            <div class="site-card-header">
                                <h3 class="title">{{ __('Content') }}</h3>
                            </div>
                            <div class="site-card-body">
                                <form action="{{ route('admin.page.section.section.update') }}" method="post"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="section_code" value="app-link">
                                    <input type="hidden" name="section_locale" value="{{ $key }}">

                                    @if ($key == 'en')
                                        <div class="site-input-groups row">
                                            <label for=""
                                                class="col-sm-3 col-label pt-0">{{ __('Section Visibility') }}<i
                                                    data-lucide="info" data-bs-toggle="tooltip"
                                                    data-bs-original-title="Manage Section Visibility"></i></label>
                                            <div class="col-sm-3">
                                                <div class="site-input-groups">
                                                    <div class="switch-field">
                                                        <input type="radio" id="app-active" name="status"
                                                            @checked($status) value="1" />
                                                        <label for="app-active">{{ __('Show') }}</label>
                                                        <input type="radio" id="app-deactivate" name="status"
                                                            @checked(!$status) value="0" />
                                                        <label for="app-deactivate">{{ __('Hide') }}</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="site-input-groups row">
                                        <label for="" class="col-sm-3 col-label">{{ __('Section Title') }}</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="title" class="box-input"
                                                value="{{ $data->title }}">
                                        </div>
                                    </div>

                                    <div class="site-input-groups row">
                                        <label for="" class="col-sm-3 col-label">{{ __('Description') }}</label>
                                        <div class="col-sm-9">
                                            <textarea name="description" class="form-textarea">{{ $data->description }}</textarea>
                                        </div>
                                    </div>

                                    @if ($key == 'en')
                                        <div class="site-input-groups row">
                                            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-label">
                                                {{ __('Background Image') }}
                                            </div>
                                            <div class="col-xl-9 col-lg-9 col-md-9 col-sm-12">
                                                <div class="wrap-custom-file">
                                                    <input type="file" name="background_image" id="appLinkBackgroundImage"
                                                        accept=".gif, .jpg, .png, .webp" />
                                                    <label for="appLinkBackgroundImage" id="background_image"
                                                        @if ($data['background_image'] ?? false) class="file-ok"
                                                        style="background-image: url({{ asset($data['background_image']) }})" @endif>
                                                        <img class="upload-icon"
                                                            src="{{ asset('global/materials/upload.svg') }}"
                                                            alt="" />
                                                        <span>{{ __('Update Image') }}</span>
                                                    </label>
                                                    @removeimg($data['background_image'] ?? null, background_image)
                                                </div>
                                            </div>
                                        </div>

                                        <div class="site-input-groups row">
                                            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-label">
                                                {{ __('Phone Image') }}
                                            </div>
                                            <div class="col-xl-9 col-lg-9 col-md-9 col-sm-12">
                                                <div class="wrap-custom-file">
                                                    <input type="file" name="right_image" id="appLinkPhoneImage"
                                                        accept=".gif, .jpg, .png, .webp" />
                                                    <label for="appLinkPhoneImage" id="right_image"
                                                        @if ($data['right_image'] ?? false) class="file-ok"
                                                        style="background-image: url({{ asset($data['right_image']) }})" @endif>
                                                        <img class="upload-icon"
                                                            src="{{ asset('global/materials/upload.svg') }}"
                                                            alt="" />
                                                        <span>{{ __('Update Image') }}</span>
                                                    </label>
                                                    @removeimg($data['right_image'] ?? null, right_image)
                                                </div>
                                            </div>
                                        </div>

                                        <div class="site-input-groups row">
                                            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-label">
                                                {{ __('App Store Icon') }}
                                            </div>
                                            <div class="col-xl-9 col-lg-9 col-md-9 col-sm-12">
                                                <div class="wrap-custom-file">
                                                    <input type="file" name="app_store_icon" id="appStoreIcon"
                                                        accept=".gif, .jpg, .png, .webp" />
                                                    <label for="appStoreIcon" id="app_store_icon"
                                                        @if ($data['app_store_icon'] ?? false) class="file-ok"
                                                        style="background-image: url({{ asset($data['app_store_icon']) }})" @endif>
                                                        <img class="upload-icon"
                                                            src="{{ asset('global/materials/upload.svg') }}"
                                                            alt="" />
                                                        <span>{{ __('Update Image') }}</span>
                                                    </label>
                                                    @removeimg($data['app_store_icon'] ?? null, app_store_icon)
                                                </div>
                                            </div>
                                        </div>

                                        <div class="site-input-groups row">
                                            <label for="" class="col-sm-3 col-label">{{ __('App Store URL') }}</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="app_store_url" class="box-input"
                                                    value="{{ $data->app_store_url }}">
                                            </div>
                                        </div>

                                        <div class="site-input-groups row">
                                            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-label">
                                                {{ __('Play Store Icon') }}
                                            </div>
                                            <div class="col-xl-9 col-lg-9 col-md-9 col-sm-12">
                                                <div class="wrap-custom-file">
                                                    <input type="file" name="play_store_icon" id="playStoreIcon"
                                                        accept=".gif, .jpg, .png, .webp" />
                                                    <label for="playStoreIcon" id="play_store_icon"
                                                        @if ($data['play_store_icon'] ?? false) class="file-ok"
                                                        style="background-image: url({{ asset($data['play_store_icon']) }})" @endif>
                                                        <img class="upload-icon"
                                                            src="{{ asset('global/materials/upload.svg') }}"
                                                            alt="" />
                                                        <span>{{ __('Update Image') }}</span>
                                                    </label>
                                                    @removeimg($data['play_store_icon'] ?? null, play_store_icon)
                                                </div>
                                            </div>
                                        </div>

                                        <div class="site-input-groups row">
                                            <label for="" class="col-sm-3 col-label">{{ __('Play Store URL') }}</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="play_store_url" class="box-input"
                                                    value="{{ $data->play_store_url }}">
                                            </div>
                                        </div>
                                    @endif

                                    <div class="row">
                                        <div class="offset-sm-3 col-sm-9">
                                            <button type="submit"
                                                class="site-btn-sm primary-btn w-100">{{ __('Save Changes') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
@section('script')
    @include('backend.page.section.include.__section_image_remove')
@endsection
