@extends('backend.layouts.app')
@section('title')
    {{ __('About Us Section') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-xl-12">
                        <div class="title-content">
                            <h2 class="title">{{ __('About Us Section') }}</h2>
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
                                id="pills-informations-tab" data-bs-toggle="pill" data-bs-target="#{{ $language->locale }}"
                                type="button" role="tab" aria-controls="pills-informations" aria-selected="true"><i
                                    data-lucide="languages"></i>{{ $language->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="tab-content" id="pills-tabContent">
                @foreach ($groupData as $key => $value)
                    @php
                        $data = new Illuminate\Support\Fluent(is_array($value) ? $value : []);
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
                                    <input type="hidden" name="section_code" value="about-us">
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
                                                        <input type="radio" id="about-active" name="status"
                                                            @checked($status) value="1" />
                                                        <label for="about-active">{{ __('Show') }}</label>
                                                        <input type="radio" id="about-deactivate" name="status"
                                                            @checked(!$status) value="0" />
                                                        <label for="about-deactivate">{{ __('Hide') }}</label>
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
                                                {{ __('Section Image') }}
                                            </div>
                                            <div class="col-xl-9 col-lg-9 col-md-9 col-sm-12">
                                                <div class="wrap-custom-file">
                                                    <input type="file" name="left_image" id="aboutSectionImage"
                                                        accept=".gif, .jpg, .png, .webp" />
                                                    <label for="aboutSectionImage" id="left_image"
                                                        @if ($data['left_image'] ?? false) class="file-ok"
                                                        style="background-image: url({{ asset($data['left_image']) }})" @endif>
                                                        <img class="upload-icon"
                                                            src="{{ asset('global/materials/upload.svg') }}"
                                                            alt="" />
                                                        <span>{{ __('Update Image') }}</span>
                                                    </label>
                                                    @removeimg($data['left_image'] ?? null, left_image)
                                                </div>
                                            </div>
                                        </div>

                                        <div class="site-input-groups row">
                                            <div class="col-xl-3 col-lg-3 col-md-3 col-sm-12 col-label">
                                                {{ __('Background Image') }}
                                            </div>
                                            <div class="col-xl-9 col-lg-9 col-md-9 col-sm-12">
                                                <div class="wrap-custom-file">
                                                    <input type="file" name="background_image" id="backgroundImage"
                                                        accept=".gif, .jpg, .png, .webp" />
                                                    <label for="backgroundImage" id="background_image"
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

                <div class="site-card">
                    <div class="site-card-header">
                        <h3 class="title">{{ __('Highlight Items') }}</h3>
                        <div class="card-header-links">
                            <a href="" class="card-header-link" type="button" data-bs-toggle="modal"
                                data-bs-target="#addNew">{{ __('Add New') }}</a>
                        </div>
                    </div>
                    <div class="site-card-body">
                        <div class="site-table table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('Title') }}</th>
                                        <th scope="col">{{ __('Description') }}</th>
                                        <th scope="col">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($landingContent as $content)
                                        <tr>
                                            <td>{{ $content->title }}</td>
                                            <td>{{ $content->description }}</td>
                                            <td>
                                                <button class="round-icon-btn primary-btn editContent" type="button"
                                                    data-id="{{ $content->id }}">
                                                    <i data-lucide="edit-3"></i>
                                                </button>
                                                <button class="round-icon-btn red-btn deleteContent" type="button"
                                                    data-id="{{ $content->id }}">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('backend.page.' . site_theme() . '.section.include.__add_new_data', ['type' => 'about-us'])
    @include('backend.page.' . site_theme() . '.section.include.__edit_content')
    @include('backend.page.' . site_theme() . '.section.include.__delete_state')
@endsection
@section('script')
    @include('backend.page.section.include.__section_image_remove')
    <script>
        "use strict";

        $('.editContent').on('click', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var url = '{{ route('admin.page.content-edit', ':id') }}'.replace(':id', id);

            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    $('#target-element').html(response.html);
                    $('#editContent').modal('show');
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        });

        $('.deleteContent').on('click', function(e) {
            e.preventDefault();
            $('#deleteId').val($(this).data('id'));
            $('#deleteContent').modal('show');
        });
    </script>
@endsection
