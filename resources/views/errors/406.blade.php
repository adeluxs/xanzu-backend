@extends('errors.layout')
@push('title')
    {{ $exception?->getMessage() ?? __('Not Acceptable') }}
@endpush