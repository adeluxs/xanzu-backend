@if ($status == 1)
    <div class="site-badge success">{{ __('Enabled') }}</div>
@else
    <div class="site-badge danger">{{ __('Disabled') }}</div>
@endif
