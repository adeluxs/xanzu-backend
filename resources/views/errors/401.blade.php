@extends('errors.layout')
@push('title')
    {{ $exception?->getMessage() ?? __('Unauthorized') }}
@endpush