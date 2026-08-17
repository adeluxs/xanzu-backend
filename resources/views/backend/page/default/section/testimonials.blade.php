@extends('backend.layouts.app')
@section('title')
    {{ __('Testimonial Section') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-xl-12">
                        <div class="title-content">
                            <h2 class="title">{{ __('Testimonial Section') }}</h2>
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
                                <h3 class="title">{{ __('Heading and Activity') }}</h3>
                            </div>
                            <div class="site-card-body">
                                <form action="{{ route('admin.page.section.section.update') }}" method="post"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="section_code" value="testimonials">
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
                                                        <input type="radio" id="testimonial-active" name="status"
                                                            @checked($status) value="1" />
                                                        <label for="testimonial-active">{{ __('Show') }}</label>
                                                        <input type="radio" id="testimonial-deactivate" name="status"
                                                            @checked(!$status) value="0" />
                                                        <label for="testimonial-deactivate">{{ __('Hide') }}</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="site-input-groups row">
                                        <label for="" class="col-sm-3 col-label">{{ __('Section Title') }}</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="testimonial_title" class="box-input"
                                                value="{{ $data->testimonial_title }}">
                                        </div>
                                    </div>

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

            <div class="site-card">
                <div class="site-card-header">
                    <h3 class="title">{{ __('Testimonials') }}</h3>
                    <div class="card-header-links">
                        <a href="" class="card-header-link" type="button" data-bs-toggle="modal"
                            data-bs-target="#addNew">{{ __('Add New') }}</a>
                    </div>
                </div>
                @php
                    $testimonials = App\Models\Testimonial::where('locale', 'en')->get();
                @endphp
                <div class="">
                    <div class="site-table table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Image') }}</th>
                                    <th scope="col">{{ __('Name') }}</th>
                                    <th scope="col">{{ __('Designation') }}</th>
                                    <th scope="col">{{ __('Rating') }}</th>
                                    <th scope="col">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($testimonials as $content)
                                    <tr>
                                        <td>
                                            <img src="{{ asset($content->picture) }}" alt="{{ $content->name }}"
                                                style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                                        </td>
                                        <td>{{ $content->name }}</td>
                                        <td>{{ $content->designation }}</td>
                                        <td>{{ $content->star }}/5</td>
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

    @include('backend.page.' . site_theme() . '.section.include.__add_new_testimonial')
    @include('backend.page.' . site_theme() . '.section.include.__edit')
    @include('backend.page.' . site_theme() . '.section.include.__delete_testimonial')
@endsection
@section('script')
    <script>
        "use strict";

        $('.editContent').on('click', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var url = '{{ route('admin.page.testimonial.edit', ':id') }}'.replace(':id', id);

            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    $('#target-element').html(response);
                    imagePreview();
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
