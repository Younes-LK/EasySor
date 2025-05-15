<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'fa' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <link rel="preload" href="{{ asset('fonts/Vazirmatn-Regular.woff2') }}" as="font" type="font/woff2"
        crossorigin="anonymous">

    <link rel="icon" href="{{ asset('assets/images/logos/EasySor.png') }}" type="image/x-icon">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">



    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="{{ app()->getLocale() == 'fa' ? 'font-fa' : 'font-en' }}">


    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <!--include('layouts.navigation')-->

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>

</html>
