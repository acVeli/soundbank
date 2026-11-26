<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#020408">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name') }}</title>
    <meta name="description" content="@yield('description', 'Samples, voix, kicks, hats, basses et packs vendus directement par les artistes. Licence libre de droits.')">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect x='6' y='6' width='20' height='20' transform='rotate(45 16 16)' fill='none' stroke='%235ce1ff' stroke-width='2.5'/%3E%3Crect x='13' y='13' width='6' height='6' fill='%235ce1ff'/%3E%3C/svg%3E">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="@yield('body-class')">
    @yield('body')

    @include('partials.player')
</body>
</html>
