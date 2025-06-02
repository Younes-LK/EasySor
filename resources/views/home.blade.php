@extends('layouts.base')

@if (Auth::check())
    @section('sidebar')
        @include('layouts.sidebar')
    @endsection
@endif

@section('content')
    <div class="container mx-auto px-4 py-6">

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 dark:bg-green-700 text-green-700 dark:text-green-100 rounded-md">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->has('general'))
            <div class="mb-4 p-3 bg-red-100 dark:bg-red-700 text-red-700 dark:text-red-100 rounded-md">
                {{ $errors->first('general') }}
            </div>
        @endif

        @guest
            {{-- Guest View: Introduction to EasySor --}}
            <div class="text-center py-12">
                <img src="{{ asset('assets/images/logos/EasySor.png') }}" alt="EasySor Logo" class="w-32 h-32 mx-auto mb-6">
                <h1 class="text-4xl font-bold mb-4 text-gray-800 dark:text-white">به ایزی‌سور خوش آمدید!</h1>
                <p class="text-lg text-gray-600 dark:text-gray-300 mb-8 max-w-2xl mx-auto">
                    ایزی‌سور راهکاری جامع برای مدیریت شرکت‌های خدماتی آسانسور است. به راحتی مشتریان، قراردادها، سرویس‌های
                    دوره‌ای و تعمیرات خود را مدیریت کنید.
                </p>
                <div class="space-x-4 rtl:space-x-reverse">
                    <a href="{{ route('login') }}"
                        class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition duration-150">
                        ورود به حساب کاربری
                    </a>
                </div>
            </div>

            <div class="mt-12 grid md:grid-cols-3 gap-8 text-right">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-xl font-semibold text-indigo-600 dark:text-indigo-400 mb-3">مدیریت مشتریان</h3>
                    <p class="text-gray-600 dark:text-gray-300">اطلاعات کامل مشتریان و آدرس‌های آن‌ها را ثبت و نگهداری کنید.</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-xl font-semibold text-indigo-600 dark:text-indigo-400 mb-3">قراردادهای نصب</h3>
                    <p class="text-gray-600 dark:text-gray-300">جزئیات قراردادهای نصب آسانسور، تجهیزات مورد نیاز و پرداخت‌ها را
                        مدیریت کنید.</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                    <h3 class="text-xl font-semibold text-indigo-600 dark:text-indigo-400 mb-3">سرویس‌های دوره‌ای</h3>
                    <p class="text-gray-600 dark:text-gray-300">برنامه‌ریزی و پیگیری سرویس‌های دوره‌ای، ثبت گزارشات و مدیریت
                        پرداخت‌های مربوطه.</p>
                </div>
            </div>
        @endguest

        @auth
            {{-- Logged-in User View: Dashboard --}}
            <h1 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white">داشبورد وظایف شما</h1>

            {{-- Pending Contracts (نصب) --}}
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-3 text-indigo-700 dark:text-indigo-300 border-b-2 border-indigo-500 pb-2">
                    قراردادهای نصب</h2>
                @if ($pendingContracts->isNotEmpty())
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($pendingContracts as $contract)
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow hover:shadow-lg transition-shadow">
                                <h3 class="font-semibold text-lg text-gray-900 dark:text-white">
                                    {{ $contract->customer->name ?? 'N/A' }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">آدرس:
                                    {{ $contract->address->address ?? 'N/A' }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">توضیحات:
                                    {{ Str::words($contract->description ?? '', 10, '...') }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-300 mt-1">تکنسین:
                                    {{ $contract->assignedUser->name ?? 'مشخص نشده' }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">تاریخ ثبت:
                                    {{ $contract->formatted_created_at ?? 'N/A' }}</p>
                                <div class="mt-3 flex justify-between items-center">
                                    <a href="{{ route('contracts.edit', $contract->id) }}"
                                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">مشاهده/ویرایش
                                        جزئیات</a>
                                    @if (Auth::user()->isAdmin() || (Auth::user()->isStaff() && $contract->assigned_to == Auth::id()))
                                        {{-- MODIFIED: Button to trigger modal --}}
                                        <button type="button" x-data=""
                                            x-on:click.prevent="$dispatch('open-modal', 'confirm-contract-done-{{ $contract->id }}')"
                                            class="px-3 py-1.5 text-xs bg-cyan-500 text-white rounded-md hover:bg-cyan-600">
                                            ثبت انجام قرارداد
                                        </button>
                                        {{-- Hidden form for this specific contract --}}
                                        <form id="contract-done-form-{{ $contract->id }}"
                                            action="{{ route('home.contract.done', $contract->id) }}" method="POST"
                                            class="hidden">
                                            @csrf
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-600 dark:text-gray-400">در حال حاضر قرارداد نصب فعالی برای شما وجود ندارد.</p>
                @endif
            </div>

            {{-- Due Maintenances (سرویس‌ها) --}}
            <div>
                <h2 class="text-xl font-semibold mb-3 text-teal-700 dark:text-teal-300 border-b-2 border-teal-500 pb-2">
                    سرویس‌های دوره‌ای</h2>
                @if ($dueMaintenances->isNotEmpty())
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($dueMaintenances as $maintenance)
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow hover:shadow-lg transition-shadow">
                                <h3 class="font-semibold text-lg text-gray-900 dark:text-white">
                                    {{ $maintenance->customer->name ?? 'N/A' }}</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">آدرس:
                                    {{ $maintenance->address->address ?? 'N/A' }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-300 mt-1">تکنسین:
                                    {{ $maintenance->user->name ?? 'مشخص نشده' }}</p>
                                <p class="text-sm text-orange-600 dark:text-orange-400 font-medium mt-1">
                                    موعد سرویس بعدی: {{ $maintenance->next_due_display ?? 'نیاز به بررسی' }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    شروع سرویس: {{ $maintenance->formatted_start_date ?? 'N/A' }} |
                                    انجام شده: {{ $maintenance->completed_count }}/{{ $maintenance->duration_in_months }}
                                </p>
                                <div class="mt-3 flex justify-between items-center">
                                    <a href="{{ route('maintenances.edit', $maintenance->id) }}"
                                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">مشاهده/ویرایش
                                        جزئیات</a>
                                    @if (Auth::user()->isAdmin() || (Auth::user()->isStaff() && $maintenance->assigned_to == Auth::id()))
                                        {{-- MODIFIED: Button to trigger modal --}}
                                        <button type="button" x-data=""
                                            x-on:click.prevent="$dispatch('open-modal', 'confirm-maintenance-done-{{ $maintenance->id }}')"
                                            class="px-3 py-1.5 text-xs bg-green-500 text-white rounded-md hover:bg-green-600">
                                            ثبت انجام سرویس
                                        </button>
                                        {{-- Hidden form for this specific maintenance --}}
                                        <form id="maintenance-done-form-{{ $maintenance->id }}"
                                            action="{{ route('home.maintenance.done', $maintenance->id) }}" method="POST"
                                            class="hidden">
                                            @csrf
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-600 dark:text-gray-400">در حال حاضر سرویس دوره‌ای سررسید شده‌ای برای شما وجود ندارد.</p>
                @endif
            </div>


            {{-- MODAL DEFINITIONS --}}
            {{-- Confirmation Modal for Contracts --}}
            @foreach ($pendingContracts as $contract)
                <x-confirm-action-modal name="confirm-contract-done-{{ $contract->id }}" title="تایید انجام قرارداد"
                    confirmButtonText="تایید و تکمیل" confirmButtonClass="bg-cyan-600 hover:bg-cyan-700 focus:ring-cyan-500"
                    actionFormId="contract-done-form-{{ $contract->id }}" {{-- This will be set by JS if needed, or directly if modal is unique enough --}}>
                    آیا از تکمیل این قرارداد ({{ $contract->customer->name ?? 'N/A' }}) مطمئن هستید؟ وضعیت به "تکمیل شده" تغییر
                    خواهد کرد.
                </x-confirm-action-modal>
            @endforeach

            {{-- Confirmation Modal for Maintenances --}}
            @foreach ($dueMaintenances as $maintenance)
                <x-confirm-action-modal name="confirm-maintenance-done-{{ $maintenance->id }}"
                    title="تایید انجام سرویس دوره‌ای" confirmButtonText="تایید و ثبت گزارش"
                    confirmButtonClass="bg-green-600 hover:bg-green-700 focus:ring-green-500"
                    actionFormId="maintenance-done-form-{{ $maintenance->id }}">
                    آیا از ثبت انجام سرویس برای این ماه برای مشتری ({{ $maintenance->customer->name ?? 'N/A' }}) مطمئن هستید؟
                    یک گزارش با تاریخ امروز و جزئیات پیش‌فرض ثبت خواهد شد.
                </x-confirm-action-modal>
            @endforeach

        @endauth
    </div>

    {{-- Alpine.js script to handle setting the form ID for the modal dynamically if needed --}}
    {{-- However, with unique modal names and form IDs, the x-on:click on the modal's confirm button can directly submit the correct form. --}}
    {{-- The `actionFormId` prop on the component is now directly setting the `formIdToSubmit` in its Alpine scope. --}}
@endsection
