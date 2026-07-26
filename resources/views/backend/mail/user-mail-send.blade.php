<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $details['title'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Bai+Jamjuree :wght@400;700&family=Roboto&display=swap"
        rel="stylesheet">
    <style>
        /* Google Font */
        @import url('https://fonts.googleapis.com/css2?family=Bai+Jamjuree:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;1,200;1,300;1,400;1,500;1,600;1,700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');

        /* Root Variables & Typography */
        :root {
            --font-base: 16px;
            --line-height-base: 1.6;
            --font-ratio: 1.25;
            --color-heading: #222223;
            --color-primary: #FDCC02;
            --color-body: #47494E;
            --color-white: #fff;
            --font-body: "Roboto", sans-serif;
            --font-heading: "Bai Jamjuree", sans-serif;
        }

        /* Reset Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Base Body Styles */
        body {
            font-family: var(--font-body);
            font-size: var(--font-base);
            line-height: calc(var(--font-base) * var(--line-height-base));
            color: var(--color-body);
            background-color: hsl(0deg 0% 95.28%);
        }

        /* Typography Styles */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            margin: 0 0 1rem 0;
            color: var(--font-heading);
            line-height: 1.2;
            word-break: break-word;
            font-weight: 700;
            color: var(--color-heading);
        }

        /* Headings */
        h1 {
            font-size: clamp(1.5rem, 5vw, 1.75rem);
            line-height: clamp(2rem, 6vw, 2.5rem);
        }

        h2 {
            font-size: clamp(1.25rem, 4.5vw, 1.5rem);
            line-height: clamp(1.8rem, 5.5vw, 2.125rem);
        }

        h3 {
            font-size: clamp(1rem, 4vw, 1.25rem);
            line-height: clamp(1.5rem, 5vw, 1.875rem);
        }

        h4 {
            font-size: clamp(0.9375rem, 3.5vw, 1.125rem);
            line-height: clamp(1.375rem, 4.5vw, 1.625rem);
        }

        h5 {
            font-size: clamp(0.875rem, 3vw, 1rem);
            line-height: clamp(1.25rem, 4vw, 1.5rem);
        }

        h6 {
            font-size: clamp(0.8125rem, 2.5vw, 0.875rem);
            line-height: clamp(1.125rem, 3.5vw, 1.25rem);
        }

        /* Paragraph */
        p {
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 16px;
            text-align: left;
        }

        a {
            word-wrap: break-word;
            overflow-wrap: break-word;
            color: #444344;
        }

        a:hover {
            text-decoration: none;
        }

        /* Small Text */
        small {
            font-size: 14px;
            line-height: 22px;
        }

        img {
            max-width: 100%;
            object-fit: cover;
        }

        .email-container {
            max-width: 640px;
            background-color: var(--color-white);
            margin: 15px auto;
            padding: 0 30px;
        }

        /* Header */
        .header {
            padding: 20px 0px 20px;
            position: relative;
        }

        .header img {
            height: 28px;
        }

        /* Hero css */
        .hero {
            padding: 0px 0px 20px;
            position: relative;
        }

        .hero h1 span {
            color: #FDCC02;
        }

        .hero .thumb {
            margin-bottom: 20px;
        }

        .hero .thumb img {
            width: 100%;
        }

        /* Features list */
        .features {
            padding: 0px 0px 30px;
        }

        ul li {
            list-style-type: disc;
            margin-left: 20px;
            margin-bottom: 14px;
        }

        ul li:last-child {
            margin-bottom: 0;
        }

        .thanks-contents {
            padding: 0px 0px 10px;
            position: relative;
            margin-bottom: 30px;
        }

        .thanks-contents span {
            display: block;
            color: #9A9DA7;
        }

        .primary-button {
            padding: 0 16px;
            position: relative;
            z-index: 1;
            transition: all 0.4s ease-in-out;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 50px;
            font-size: 14px;
            font-weight: 700;
            gap: 8px;
            background: #FDCC02;
            border-radius: 8px;
            color: var(--color-heading);
            text-decoration: none;
        }

        .btn-inner {
            margin-top: 30px;
        }

        /* Footer Styles */
        .footer {
            text-align: center;
            padding: 0px 0px 30px;
        }

        .footer p {
            max-width: 100%;
            margin: 15px auto 0px;
            text-align: center;
        }

        /* Footer Links */
        .footer-links {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.75rem 1rem;
        }

        .footer-links a {
            display: inline-flex;
            align-items: center;
            text-decoration: underline;
            font-size: 16px;
            color: #444344;
        }

        .footer-links a svg:hover {
            stroke: var(--color-primary);
        }

        .footer-links a:hover {
            text-decoration: none;
        }

        /* Social Icons */
        .social-icons {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 0 auto;
            margin-top: 16px;
            margin-bottom: 16px;
        }

        .social-icons a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            color: #4a5cff;
            transition: color 0.3s ease;
        }

        .social-icons a svg *:hover {
            color: var(--color-primary);
        }

        /* responsive css */
        @media (max-width: 480px) {
            .email-container {
                padding: 0 16px;
            }

            .features-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <a href="{{ $details['site_link'] }}">
                <img src="{{ asset(setting('site_dark_logo', 'global')) }}" alt="Logo">
            </a>
        </div>

        <!-- Hero -->
        <div class="hero">
            <h1>{{ __('Welcome to') }} <span>{{ $details['site_title'] }}</span></h1>
            <p>{{ $details['salutation'] }}</p>
            <p>{!! $details['email_body'] !!}</p>
        </div>

        <!-- CTA Button -->
        @if ($details['button_link'])
            <div class="thanks-contents">
                <a href="{{ $details['button_link'] }}" class="primary-button">{{ $details['button_level'] }}</a>
            </div>
        @endif
        <div class="hero">
            <strong>
                {!! $details['footer_body'] !!}
            </strong>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>{{ __('This email was sent to') }} <a
                    href="mailto:{{ $details['email'] ?? 'user@example.com' }}">{{ $details['email'] ?? 'user@example.com' }}</a>.
            </p>
            <div class="footer-links">
                <a href="{{ route('home') }}">{{ __('Home') }}</a>
                <a href="{{ route('page', 'page/terms') }}">{{ __('Terms and Conditions') }}</a>
                <a href="{{ route('page', 'page/privacy-policy') }}">{{ __('Privacy Policy') }}</a>
            </div>
            <div class="footer-copyright">
                &copy; {{ date('Y') }} {{ $details['site_title'] }}.
            </div>
        </div>
    </div>
</body>

</html>
