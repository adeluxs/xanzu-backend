@extends('backend.layouts.app')

@section('title')
    {{ __('Transfer Limits') }}
@endsection

@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-xl-12 col-md-12">
                        <div class="title-content">
                            <h2 class="title">{{ __('Transfer Limits') }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-xl-12 col-md-12">
                    <div class="site-card">
                        <div class="site-card-header">
                            <h3 class="title">{{ __('Manage Transfer Limits') }}</h3>
                        </div>
                        <div class="site-card-body">
                            @if (session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif

                            @if (!empty($availableTypes))
                                <div class="site-card border mb-4">
                                    <div class="site-card-header">
                                        <h4 class="title">{{ __('Add Transfer Limit') }}</h4>
                                    </div>
                                    <div class="site-card-body">
                                        <form action="{{ route('admin.transfer-limit.store') }}" method="post" class="row g-3 align-items-end">
                                            @csrf
                                            <div class="col-xl-3 col-md-6">
                                                <label class="box-input-label">{{ __('Applies To') }}</label>
                                                <select name="user_type" class="form-select" required>
                                                    @foreach ($availableTypes as $type)
                                                        <option value="{{ $type }}" @selected(old('user_type') === $type)>
                                                            {{ $type === 'all' ? __('All Users') : __(ucfirst($type)) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-xl-2 col-md-6">
                                                <label class="box-input-label">{{ __('Minimum Amount') }}</label>
                                                <input type="number" step="0.01" min="0" name="min_amount" class="form-control" value="{{ old('min_amount', 0) }}" required>
                                            </div>
                                            <div class="col-xl-2 col-md-6">
                                                <label class="box-input-label">{{ __('Maximum Amount') }}</label>
                                                <input type="number" step="0.01" min="0" name="max_amount" class="form-control" value="{{ old('max_amount', 0) }}">
                                            </div>
                                            <div class="col-xl-2 col-md-6">
                                                <label class="box-input-label">{{ __('Daily Limit') }}</label>
                                                <input type="number" step="0.01" min="0" name="daily_limit" class="form-control" value="{{ old('daily_limit', 0) }}">
                                            </div>
                                            <div class="col-xl-2 col-md-6">
                                                <label class="box-input-label">{{ __('Monthly Limit') }}</label>
                                                <input type="number" step="0.01" min="0" name="monthly_limit" class="form-control" value="{{ old('monthly_limit', 0) }}">
                                            </div>
                                            <input type="hidden" name="daily_transaction_count" value="0">
                                            <input type="hidden" name="monthly_transaction_count" value="0">
                                            <input type="hidden" name="status" value="1">
                                            <div class="col-xl-1 col-md-6">
                                                <button type="submit" class="site-btn-sm primary-btn w-100">{{ __('Add') }}</button>
                                            </div>
                                        </form>
                                        <small class="text-muted d-block mt-2">{{ __('Use 0 for any unlimited amount. After creating a policy you can configure transaction-count limits below.') }}</small>
                                    </div>
                                </div>
                            @endif

                            <div class="row">
                                @forelse ($limits as $limit)
                                    <div class="col-xl-4 col-md-6 mb-4">
                                        <div class="site-card border {{ $limit->status ? 'border-success' : 'border-danger' }}">
                                            <div class="site-card-header">
                                                <h4 class="title text-capitalize">
                                                    {{ $limit->user_type }}
                                                    @if ($limit->user_type == 'all')
                                                        ({{ __('All Users') }})
                                                    @endif
                                                    @if ($limit->status)
                                                        <span class="badge bg-success ms-2">{{ __('Active') }}</span>
                                                    @else
                                                        <span class="badge bg-danger ms-2">{{ __('Inactive') }}</span>
                                                    @endif
                                                </h4>
                                            </div>
                                            <div class="site-card-body">
                                                <form action="{{ route('admin.transfer-limit.' . ($limit->exists ? 'update' : 'store'), $limit->exists ? $limit->id : null) }}" method="post">
                                                    @csrf
                                                    @if ($limit->exists)
                                                        @method('PUT')
                                                    @endif

                                                    <input type="hidden" name="user_type" value="{{ $limit->user_type }}">

                                                    <div class="mb-3">
                                                        <label class="box-input-label">{{ __('Minimum Amount') }}</label>
                                                        <input type="number" step="0.01" name="min_amount" class="form-control" value="{{ $limit->min_amount }}" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="box-input-label">{{ __('Maximum Amount') }}</label>
                                                        <input type="number" step="0.01" name="max_amount" class="form-control" value="{{ $limit->max_amount }}">
                                                        <small class="text-muted">{{ __('Leave 0 for unlimited') }}</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="box-input-label">{{ __('Daily Limit') }}</label>
                                                        <input type="number" step="0.01" name="daily_limit" class="form-control" value="{{ $limit->daily_limit }}">
                                                        <small class="text-muted">{{ __('Leave 0 for unlimited') }}</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="box-input-label">{{ __('Daily Transaction Count') }}</label>
                                                        <input type="number" name="daily_transaction_count" class="form-control" value="{{ $limit->daily_transaction_count }}">
                                                        <small class="text-muted">{{ __('Leave 0 for unlimited') }}</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="box-input-label">{{ __('Monthly Limit') }}</label>
                                                        <input type="number" step="0.01" name="monthly_limit" class="form-control" value="{{ $limit->monthly_limit }}">
                                                        <small class="text-muted">{{ __('Leave 0 for unlimited') }}</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="box-input-label">{{ __('Monthly Transaction Count') }}</label>
                                                        <input type="number" name="monthly_transaction_count" class="form-control" value="{{ $limit->monthly_transaction_count }}">
                                                        <small class="text-muted">{{ __('Leave 0 for unlimited') }}</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="box-input-label">{{ __('Status') }}</label>
                                                        <div class="switch-field">
                                                            <input type="radio" id="status-1-{{ $limit->user_type }}" name="status" value="1" @checked($limit->status)>
                                                            <label for="status-1-{{ $limit->user_type }}">{{ __('Active') }}</label>
                                                            <input type="radio" id="status-0-{{ $limit->user_type }}" name="status" value="0" @checked(!$limit->status)>
                                                            <label for="status-0-{{ $limit->user_type }}">{{ __('Inactive') }}</label>
                                                        </div>
                                                    </div>

                                                    <button type="submit" class="site-btn-sm primary-btn">
                                                        {{ __('Save') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="text-center">{{ __('No transfer limits configured.') }}</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
