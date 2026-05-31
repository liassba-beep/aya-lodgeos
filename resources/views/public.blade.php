<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $website = data_get($page, 'props.website', []);
            $property = data_get($page, 'props.property', []);
            $title = data_get($website, 'title') ?: data_get($property, 'name', '');
            $description = data_get($website, 'description');
            $canonical = data_get($website, 'canonical_url');
            $ogImage = data_get($website, 'og_image');
            $favicon = data_get($website, 'favicon');
            $jsonLd = data_get($website, 'json_ld');
        @endphp
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0c0a09">

        <title inertia>{{ $title }}</title>
        @if ($description)
            <meta name="description" content="{{ $description }}">
            <meta property="og:description" content="{{ $description }}">
            <meta name="twitter:description" content="{{ $description }}">
        @endif
        @if ($canonical)
            <link rel="canonical" href="{{ $canonical }}">
            <meta property="og:url" content="{{ $canonical }}">
        @endif
        @if ($favicon)
            <link rel="icon" href="{{ $favicon }}">
        @endif
        <meta property="og:type" content="business.business">
        <meta property="og:locale" content="pt_PT">
        <meta property="og:title" content="{{ $title }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $title }}">
        @if ($ogImage)
            <meta property="og:image" content="{{ $ogImage }}">
            <meta name="twitter:image" content="{{ $ogImage }}">
        @endif
        @if ($jsonLd)
            <script type="application/ld+json">
                {!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
            </script>
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @viteReactRefresh
        @vite(['resources/js/public.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
