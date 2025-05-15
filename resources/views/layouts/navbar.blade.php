<header
    class="fixed inset-x-0 top-0 z-30 mx-auto w-full max-w-screen-md border border-gray-100 bg-gray-300 py-3 shadow backdrop-blur-lg md:top-6 md:rounded-3xl lg:max-w-screen-lg dark:bg-gray-600">
    <div class="px-4">
        <div class="flex items-center justify-between">
            <!-- Buttons -->
            <div class="flex items-center gap-3">
                @auth

                    <a href="{{ route('profile.edit') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600">
                        مدیریت حساب
                    </a>

                    <!-- فقط دکمه بدون فرم -->
                    <button type="button" x-data x-on:click="$dispatch('open-modal', 'confirm-logout')"
                        class="inline-flex items-center justify-center rounded-xl bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-600">
                        خروج
                    </button>



                    @if (Auth::user()->role === 'admin')
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center justify-center rounded-xl bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-600">
                            ایجاد کاربر
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600">
                        ورود
                    </a>
                @endauth
            </div>

            <!-- Logo -->
            <div class="flex shrink-0">
                <a class="flex items-center" href="/">
                    <img class="h-7 w-auto" src="{{ asset('assets/images/logos/EasySor.png') }}" alt="Logo">
                    <p class="sr-only">EasySor</p>
                </a>
            </div>
        </div>
    </div>

</header>
