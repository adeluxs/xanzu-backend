@php
    $isRtl = isRtl(app()->getLocale());
@endphp

<!DOCTYPE html>
<html class="no-js" lang="{{ app()->getLocale() }}" @if ($isRtl) dir="rtl" @endif>

@stack('css')

<body class="{{ $bodyClass ?? '' }}">

    @yield('content')

    @yield('script')
</body>

</html>
