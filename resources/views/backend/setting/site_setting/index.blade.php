@extends('backend.setting.index')
@section('setting-title')
    {{ __('Site Settings') }}
@endsection
@section('title')
    {{ __('Site Settings') }}
@endsection
@section('setting-content')
    @php
        $configSettings = config('setting');
        $halfPoint = intval(count($configSettings) / 2);
    @endphp
    {{-- @dd($configSettings); --}}

    <div class="col-xl-6 col-lg-12 col-md-12 col-12">@include('backend.setting.site_setting.include.__global', [
        'fields' => $configSettings['global'],
        'section' => 'global',
    ])</div>
    <div class="col-xl-6 col-lg-12 col-md-12 col-12">@include('backend.setting.site_setting.include.__permission', [
        'fields' => $configSettings['permission'],
        'section' => 'permission',
    ])</div>
    <div class="col-xl-6 col-lg-12 col-md-12 col-12">@include('backend.setting.site_setting.include.__delivery', [
        'fields' => $configSettings['delivery'],
        'section' => 'delivery',
    ])</div>
    <div class="col-xl-6 col-lg-12 col-md-12 col-12">@include('backend.setting.site_setting.include.__fee', [
        'fields' => $configSettings['fee'],
        'section' => 'fee',
    ])</div>
    <div class="col-xl-6 col-lg-12 col-md-12 col-12">@include('backend.setting.site_setting.include.__flash_sale', [
        'fields' => $configSettings['flash_sale'],
        'section' => 'flash_sale',
    ])</div>
    <div class="col-xl-6 col-lg-12 col-md-12 col-12">@include('backend.setting.site_setting.include.__gdpr', [
        'fields' => $configSettings['gdpr'],
        'section' => 'gdpr',
    ])</div>
    <div class="col-xl-6 col-lg-12 col-md-12 col-12">@include('backend.setting.site_setting.include.__inactive_user', [
        'fields' => $configSettings['inactive_user'],
        'section' => 'inactive_user',
    ])</div>
    <div class="col-xl-6 col-lg-12 col-md-12 col-12">@include('backend.setting.site_setting.include.__kyc', [
        'fields' => $configSettings['kyc'],
        'section' => 'kyc',
    ])</div>
    <div class="col-xl-6 col-lg-12 col-md-12 col-12">@include('backend.setting.site_setting.include.__site_maintenance', [
        'fields' => $configSettings['site_maintenance'],
        'section' => 'site_maintenance',
    ])</div>
    <div class="col-xl-6 col-lg-12 col-md-12 col-12">@include('backend.setting.site_setting.include.__system', [
        'fields' => $configSettings['system'],
        'section' => 'system',
    ])</div>
@endsection
@push('single-script')
    <script>
        (function($) {
            'use strict';

            var timezoneData = @json(\DateTimeZone::listIdentifiers(DateTimeZone::ALL));
            const convertedData = timezoneData.map(item => ({
                id: item,
                text: `${item}`
            }));

            $('.site-timezone').select2({
                data: convertedData
            });
        })(jQuery);

        function fieldActiveToggle(fieldName, selectors) {
            "use strict";
            var trueValue = ['1'];
            if (trueValue.includes($('[name="' + fieldName + '"]:checked').val())) {
                $(selectors).removeClass('d-none');
            } else {
                $(selectors).addClass('d-none');
            }
        }
    </script>
@endpush
