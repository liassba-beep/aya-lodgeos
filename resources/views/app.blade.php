<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#09090b">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="application-name" content="AYA LodgeOS">
        <meta name="apple-mobile-web-app-title" content="AYA LodgeOS">
        <link rel="manifest" href="{{ request()->is('trabalhador*') ? '/worker-manifest.webmanifest' : '/manifest.webmanifest' }}">
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="192x192" href="/icons/android-chrome-192x192.png">

        <title inertia>{{ config('app.name') === 'Laravel' ? 'AYA LodgeOS' : config('app.name', 'AYA LodgeOS') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
