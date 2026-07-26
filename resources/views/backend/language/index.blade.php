@extends('backend.setting.index')
@section('title')
    {{ __('Language Settings') }}
@endsection
@section('setting-title')
    {{ __('Language Settings') }}
@endsection
@section('setting-content')
    <div class="main-content pt-0">
        <div class="page-title pt-0">
            <div class="row">
                <div class="col">
                    <div class="title-content">
                        <h2 class="title"></h2>
                        <div>
                            <a href="{{ route('admin.language-sync-missing') }}" class="title-btn me-2"><i
                                    data-lucide="refresh-ccw"></i>{{ __('Sync Missing Translation Keys') }}</a>
                            <a href="{{ route('admin.language.create') }}" class="title-btn"><i
                                    data-lucide="plus-circle"></i>{{ __('Add New') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="site-card-body">
                    <div class="site-table table-responsive">
                        @include('backend.language.include.__filter')
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Language Name') }}</th>
                                    <th>{{ __('RTL Support') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($langs as $language)
                                    <tr>
                                        <td>
                                            @include('backend.language.include.__name', [
                                                'name' => $language->name,
                                                'locale' => $language->locale,
                                                'is_default' => $language->is_default,
                                            ])
                                        </td>
                                        <td>
                                            @if ($language->is_rtl)
                                                <div class="site-badge success">{{ __('Yes') }}</div>
                                            @else
                                                <div class="site-badge pending">{{ __('No') }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @include('backend.language.include.__status', [
                                                'status' => $language->status,
                                            ])
                                        </td>
                                        <td>
                                            @include('backend.language.include.__action', [
                                                'id' => $language->id,
                                                'locale' => $language->locale,
                                                'is_default' => $language->is_default,
                                                'name' => $language->name,
                                            ])
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{ $langs->links('backend.include.__pagination') }}
                    </div>
                </div>

                <!-- Modal for Delete Language -->
                <div class="modal fade" id="deleteLanguage" tabindex="-1" aria-labelledby="deleteLanguageModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-md modal-dialog-centered">
                        <div class="modal-content site-table-modal">
                            <div class="modal-body popup-body">
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                                <div class="popup-body-text centered">
                                    <div class="info-icon">
                                        <i data-lucide="alert-triangle"></i>
                                    </div>
                                    <div class="title">
                                        <h4>{{ __('Are you sure?') }}</h4>
                                    </div>
                                    <p>
                                        {{ __('You want to delete') }} <strong id="language-name"></strong>
                                        {{ __('Language?') }}
                                    </p>
                                    <div class="action-btns">
                                        <form id="deleteLanguageForm" method="post">
                                            @method('DELETE')
                                            @csrf
                                            <button type="submit" class="site-btn-sm primary-btn me-2">
                                                <i data-lucide="check"></i>
                                                Confirm
                                            </button>
                                            <a href="" class="site-btn-sm red-btn" type="button"
                                                data-bs-dismiss="modal" aria-label="Close"><i
                                                    data-lucide="x"></i>{{ __('Cancel') }}</a>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Modal for Delete Language End-->
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        (function($) {
            "use strict";

            $('body').on('click', '#deleteLanguageModal', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');

                $('#language-name').html(name);
                var url = '{{ route('admin.language.destroy', ':id') }}';
                url = url.replace(':id', id);
                $('#deleteLanguageForm').attr('action', url);
                $('#deleteLanguage').modal('toggle')

            })

        })(jQuery);
    </script>
@endsection
