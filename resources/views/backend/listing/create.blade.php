@extends('backend.layouts.app')
@section('title')
    {{ __('Create Listing') }}
@endsection
@section('setting-title')
    {{ __('Create New Listing') }}
@endsection
@section('style')
    <style>
        .prcntcurr.prcntcurr-large {
            width: 110px;
        }
    </style>
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Create Listing') }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 col-lg-12 col-md-12 col-12">
                    <div class="site-card">
                        <div class="site-card-header">
                            <h3 class="title">{{ __('Listing Information') }}</h3>
                            <div class="card-header-links">
                                <a href="{{ route('admin.listing.index') }}" class="site-btn-xs primary-btn">
                                    <i data-lucide="list"></i>{{ __('All Listings') }}
                                </a>
                            </div>
                        </div>
                        <div class="site-card-body">
                            <form action="{{ route('admin.listing.store') }}" method="POST" enctype="multipart/form-data"
                                id="listingForm">
                                @csrf
                                <div class="row">
                                    <div class="col-xl-12">
                                        @include('backend.listing.include.__form', ['listing' => null])
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
