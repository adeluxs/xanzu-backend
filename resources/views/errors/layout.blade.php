<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>{{ setting('site_title', 'global') }} - @stack('title')</title>

    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" href="{{ asset(setting('site_favicon', 'global')) }}" type="image/x-icon" />

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <style type="text/tailwindcss">
        @theme {
            --color-grayish: #2f3542;
        }
    </style>

    @stack('style')
</head>

@inject('response', 'Illuminate\Http\Response')

<body>
    <main
        class="relative min-h-screen w-full flex items-center justify-center overflow-hidden bg-cover bg-center bg-no-repeat"
        style="background-image: url('{{ themeAsset('/images/error/error-page-bg.jpg') }}');">

        <div class="relative z-10 flex flex-col items-center justify-center w-full max-w-2xl mx-auto px-6">
            <div class="relative w-full flex justify-center items-center">

                <div class="w-[280px] sm:w-[500px] md:w-[660px] h-[450px]">
                    <img src="{{ themeAsset('/images/error/page-not-found-img.png') }}"
                        alt="{{ __('Error illustration') }}" width="660" height="450"
                        class="w-full h-full object-contain" draggable="false" />
                </div>

                <div
                    class="absolute flex flex-col items-center justify-center gap-1 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">

                    <div class="mt-[-70px] sm:mt-[-100px] md:mt-[-150px] mr-4 sm:mr-18 md:mr-16">
                        <h1
                            class="font-bold text-2xl sm:text-[40px] leading-none tracking-tight text-grayish sm:mb-1 mb-0 text-center">
                            {{ $exception->getStatusCode() ?? 404 }}
                        </h1>

                        <p class="text-grayish/60 text-base font-medium text-center">
                            @stack('title')
                        </p>

                        <div class="mt-2 sm:mt-7.5 text-center">
                            <a href="{{ route('home') }}"
                                class="h-[28px] sm:h-[36px] px-4 rounded-md bg-grayish text-white text-sm font-medium hover:opacity-90 transition inline-flex items-center justify-center">
                                {{ __('Back Home') }}
                            </a>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </main>
</body>

</html>
