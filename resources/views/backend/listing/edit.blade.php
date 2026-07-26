@extends('backend.layouts.app')
@section('title')
    {{ __('Edit Listing') }}
@endsection
@section('title')
    {{ __('Edit Listing') }}
@endsection
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Edit Listing') }}</h2>
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
                                <a href="{{ route('admin.listing.index') }}" class="site-btn primary-btn">
                                    <i data-lucide="list"></i>{{ __('All Listings') }}
                                </a>
                                <a href="{{ route('admin.listing.delivery-items', $listing->id) }}" class="site-btn primary-btn">
                                    <i data-lucide="package"></i>{{ __('Delivery Items') }}
                                </a>
                            </div>
                        </div>
                        <div class="site-card-body">
                            <form action="{{ route('admin.listing.update', $listing->id) }}" method="POST"
                                enctype="multipart/form-data" id="listingForm">
                                @csrf
                                @include('backend.listing.include.__form', ['listing' => $listing])
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
