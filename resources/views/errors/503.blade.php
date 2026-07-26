@extends('errors.layout')
@push('title')
    {{ $exception?->getMessage() ?? __('Maintenance Mode') }}
@endpush
@section('description')
    {{ setting('maintenance_text', 'site_maintenance') }}
@endsection
