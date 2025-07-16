<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'fa' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>

    <link rel="icon" href="{{ asset('assets/images/logos/EasySor.png') }}" type="image/x-icon">

    <!-- CSS already handled in app.css -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Preload custom font (Vazirmatn) for faster loading -->
    <link rel="preload" href="{{ asset('fonts/Vazirmatn-Regular.woff2') }}" as="font" type="font/woff2"
        crossorigin="anonymous">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

@yield('sidebar')

<body
    class="min-h-screen flex flex-col {{ app()->getLocale() == 'fa' ? 'font-fa' : 'font-en' }} bg-light dark:bg-dark dark:text-white">
    @include('layouts.navbar')

    <!-- Main Content -->
    <div class="flex-1 pt-14 md:pt-20 px-4 md:px-16 lg:px-64 pb-16 lg:pb-0"> <!-- Added padding and spacing -->
        <main>
            @yield('content')
        </main>
    </div>

    @include('components.confirm-logout-modal')

    <!-- Footer
    <footer class="mt-4">
        include('layouts.footer')
    </footer>
    -->

</body>

</html>
