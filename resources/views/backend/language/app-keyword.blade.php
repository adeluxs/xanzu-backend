@extends('backend.layouts.app')
@section('title')
    {{ __('Language App Keywords') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Language App Keywords') }} ({{ ucwords($locale) }})</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12">
                    <div class="site-card-body">
                        <div class="site-table table-responsive">
                            <form action="{{ request()->url() }}" method="get" id="filterForm">
                                <div class="table-filter">
                                    <div class="filter">
                                        <div class="search">
                                            <input type="text" id="search" name="search"
                                                value="{{ request('search') }}" placeholder="Search..." />
                                        </div>
                                        <button type="submit" class="apply-btn"><i
                                                data-lucide="search"></i>{{ __('Search') }}</button>
                                    </div>
                                </div>
                            </form>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">{{ __('Key') }}</th>
                                        <th scope="col">{{ __('Value') }}</th>
                                        <th scope="col">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($translations as $key => $value)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $key }}</td>
                                            <td>
                                                {{ $value }}
                                            </td>
                                            <td>
                                                <button class="round-icon-btn primary-btn edit-language-keyword"
                                                    data-language="{{ $locale }}" data-group="app"
                                                    data-key="{{ $key }}" data-value="{{ $value }}"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-original-title="{{ __('Edit Value') }}"><i
                                                        data-lucide="edit-3"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <td colspan="4" class="text-center">{{ __('No Data Found!') }}</td>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Edit Language Key-->
        <div class="modal fade" id="editKeyword" tabindex="-1" aria-labelledby="editLanguageKeyModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content site-table-modal">
                    <div class="modal-body popup-body">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        <form action="{{ route('admin.language.app-keyword-update') }}" method="post">
                            @csrf
                            <div class="popup-body-text">
                                <h3 class="title">{{ __('Edit Keyword') }}</h3>
                                <div class="site-input-groups mb-2">
                                    <label class="box-input-label key-label"></label>
                                    <input type="hidden" class="box-input key-key" name="key">
                                    <input type="text" class="box-input key-value" name="value">
                                    <input type="hidden" class="box-input key-group" name="group">
                                    <input type="hidden" class="box-input key-language" name="language">
                                </div>
                                <div class="action-btns">
                                    <button type="submit" class="site-btn-sm primary-btn me-2">
                                        <i data-lucide="check"></i>
                                        {{ __('Save Changes') }}
                                    </button>
                                    <a href="" class="site-btn-sm red-btn" type="button" data-bs-dismiss="modal"
                                        aria-label="Close"><i data-lucide="x"></i>{{ __('Close') }}</a>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
        <!-- Modal for Edit Language Key End-->
    </div>
@endsection
@section('script')
    <script>
        (function($) {
            "use strict";

            $('.edit-language-keyword').on('click', function(e) {

                var key = $(this).data('key');
                var value = $(this).data('value');
                var group = $(this).data('group');
                var language = $(this).data('language');


                $('.key-label').html(key);
                $('.key-key').val(key);
                $('.key-value').val(value);
                $('.key-group').val(group);
                $('.key-language').val(language);

                $('#editKeyword').modal('toggle')
            })
        })(jQuery);
    </script>
@endsection
