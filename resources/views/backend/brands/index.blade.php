@extends('backend.layouts.app')
@section('title')
    {{ __('Brands') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Brands') }}</h2>
                            <a href="{{ route('admin.brand.create') }}" class="title-btn"><i data-lucide="plus-circle"></i>
                                {{ __('Add Brand') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12">
                    <div class="site-card">
                        <div class="site-table table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('ID') }}</th>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Image') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($brands as $brand)
                                        <tr>
                                            <td>{{ $brand->id }}</td>
                                            <td>{{ $brand->name }}</td>
                                            <td>
                                                @if ($brand->image)
                                                    <img src="{{ asset($brand->image) }}" alt=""
                                                        height="40">
                                                @endif
                                            </td>
                                            <td>
                                                @if ($brand->status)
                                                    <div class="site-badge success">{{ __('Active') }}</div>
                                                @else
                                                    <div class="site-badge danger">{{ __('Inactive') }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.brand.edit', $brand->id) }}"
                                                    class="round-icon-btn primary-btn">
                                                    <i data-lucide="edit-3"></i>
                                                </a>
                                                <form action="{{ route('admin.brand.delete', $brand->id) }}" method="post"
                                                    style="display:inline">
                                                    @csrf
                                                    <button class="round-icon-btn red-btn" type="submit"><i
                                                            data-lucide="trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">{{ __('No Data Found!') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            {{ $brands->links('backend.include.__pagination') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
