@extends('backend.layouts.app')
@section('title')
    {{ __('Courier Partners') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Courier Partners') }}</h2>
                            <a href="{{ route('admin.courier.create') }}" class="title-btn"><i data-lucide="plus-circle"></i>
                                {{ __('Add Courier Partner') }}</a>
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
                                        <th>{{ __('Logo') }}</th>
                                        <th>{{ __('Short Description') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($courierPartners as $courierPartner)
                                        <tr>
                                            <td>{{ $courierPartner->id }}</td>
                                            <td>{{ $courierPartner->name }}</td>
                                            <td>
                                                @if ($courierPartner->logo)
                                                    <img src="{{ asset($courierPartner->logo) }}" alt=""
                                                        height="40">
                                                @endif
                                            </td>
                                            <td>{{ $courierPartner->short_description ?? '—' }}</td>
                                            <td>
                                                @if ($courierPartner->status)
                                                    <div class="site-badge success">{{ __('Active') }}</div>
                                                @else
                                                    <div class="site-badge danger">{{ __('Inactive') }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.courier.edit', $courierPartner->id) }}"
                                                    class="round-icon-btn primary-btn">
                                                    <i data-lucide="edit-3"></i>
                                                </a>
                                                <form action="{{ route('admin.courier.delete', $courierPartner->id) }}"
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
                            {{ $courierPartners->links('backend.include.__pagination') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
