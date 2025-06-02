@extends('layouts.base')

@section('sidebar')
    @include('layouts.sidebar')
@endsection

@section('content')
    <div class="container mx-auto px-4 py-6">
        <h2 class="text-xl font-bold mb-6 dark:text-white">ویرایش کاربر: {{ $user->name }}</h2>

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-700 text-red-700 dark:text-red-100 rounded-md">
                <strong class="font-bold">خطا در ورودی!</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <form method="POST" action="{{ route('users.update', $user->id) }}">
                @csrf
                @method('PUT')

                <div class="grid md:grid-cols-2 gap-6">
                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">نام و نام
                            خانوادگی</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-200">شماره
                            تلفن</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('phone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- National Code --}}
                    <div>
                        <label for="national_code" class="block text-sm font-medium text-gray-700 dark:text-gray-200">کد
                            ملی</label>
                        <input type="text" name="national_code" id="national_code"
                            value="{{ old('national_code', $user->national_code) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('national_code')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Role --}}
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-200">نقش</label>
                        <select name="role" id="role" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            {{ $user->id === Auth::id() && $user->isAdmin() && \App\Models\User::where('role', 'admin')->count() <= 1 ? 'disabled' : '' }}>
                            <option value="staff" {{ old('role', $user->role) == 'staff' ? 'selected' : '' }}>کارمند
                            </option>
                            <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>مدیر
                            </option>
                        </select>
                        @if ($user->id === Auth::id() && $user->isAdmin() && \App\Models\User::where('role', 'admin')->count() <= 1)
                            <input type="hidden" name="role" value="{{ $user->role }}">
                            <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">نقش تنها مدیر سیستم قابل تغییر
                                نیست.</p>
                        @endif
                        @error('role')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">برای تغییر رمز عبور، فیلدهای زیر را پر
                            کنید. در غیر این صورت، خالی بگذارید.</p>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-200">رمز عبور
                            جدید</label>
                        <input type="password" name="password" id="password"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password Confirmation --}}
                    <div>
                        <label for="password_confirmation"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-200">تکرار رمز عبور جدید</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    {{-- Is Active --}}
                    <div class="md:col-span-2">
                        <div class="flex items-center mt-2">
                            <input id="is_active" name="is_active" type="checkbox" value="1"
                                {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                class="h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded focus:ring-indigo-500"
                                {{ $user->id === Auth::id() && $user->isAdmin() && \App\Models\User::where('role', 'admin')->where('is_active', true)->count() <= 1 ? 'disabled' : '' }}>
                            <label for="is_active" class="ms-2 block text-sm text-gray-900 dark:text-gray-200">کاربر فعال
                                باشد</label>
                        </div>
                        @if (
                            $user->id === Auth::id() &&
                                $user->isAdmin() &&
                                \App\Models\User::where('role', 'admin')->where('is_active', true)->count() <= 1)
                            <input type="hidden" name="is_active" value="1"> {{-- Keep it active --}}
                            <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">وضعیت تنها مدیر فعال سیستم قابل
                                تغییر نیست.</p>
                        @endif
                        @error('is_active')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <a href="{{ route('users.index') }}"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        انصراف
                    </a>
                    <button type="submit"
                        class="ms-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        بروزرسانی کاربر
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
