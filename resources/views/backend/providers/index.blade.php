@extends('backend.layouts.app')
@section('title')
    {{ __('Providers') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Providers') }}</h2>
                            <a href="{{ route('admin.provider.create') }}" class="title-btn"><i data-lucide="plus-circle"></i>
                                {{ __('Add Provider') }}</a>
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
                                        <th>{{ __('Website') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($providers as $provider)
                                        <tr>
                                            <td>{{ $provider->id }}</td>
                                            <td>{{ $provider->name }}</td>
                                            <td>
                                                @if ($provider->image)
                                                    <img src="{{ asset($provider->image) }}" alt="" height="40">
                                                @endif
                                            </td>
                                            <td>
                                                @if ($provider->website_url)
                                                    <a class="link" href="{{ $provider->website_url }}" target="_blank"
                                                        rel="noopener">{{ $provider->website_url }}</a>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($provider->status)
                                                    <div class="site-badge success">{{ __('Active') }}</div>
                                                @else
                                                    <div class="site-badge danger">{{ __('Inactive') }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.provider.edit', $provider->id) }}"
                                                    class="round-icon-btn primary-btn">
                                                    <i data-lucide="edit-3"></i>
                                                </a>
                                                <form action="{{ route('admin.provider.delete', $provider->id) }}"
                                                    method="post" style="display:inline">
                                                    @csrf
                                                    <button class="round-icon-btn red-btn" type="submit"><i
                                                            data-lucide="trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">{{ __('No Data Found!') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            {{ $providers->links('backend.include.__pagination') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
