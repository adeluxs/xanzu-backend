@extends('backend.layouts.app')
@section('title')
    {{ __('Templates') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">@yield('title')</h2>
                            <a href="{{ route('admin.settings.mail') }}" class="title-btn"><i
                                    data-lucide="mail"></i>{{ __('Email Settings') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">

                <div class="col-xl-12">
                    <div class="site-card-body table-responsive">
                        <div class="site-table table-responsive">
                            @include('backend.template.include.__filter', ['status' => true])
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Email For') }}</th>
                                        <th>{{ __('Notification Status') }}</th>
                                        <th>{{ __('Email Status') }}</th>
                                        <th>{{ __('SMS Status') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($templates as $email)
                                        <tr>
                                            <td>
                                                @include('backend.template.include.__name', [
                                                    'name' => $email->name,
                                                    'for' => $email->for,
                                                ])
                                            </td>
                                            <td>
                                                @include('backend.template.include.__status', [
                                                    'status' => $email->notification_status,
                                                ])
                                            </td>
                                            <td>
                                                @include('backend.template.include.__status', [
                                                    'status' => $email->email_status,
                                                ])
                                            </td>
                                            <td>
                                                @include('backend.template.include.__status', [
                                                    'status' => $email->sms_status,
                                                ])
                                            </td>
                                            <td>
                                                @include('backend.template.include.__action', [
                                                    'id' => $email->id,
                                                ])
                                            </td>
                                        </tr>
                                    @empty
                                        <td colspan="5" class="text-center">{{ __('No Data Found!') }}</td>
                                    @endforelse
                                </tbody>
                            </table>
                            {{ $templates->links('backend.include.__pagination') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
