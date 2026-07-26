@extends('backend.layouts.app')
@section('title')
    {{ __('Hero Section') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-xl-12">
                        <div class="title-content">
                            <h2 class="title">{{ __('Hero Section') }}</h2>
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
                            <a href="" class="nav-link {{ $loop->first ? 'active' : '' }}" id="pills-informations-tab"
                                data-bs-toggle="pill" data-bs-target="#{{ $language->locale }}" type="button"
                                role="tab" aria-controls="pills-informations" aria-selected="true"><i
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
                                <h3 class="title">{{ __('Contents') }}</h3>
                            </div>
                            <div class="site-card-body">
                                <form action="{{ route('admin.page.section.section.update') }}" method="post"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="section_code" value="hero">
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
                                                        <input type="radio" id="hero-active" name="status"
                                                            @checked($status) value="1" />
                                                        <label for="hero-active">{{ __('Show') }}</label>
                                                        <input type="radio" id="hero-deactivate" name="status"
                                                            @checked(!$status) value="0" />
                                                        <label for="hero-deactivate">{{ __('Hide') }}</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="site-input-groups row">
                                        <label for="" class="col-sm-3 col-label">{{ __('Hero Title') }}</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="hero_title" class="box-input"
                                                value="{{ $data->hero_title }}">
                                        </div>
                                    </div>

                                    <div class="site-input-groups row">
                                        <label for="" class="col-sm-3 col-label">{{ __('Hero Description') }}</label>
                                        <div class="col-sm-9">
                                            <textarea name="hero_description" class="form-textarea">{{ $data->hero_description }}</textarea>
                                        </div>
                                    </div>

                                    <div class="site-input-groups row">
                                        <label for="" class="col-sm-3 col-label">{{ __('QR Text') }}</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="qr_text" class="box-input"
                                                value="{{ $data->qr_text }}">
                                        </div>
                                    </div>

                                    @if ($key == 'en')
                                        <div class="site-input-groups row">
                                            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-label">
                                                {{ __('Background Image') }} <small>(Hero background)</small>
                                            </div>
                                            <div class="col-xl-9 col-lg-9 col-md-9 col-sm-12">
                                                <div class="wrap-custom-file">
                                                    <input type="file" name="background_image" id="heroBackgroundImage"
                                                        accept=".gif, .jpg, .png, .webp" />
                                                    <label for="heroBackgroundImage" id="background_image"
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
                                                {{ __('Hero Image') }}
                                            </div>
                                            <div class="col-xl-9 col-lg-9 col-md-9 col-sm-12">
                                                <div class="wrap-custom-file">
                                                    <input type="file" name="hero_image" id="heroImage"
                                                        accept=".gif, .jpg, .png, .webp" />
                                                    <label for="heroImage" id="hero_image"
                                                        @if ($data['hero_image'] ?? false) class="file-ok"
                                                        style="background-image: url({{ asset($data['hero_image']) }})" @endif>
                                                        <img class="upload-icon"
                                                            src="{{ asset('global/materials/upload.svg') }}"
                                                            alt="" />
                                                        <span>{{ __('Update Image') }}</span>
                                                    </label>
                                                    @removeimg($data['hero_image'] ?? null, hero_image)
                                                </div>
                                            </div>
                                        </div>

                                        <div class="site-input-groups row">
                                            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-label">
                                                {{ __('QR Image') }}
                                            </div>
                                            <div class="col-xl-9 col-lg-9 col-md-9 col-sm-12">
                                                <div class="wrap-custom-file">
                                                    <input type="file" name="qr_image" id="qrImage"
                                                        accept=".gif, .jpg, .png, .webp" />
                                                    <label for="qrImage" id="qr_image"
                                                        @if ($data['qr_image'] ?? false) class="file-ok"
                                                        style="background-image: url({{ asset($data['qr_image']) }})" @endif>
                                                        <img class="upload-icon"
                                                            src="{{ asset('global/materials/upload.svg') }}"
                                                            alt="" />
                                                        <span>{{ __('Update Image') }}</span>
                                                    </label>
                                                    @removeimg($data['qr_image'] ?? null, qr_image)
                                                </div>
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
