@extends('backend.layouts.app')
@section('title')
    {{ __('Send email to all') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Send email to all') }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">

                <div class="col-xl-12">
                    <div class="site-card">
                        <div class="site-card-body">
                            <form action="{{ route('admin.mail-send.sent') }}" method="post">
                                @csrf
                                <div class="row">
                                    <div class="site-input-groups col-9">
                                        <label for="" class="box-input-label">{{ __('Subject:') }}</label>
                                        <input type="text" name="subject" class="box-input mb-0" required="" />
                                    </div>
                                    <div class="site-input-groups col-3">
                                        <label for="" class="box-input-label">{{ __('User Type:') }}</label>
                                        <select name="user_type" class="form-select mb-0">
                                            <option value="all">{{ __('All') }}</option>
                                            <option value="buyer">{{ __('Buyer') }}</option>
                                            <option value="merchant">{{ __('Merchant') }}</option>
                                        </select>
                                    </div>
                                    <div class="site-input-groups col-12">
                                        <label for="" class="box-input-label">{{ __('Email Details') }}</label>
                                        <textarea name="message" class="form-textarea mb-0"></textarea>
                                    </div>

                                    <div class="action-btns">
                                        <button type="submit" class="site-btn-sm primary-btn me-2">
                                            <i data-lucide="send"></i>
                                            {{ __('Send Email') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
