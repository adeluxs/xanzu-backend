@extends('backend.layouts.app')
@section('title')
    {{ __('Banners') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Banners') }}</h2>
                            <a href="{{ route('admin.banner.create') }}" class="title-btn"><i data-lucide="plus-circle"></i>
                                {{ __('Add Banner') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12">
                    <div class="site-card">
                        <div class="">
                            <div class="site-table table-responsive">
                                <form action="{{ request()->fullUrl() }}" method="get" id="filterForm">
                                    <div class="table-filter">
                                        <div class="filter">
                                            <div class="search">
                                                <input type="text" id="search" name="search"
                                                    value="{{ request('search') }}" placeholder="{{ __('Search') }}...">
                                            </div>
                                            <button type="submit" class="apply-btn">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    data-lucide="search" class="lucide lucide-search">
                                                    <circle cx="11" cy="11" r="8"></circle>
                                                    <path d="m21 21-4.3-4.3"></path>
                                                </svg>{{ __('Search') }}
                                            </button>
                                        </div>
                                        <div class="filter d-flex">
                                            <select class="form-select form-select-sm show"
                                                aria-label=".form-select-sm example" name="perPage" id="perPage">
                                                <option @selected(request('perPage') == 15) value="15">{{ __('15') }}
                                                </option>
                                                <option @selected(request('perPage') == 30) value="30">{{ __('30') }}
                                                </option>
                                                <option @selected(request('perPage') == 45) value="45">{{ __('45') }}
                                                </option>
                                                <option @selected(request('perPage') == 60) value="60">{{ __('60') }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </form>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col">{{ __('Name') }}</th>
                                            <th scope="col">{{ __('Category') }}</th>
                                            <th scope="col">{{ __('Image') }}</th>
                                            <th scope="col">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($banners as $banner)
                                            <tr>
                                                <td><strong>{{ $banner->name }}</strong></td>
                                                <td>{{ $banner->category?->name ?? '-' }}</td>
                                                <td>
                                                    @if (!empty($banner->image))
                                                        <img src="{{ asset($banner->image) }}" alt=""
                                                            style="height: 36px; width: auto;">
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('admin.banner.edit', $banner->id) }}"
                                                        class="round-icon-btn primary-btn" data-bs-toggle="tooltip"
                                                        title="" data-bs-placement="top"
                                                        data-bs-original-title="{{ __('Edit Banner') }}">
                                                        <i data-lucide="edit-3"></i>
                                                    </a>
                                                    <a href="#" class="round-icon-btn red-btn" id="deleteBtn"
                                                        data-id="{{ $banner->id }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-bs-original-title="{{ __('Delete Banner') }}">
                                                        <i data-lucide="trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <td colspan="10" class="text-center">{{ __('No Data Found!') }}</td>
                                        @endforelse
                                    </tbody>
                                </table>
                                @include('backend.banners.include.__delete_modal')
                                {{ $banners->links('backend.include.__pagination') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function($) {
            "use strict";

            $('body').on('click', '#deleteBtn', function() {
                var id = $(this).data('id');
                var url = '{{ route('admin.banner.delete', ':id') }}';
                url = url.replace(':id', id);
                $('#deleteForm').attr('action', url);
                $('#deleteModal').modal('show');
            });

        })(jQuery);
        $(document).on('change', '#filterForm select', function() {
            $('#filterForm').submit();
        });
    </script>
@endsection
