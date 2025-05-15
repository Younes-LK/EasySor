<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'fa' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Preload custom font (Vazirmatn) for faster loading -->
    <link rel="preload" href="{{ asset('fonts/Vazirmatn-Regular.woff2') }}" as="font" type="font/woff2"
        crossorigin="anonymous">

    <link rel="icon" href="{{ asset('assets/images/logos/EasySor.png') }}" type="image/x-icon">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="min-h-screen {{ app()->getLocale() == 'fa' ? 'font-fa' : 'font-en' }} bg-light dark:bg-dark dark:text-white overflow-hidden">
    <div class="min-h-screen flex flex-col sm:justify-center items-center bg-gray-100 dark:bg-dark pt-6 sm:pt-0">
        <div>
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500 dark:text-white" />
            </a>
        </div>

        <!-- Updated the background color of the form container -->
        <div
            class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
            {{ $slot }}
        </div>
    </div>
</body>

</html>
