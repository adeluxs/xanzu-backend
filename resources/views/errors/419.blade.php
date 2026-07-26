@extends('errors.layout')
@push('title')
    {{ $exception?->getMessage() ?? __('Page Expired') }}
@endpush