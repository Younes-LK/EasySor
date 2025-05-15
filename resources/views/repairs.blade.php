@extends('layouts.base')
@if (Auth::check())
    @section('sidebar')
        @include('layouts.sidebar')
    @endsection
@endif
@section('content')
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">{{ __('خوش آمدید به ایزی‌سور!') }}</h1>
        <p class="text-lg text-gray-700 dark:text-gray-300">
            {{ __('این یک آزمایش است.') }}</p>

        <div class="mt-4">
            <p class="text-gray-700 dark:text-gray-300">{{ __('میتونی تغییرش بدی') }}</p>
        </div>
        <div class="mt-4">
            <p class="text-gray-700 dark:text-gray-300">{{ __('میتونی تغییرش بدی') }}</p>
        </div>
        <div class="mt-4">
            <p class="text-gray-700 dark:text-gray-300">{{ __('میتونی تغییرش بدی') }}</p>
        </div>
        <div class="mt-4">
            <p class="text-gray-700 dark:text-gray-300">{{ __('میتونی تغییرش بدی') }}</p>
        </div>
        <div class="mt-4">
            <p class="text-gray-700 dark:text-gray-300">{{ __('میتونی تغییرش بدی') }}</p>
        </div>
        <div class="mt-4">
            <p class="text-gray-700 dark:text-gray-300">{{ __('میتونی تغییرش بدی') }}</p>
        </div>
        <div class="mt-4">
            <p class="text-gray-700 dark:text-gray-300">{{ __('میتونی تغییرش بدی') }}</p>
        </div>
        <div class="mt-4">
            <p class="text-gray-700 dark:text-gray-300">{{ __('میتونی تغییرش بدی') }}</p>
        </div>
        <div class="mt-4">
            <p class="text-gray-700 dark:text-gray-300">{{ __('میتونی تغییرش بدی') }}</p>
        </div>
        <div class="mt-4">
            <p class="text-gray-700 dark:text-gray-300">{{ __('میتونی تغییرش بدی') }}</p>
        </div>
        <div class="mt-4">
            <p class="text-gray-700 dark:text-gray-300">{{ __('میتونی تغییرش بدی') }}</p>
        </div>
        <div class="mt-4">
            <p class="text-gray-700 dark:text-gray-300">{{ __('میتونی تغییرش بدی') }}</p>
        </div>
        <div class="mt-4">
            <p class="text-gray-700 dark:text-gray-300">{{ __('میتونی تغییرش بدی') }}</p>
        </div>
        <div class="mt-4">
            <p class="text-gray-700 dark:text-gray-300">{{ __('میتونی تغییرش بدی') }}</p>
        </div>
        <div class="mt-4">
            <p class="text-gray-700 dark:text-gray-300">{{ __('میتونی تغییرش بدی') }}</p>
        </div>

    </div>
@endsection
