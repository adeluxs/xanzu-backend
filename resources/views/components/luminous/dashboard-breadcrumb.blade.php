<div class="sort-title {{ $attributes->get('class') }}">
  <h4>{{ $attributes->get('title') }}</h4>
  <div class="table-sort {{ $attributes->get('class') }}">
    {!! $slot !!}
  </div>
</div>