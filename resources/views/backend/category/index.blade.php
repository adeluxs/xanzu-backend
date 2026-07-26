@extends('backend.layouts.app')
@section('title')
    {{ __('Categories') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Categories') }}</h2>
                            @can('category-create')
                                <a href="{{ route('admin.category.create') }}" class="title-btn"><i
                                        data-lucide="plus-circle"></i>{{ __('Add New') }}</a>
                            @endcan
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
                                        <div class="filter d-flex">
                                            <div class="search">
                                                <input type="text" id="search" name="search" value=""
                                                    placeholder="{{ __('Search') }}...">
                                            </div>
                                            <button type="submit" class="apply-btn"><svg xmlns="http://www.w3.org/2000/svg"
                                                    width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" data-lucide="search"
                                                    class="lucide lucide-search">
                                                    <circle cx="11" cy="11" r="8"></circle>
                                                    <path d="m21 21-4.3-4.3"></path>
                                                </svg>{{ __('Search') }}</button>
                                        </div>
                                    </div>
                                </form>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col">{{ __('Image') }}</th>
                                            @include('backend.filter.th', [
                                                'label' => __('Category Name'),
                                                'field' => 'name',
                                            ])
                                            @include('backend.filter.th', [
                                                'label' => __('Sub Category Count'),
                                                'field' => 'children_count',
                                            ])
                                            <th scope="col">{{ __('Is Trending') }}</th>
                                            <th scope="col">{{ __('Status') }}</th>
                                            <th scope="col">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($categories as $category)
                                            <tr>
                                                <td><img src="{{ asset($category->image) }}" alt="{{ $category->name }}"
                                                        width="50"></td>
                                                <td><strong>
                                                        @if ($category->parent_id)
                                                            {{ $category->parent->name . ' > ' }}
                                                        @endif
                                                        {{ $category->name }}
                                                    </strong></td>
                                                <td>
                                                    {{ $category->children_count }}
                                                </td>
                                                <td>
                                                    @if ($category->is_trending)
                                                        <div class="site-badge success">{{ __('Yes') }}</div>
                                                    @else
                                                        <div class="site-badge danger">{{ __('No') }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($category->status)
                                                        <div class="site-badge success">{{ __('Active') }}</div>
                                                    @else
                                                        <div class="site-badge danger">{{ __('Inactive') }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @can('category-edit')
                                                        <a href="{{ route('admin.category.edit', $category->id) }}"
                                                            class="round-icon-btn primary-btn" id="edit"
                                                            data-bs-toggle="tooltip" title="" data-bs-placement="top"
                                                            data-bs-original-title="{{ __('Edit Category') }}">
                                                            <i data-lucide="edit-3"></i>
                                                        </a>
                                                    @endcan
                                                    @can('category-delete')
                                                        <a href="#" class="round-icon-btn red-btn" id="deleteBtn"
                                                            data-id="{{ $category->id }}" data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            data-bs-original-title="{{ __('Delete Category') }}">
                                                            <i data-lucide="trash"></i>
                                                        </a>
                                                    @endcan

                                                    @can('category-edit')
                                                        <a href="{{ route('admin.category.trending.toggle', $category->id) }}"
                                                            class="round-icon-btn {{ $category->is_trending ? 'green-btn' : 'red-btn' }}"
                                                            id="edit" data-bs-toggle="tooltip" title=""
                                                            data-bs-placement="top"
                                                            data-bs-original-title="{{ $category->is_trending ? __('Unset') : __('Set') }} {{ __('Trending Category') }}">
                                                            <i data-lucide="flame"></i>
                                                        </a>
                                                    @endcan

                                                </td>
                                            </tr>
                                        @empty
                                            <td colspan="6" class="text-center">{{ __('No Data Found!') }}</td>
                                        @endforelse
                                    </tbody>
                                </table>
                                @include('backend.category.include.__delete_modal')
                            </div>
                            {{ $categories->links('backend.include.__pagination') }}
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

            // Delete Modal
            $('body').on('click', '#deleteBtn', function() {
                var id = $(this).data('id');
                var url = '{{ route('admin.category.delete', ':id') }}';
                url = url.replace(':id', id);
                $('#deleteForm').attr('action', url);
                $('#deleteModal').modal('show');
            });

        })(jQuery);
    </script>
@endsection
