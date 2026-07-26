@extends('backend.setting.index')

@section('setting-title')
    {{ __('App Settings') }}
@endsection

@section('setting-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12">
                <div class="site-card">
                    <div class="site-card-header">
                        <h3 class="title">{{ __('Onboarding Splash Screens') }}</h3>
                    </div>

                    <div class="site-card-body">
                        <form action="{{ route('admin.page.setting.update') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div>
                                <h6>{{ __('Screen One') }}</h6>

                                <div class="site-input-groups row">
                                    <div class="col-xl-3 col-label">{{ __('Image') }}</div>
                                    <div class="col-xl-9">
                                        <div class="wrap-custom-file">
                                            <input type="file" name="app_splash_one_image" id="app_splash_one_image"
                                                accept=".gif,.jpg,.png,.jpeg" />
                                            <label for="app_splash_one_image" class="file-ok"
                                                style="background-image: url({{ asset(getPageSetting('app_splash_one_image')) }})">
                                                <img class="upload-icon" src="{{ asset('global/materials/upload.svg') }}"
                                                    alt="" />
                                                <span>{{ __('Update Image') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="site-input-groups row mt-2">
                                    <label for="app_splash_one_title" class="col-xl-3 col-label">{{ __('Title') }}</label>
                                    <div class="col-xl-9">
                                        <div class="input-group joint-input">
                                            <input type="text" class="form-control" id="app_splash_one_title"
                                                name="app_splash_one_title"
                                                value="{{ getPageSetting('app_splash_one_title') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="site-input-groups row">
                                    <label for="app_splash_one_description"
                                        class="col-xl-3 col-label">{{ __('Description') }}</label>
                                    <div class="col-xl-9">
                                        <div class="input-group joint-input">
                                            <textarea class="form-control" id="app_splash_one_description" name="app_splash_one_description" rows="2">{{ getPageSetting('app_splash_one_description') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div>
                                <h6>{{ __('Screen Two') }}</h6>

                                <div class="site-input-groups row">
                                    <div class="col-xl-3 col-label">{{ __('Image') }}</div>
                                    <div class="col-xl-9">
                                        <div class="wrap-custom-file">
                                            <input type="file" name="app_splash_two_image" id="app_splash_two_image"
                                                accept=".gif,.jpg,.png,.jpeg" />
                                            <label for="app_splash_two_image" class="file-ok"
                                                style="background-image: url({{ asset(getPageSetting('app_splash_two_image')) }})">
                                                <img class="upload-icon" src="{{ asset('global/materials/upload.svg') }}"
                                                    alt="" />
                                                <span>{{ __('Update Image') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="site-input-groups row mt-2">
                                    <label for="app_splash_two_title"
                                        class="col-xl-3 col-label">{{ __('Title') }}</label>
                                    <div class="col-xl-9">
                                        <div class="input-group joint-input">
                                            <input type="text" class="form-control" id="app_splash_two_title"
                                                name="app_splash_two_title"
                                                value="{{ getPageSetting('app_splash_two_title') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="site-input-groups row">
                                    <label for="app_splash_two_description"
                                        class="col-xl-3 col-label">{{ __('Description') }}</label>
                                    <div class="col-xl-9">
                                        <div class="input-group joint-input">
                                            <textarea class="form-control" id="app_splash_two_description" name="app_splash_two_description" rows="2">{{ getPageSetting('app_splash_two_description') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div>
                                <h6>{{ __('Screen Three') }}</h6>

                                <div class="site-input-groups row">
                                    <div class="col-xl-3 col-label">{{ __('Image') }}</div>
                                    <div class="col-xl-9">
                                        <div class="wrap-custom-file">
                                            <input type="file" name="app_splash_three_image" id="app_splash_three_image"
                                                accept=".gif,.jpg,.png,.jpeg" />
                                            <label for="app_splash_three_image" class="file-ok"
                                                style="background-image: url({{ asset(getPageSetting('app_splash_three_image')) }})">
                                                <img class="upload-icon" src="{{ asset('global/materials/upload.svg') }}"
                                                    alt="" />
                                                <span>{{ __('Update Image') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="site-input-groups row mt-2">
                                    <label for="app_splash_three_title"
                                        class="col-xl-3 col-label">{{ __('Title') }}</label>
                                    <div class="col-xl-9">
                                        <div class="input-group joint-input">
                                            <input type="text" class="form-control" id="app_splash_three_title"
                                                name="app_splash_three_title"
                                                value="{{ getPageSetting('app_splash_three_title') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="site-input-groups row">
                                    <label for="app_splash_three_description"
                                        class="col-xl-3 col-label">{{ __('Description') }}</label>
                                    <div class="col-xl-9">
                                        <div class="input-group joint-input">
                                            <textarea class="form-control" id="app_splash_three_description" name="app_splash_three_description" rows="2">{{ getPageSetting('app_splash_three_description') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="offset-sm-3 col-sm-9">
                                    <button type="submit" class="site-btn-sm primary-btn">
                                        {{ __('Save Changes') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
