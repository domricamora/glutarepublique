<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="color-scheme: light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {{-- Light only: no dark mode, whatever the device prefers. --}}
        <meta name="color-scheme" content="light">

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #ffffff;
            }

            html.dark {
                background-color: #1b2a36;
            }
        </style>

        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            {{-- Written on the server so crawlers that do not run JavaScript (most AI
                 answer engines) still read each page (App\View\Composers\SeoComposer).
                 data-inertia keys let the page's own <Head> replace them in a browser. --}}
            <title>{{ $seo['title'] ?? config('app.name', 'Laravel') }}</title>
            @if (! empty($seo['noindex']))
                <meta name="robots" content="noindex, nofollow" data-inertia="seo-robots">
            @elseif (! empty($seo['title']))
                @if (! empty($seo['description']))
                    <meta name="description" content="{{ $seo['description'] }}" data-inertia="seo-description">
                @endif
                <meta property="og:type" content="{{ $seo['type'] }}" data-inertia="seo-og-type">
                <meta property="og:site_name" content="{{ $seo['site_name'] }}" data-inertia="seo-og-site">
                <meta property="og:title" content="{{ $seo['title'] }}" data-inertia="seo-og-title">
                <meta property="og:description" content="{{ $seo['description'] ?? '' }}" data-inertia="seo-og-description">
                <meta property="og:url" content="{{ $seo['canonical'] }}" data-inertia="seo-og-url">
                <meta property="og:image" content="{{ $seo['image'] }}" data-inertia="seo-og-image">
                <meta property="og:locale" content="en_PH" data-inertia="seo-og-locale">
                <meta name="twitter:card" content="summary_large_image" data-inertia="seo-twitter-card">
            @endif
        </x-inertia::head>
        {{-- Outside the head manager so it stays put once the page has loaded. --}}
        @if (! empty($seo['canonical']))
            <link rel="canonical" href="{{ $seo['canonical'] }}">
        @endif
        <link rel="alternate" type="text/plain" title="LLM summary" href="{{ url('llms.txt') }}">
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
        @if (! empty($seo['body']))
            {{-- The page's facts as plain text, the same content the page shows. --}}
            <noscript>{!! $seo['body'] !!}</noscript>
        @endif
        @foreach (array_filter($seo['jsonld'] ?? []) as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
        @endforeach
    </body>
</html>
