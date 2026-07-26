@extends('backend.layouts.app')
@section('title')
    {{ __('Update :type', ['type' => $category->parent_id ? 'Sub Category' : 'Category']) }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">
                                {{ __('Update :type', ['type' => $category->parent_id ? 'Sub Category' : 'Category']) }}
                            </h2>
                            <a href="{{ route('admin.category.index') }}" class="title-btn"><i
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
                        <div class="site-card-header">
                            <h3 class="title">{{ __('Main Category') }}</h3>
                        </div>
                        <div class="site-card-body">
                            <form action="{{ route('admin.category.update', $category->id) }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="site-input-groups">
                                            <label for="" class="box-input-label">{{ __('Image') }} <span
                                                    class="text-danger">*</span></label>
                                            <div class="wrap-custom-file">
                                                <input type="file" name="image" id="image"
                                                    accept=".jpeg, .jpg, .png">
                                                <label for="image" class="file-ok"
                                                    style="background-image: url({{ asset($category->image) }})">
                                                    <img class="upload-icon d-block"
                                                        src="{{ asset('assets/global/materials/upload.svg') }}"
                                                        alt="">
                                                    <span>{{ __('Upload Category Image') }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-xxl12">
                                        <div class="site-input-groups">
                                            <label for="" class="box-input-label">{{ __('Name') }} <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="name" class="box-input mb-0"
                                                value="{{ $category->name }}" required />
                                        </div>
                                    </div>

                                    <div class="col-xxl-6">
                                        <div class="site-input-groups">
                                            <label class="box-input-label" for="">{{ __('Status') }} <span
                                                    class="text-danger">*</span></label>
                                            <div class="switch-field same-type">
                                                <input type="radio" id="radio-active" name="status" value="1"
                                                    @checked($category->status == 1) required />
                                                <label for="radio-active">{{ __('Active') }}</label>
                                                <input type="radio" id="radio-inactive" name="status" value="0"
                                                    @checked($category->status == 0) required />
                                                <label for="radio-inactive">{{ __('Inactive') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xxl-6">
                                        <div class="site-input-groups">
                                            <label class="box-input-label"
                                                for="">{{ __(key: 'Is Trending Category') }} <span
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
                                        {{ __('Update Category') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @include('backend.category.include.__sub_category')

            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        "use strict";
        // OPEN create (extra reset)
        $('#openCreateSubBtn, #openCreateSubBtnTop').on('click', function() {
            const form = $('#createSubForm')[0];
            if (form) form.reset();
            $('#createSubModal').modal('show');
        });

        // Edit button
        $(document).on('click', '.edit-sub-btn', function() {
            console.log('Edit button clicked');
            const btn = $(this);
            $('#editSubForm').attr('action', btn.data('update-url'));
            $('#edit_name').val(btn.data('name'));
            const status = String(btn.data('status')) === '1';
            $('#edit-sub-active').prop('checked', status);
            $('#edit-sub-inactive').prop('checked', !status);

            const img = btn.data('image');
            if (img) {
                $('#editImagePreview')
                    .css('background-image', 'url(' + img + ')')
                    .addClass('has-image');
            } else {
                $('#editImagePreview').css('background-image', 'none').removeClass('has-image');
            }
            $('#editSubModal').modal('show');
        });

        // Delete button
        $('body').on('click', '.delete-sub-btn', function() {
            const btn = $(this);
            $('#deleteSubForm').attr('action', btn.data('delete-url'));
            $('#deleteMessage').text("{{ __('Are you sure you want to delete (:name) ?') }}".replace(':name', btn
                .data('name')));
            $('#deleteSubModal').modal('show');
        });

        // Clear forms when hidden (optional)
        $('#createSubModal').on('hidden.bs.modal', function() {
            $('#createSubForm')[0].reset();
        });
        $('#editSubModal').on('hidden.bs.modal', function() {
            $('#editSubForm')[0].reset();
            $('#editImagePreview').css('background-image', 'none').removeClass('has-image');
        });
    </script>
@endsection
