@extends('errors.layout')
@push('title')
    {{ $exception?->getMessage() ?? __('Too Many Requests') }}
@endpush