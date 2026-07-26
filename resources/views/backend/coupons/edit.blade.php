@extends('backend.layouts.app')
@section('title', 'Edit Coupon')
@section('content')
    <div class="main-content">
        <div class="page-title">
            <div class="container-fluid">
                <div class="row">
                    <div class="col">
                        <div class="title-content">
                            <h2 class="title">{{ __('Edit Coupon') }}</h2>
                            <a href="{{ route('admin.coupon.index') }}" class="title-btn"><i
                                    data-lucide="arrow-left"></i>{{ __('Back') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-6">
                    <div class="site-card">
                        <div class="site-card-body">
                            <form action="{{ route('admin.coupon.update', $coupon->id) }}" method="post"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-xxl-4">
                                        <div class="site-input-groups">
                                            <label for="code" class="box-input-label">{{ __('Coupon Code') }} <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="code" class="box-input mb-0"
                                                value="{{ old('code', $coupon->code) }}" required />
                                        </div>
                                    </div>
                                    <div class="col-xxl-4">
                                        <div class="site-input-groups">
                                            <label for="discount_type" class="box-input-label">{{ __('Discount') }} <span
                                                    class="text-danger">*</span></label>
                                            <div class="position-relative">
                                                <input type="text" class="box-input mb-0"
                                                    oninput="this.value = validateDouble(this.value)" name="discount_value"
                                                    value="{{ old('discount_value', $coupon->discount_value) }}" required>
                                                <div class="prcntcurr">
                                                    <select name="discount_type" class="form-select">
                                                        <option value="percentage" @selected(old('discount_type', $coupon->discount_type) == 'percentage')>%</option>
                                                        <option value="amount" @selected(old('discount_type', $coupon->discount_type) == 'amount')>$</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4">
                                        <div class="site-input-groups">
                                            <label for="expires_at" class="box-input-label">{{ __('Expiration Date') }}
                                                <span class="text-danger">*</span></label>
                                            <input type="date" name="expires_at" class="box-input mb-0"
                                                value="{{ old('expires_at', $coupon->expires_at->format('Y-m-d')) }}"
                                                required />
                                        </div>
                                    </div>
                                    <div class="col-xxl-4">
                                        <div class="site-input-groups">
                                            <label for="max_use_limit" class="box-input-label">{{ __('Max Use Limit') }}
                                                <span class="text-danger">*</span></label>
                                            <input readonly type="number" name="max_use_limit" class="box-input mb-0"
                                                value="{{ old('max_use_limit', $coupon->max_use_limit) }}" required />
                                        </div>
                                    </div>
                                    <div class="col-xxl-4">
                                        <div class="site-input-groups">
                                            <label for="total_used" class="box-input-label">{{ __('Total Used') }} <span
                                                    class="text-danger">*</span></label>
                                            <input readonly type="number" name="total_used" class="box-input mb-0"
                                                value="{{ old('total_used', $coupon->total_used) }}" required />
                                        </div>
                                    </div>
                                    <div class="col-xxl-4">
                                        <div class="site-input-groups">
                                            <label for="status" class="box-input-label">{{ __('Status') }} <span
                                                    class="text-danger">*</span></label>
                                            <select name="status" class="form-select" required>
                                                <option value="1" @selected(old('status', $coupon->status) == '1')>{{ __('Active') }}
                                                </option>
                                                <option value="0" @selected(old('status', $coupon->status) == '0')>{{ __('Inactive') }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xxl-4">
                                        <div class="site-input-groups">
                                            <label class="box-input-label" for="">{{ __('Show in Home') }}</label>
                                            <div class="switch-field same-type">
                                                <input type="radio" id="radio-home-yes" name="is_home" value="1"
                                                    @checked(old('is_home', (string) (int) $coupon->is_home) == '1')>
                                                <label for="radio-home-yes">{{ __('Yes') }}</label>
                                                <input type="radio" id="radio-home-no" name="is_home" value="0"
                                                    @checked(old('is_home', (string) (int) $coupon->is_home) == '0')>
                                                <label for="radio-home-no">{{ __('No') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- admin_approval removed -->
                                </div>
                                <div class="action-btns">
                                    <button type="submit" class="site-btn-sm primary-btn me-2">
                                        <i data-lucide="check"></i> {{ __('Update Coupon') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
