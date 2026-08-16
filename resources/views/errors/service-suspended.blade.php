<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ setting('site_title', 'global') }} - {{ __('Service unavailable') }}</title>
    <link rel="icon" href="{{ asset(setting('site_favicon', 'global')) }}" type="image/x-icon">
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; color: #1f1f1f; background: linear-gradient(145deg, #fffaf6, #ffeadb); }
        main { width: min(560px, 100%); padding: 42px 34px; text-align: center; background: rgba(255,255,255,.96); border: 1px solid #ffd8bd; border-radius: 26px; box-shadow: 0 24px 70px rgba(126,55,0,.12); }
        .mark { width: 76px; height: 76px; margin: 0 auto 22px; display: grid; place-items: center; border-radius: 50%; font-size: 34px; background: #fff0e5; color: #e66a12; }
        h1 { margin: 0 0 14px; font-size: clamp(1.7rem, 5vw, 2.35rem); }
        p { margin: 0; color: #61564f; font-size: 1.05rem; line-height: 1.7; }
        small { display: block; margin-top: 24px; color: #8a7c73; }
    </style>
</head>
<body>
    <main role="alert" aria-live="assertive">
        <div class="mark" aria-hidden="true">!</div>
        <h1>{{ __('Service temporarily unavailable') }}</h1>
        <p>{{ $suspensionMessage }}</p>
        <small>{{ __('Please try again later or contact the Developer.') }}</small>
    </main>
</body>
</html>
