@extends('errors.layout')
@push('title')
    {{ $exception?->getMessage() ?? __('Forbidden') }}
@endpush