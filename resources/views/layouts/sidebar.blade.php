<!-- Desktop Sidebar -->
<aside
    class="hidden lg:block fixed right-8 top-1/2 -translate-y-1/2 z-40 w-52 rounded-xl border border-gray-200 dark:border-gray-700 shadow-lg backdrop-blur-lg bg-gray-300 dark:bg-gray-800">
    <div class="p-4 flex flex-col gap-3">
        <a href="{{ route('home') }}"
            class="flex items-center gap-2 font-semibold rounded-md px-3 py-2 text-gray-800 dark:text-white hover:bg-indigo-100 dark:hover:bg-indigo-900 {{ Route::is('home') ? 'bg-gray-400 dark:bg-gray-950' : '' }}">
            <img src="{{ asset('assets/images/icons/home.ico') }}" alt="Icon" class="w-5 h-5">
            خانه
        </a>

        @if (Auth::user()->role === 'admin')
            <a href="{{ route('users.index') }}"
                class="flex items-center gap-2 font-semibold rounded-md px-3 py-2 text-gray-800 dark:text-white hover:bg-indigo-100 dark:hover:bg-indigo-900 {{ Route::is('users.*') ? 'bg-gray-400 dark:bg-gray-950' : '' }}">
                <img src="{{ asset('assets/images/icons/users.ico') }}" alt="Icon" class="w-5 h-5">
                کاربران
            </a>
            <a href="{{ route('contracts.index') }}"
                class="flex items-center gap-2 font-semibold rounded-md px-3 py-2 text-gray-800 dark:text-white hover:bg-indigo-100 dark:hover:bg-indigo-900 {{ Route::is('contracts.*') ? 'bg-gray-400 dark:bg-gray-950' : '' }}">
                <img src="{{ asset('assets/images/icons/contracts.ico') }}" alt="Icon" class="w-5 h-5">
                قراردادها
            </a>
            </a>
            <a href="{{ route('invoices.index') }}"
                class="flex items-center gap-2 font-semibold rounded-md px-3 py-2 text-gray-800 dark:text-white hover:bg-indigo-100 dark:hover:bg-indigo-900 {{ Route::is('invoices.*') ? 'bg-gray-400 dark:bg-gray-950' : '' }}">
                <img src="{{ asset('assets/images/icons/invoice.ico') }}" alt="Icon" class="w-5 h-5">
                فاکتور
            </a>
            <a href="{{ route('equipments.index') }}"
                class="flex items-center gap-2 font-semibold rounded-md px-3 py-2 text-gray-800 dark:text-white hover:bg-indigo-100 dark:hover:bg-indigo-900 {{ Route::is('equipments.*') ? 'bg-gray-400 dark:bg-gray-950' : '' }}">
                <img src="{{ asset('assets/images/icons/equipments.ico') }}" alt="Icon" class="w-5 h-5">
                تجهیزات
            </a>
            <a href="{{ route('customers.index') }}"
                class="flex items-center gap-2 font-semibold rounded-md px-3 py-2 text-gray-800 dark:text-white hover:bg-indigo-100 dark:hover:bg-indigo-900 {{ Route::is('customers.*') ? 'bg-gray-400 dark:bg-gray-950' : '' }}">
                <img src="{{ asset('assets/images/icons/customers.ico') }}" alt="Icon" class="w-5 h-5">
                مشتریان
            </a>
        @endif


        <a href="{{ route('maintenances.index') }}"
            class="flex items-center gap-2 font-semibold rounded-md px-3 py-2 text-gray-800 dark:text-white hover:bg-indigo-100 dark:hover:bg-indigo-900 {{ Route::is('maintenances.*') ? 'bg-gray-400 dark:bg-gray-950' : '' }}">
            <img src="{{ asset('assets/images/icons/services.ico') }}" alt="Icon" class="w-5 h-5">
            سرویس‌ها
        </a>
        <a href="{{ route('repairs.index') }}"
            class="flex items-center gap-2 font-semibold rounded-md px-3 py-2 text-gray-800 dark:text-white hover:bg-indigo-100 dark:hover:bg-indigo-900 {{ Route::is('repairs.*') ? 'bg-gray-400 dark:bg-gray-950' : '' }}">
            <img src="{{ asset('assets/images/icons/repairs.ico') }}" alt="Icon" class="w-5 h-5">
            تعمیرات
        </a>
    </div>
</aside>

<!-- Mobile Bottom Bar -->
<aside
    class="block lg:hidden fixed bottom-0 left-0 right-0 z-50 border-t border-gray-200 dark:border-gray-700 shadow-inner bg-gray-300 dark:bg-gray-800">
    <div class="flex justify-around items-center p-2">
        <a href="{{ route('home') }}"
            class="flex flex-col items-center justify-center w-16 text-center text-xs font-semibold text-gray-700 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 {{ Route::is('home') ? 'bg-gray-400 dark:bg-gray-950 rounded-md px-2 py-1' : '' }}">
            <img src="{{ asset('assets/images/icons/home.ico') }}" alt="Icon" class="w-6 h-6 mb-1">
            <span class="truncate">خانه</span>
        </a>

        @if (Auth::user()->role === 'admin')
            <a href="{{ route('users.index') }}"
                class="flex flex-col items-center justify-center w-16 text-center text-xs font-semibold text-gray-700 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 {{ Route::is('users.*') ? 'bg-gray-400 dark:bg-gray-950 rounded-md px-2 py-1' : '' }}">
                <img src="{{ asset('assets/images/icons/users.ico') }}" alt="Icon" class="w-6 h-6 mb-1">
                <span class="truncate">کاربران</span>
            </a>

            <a href="{{ route('contracts.index') }}"
                class="flex flex-col items-center justify-center w-16 text-center text-xs font-semibold text-gray-700 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 {{ Route::is('contracts.*') ? 'bg-gray-400 dark:bg-gray-950 rounded-md px-2 py-1' : '' }}">
                <img src="{{ asset('assets/images/icons/contracts.ico') }}" alt="Icon" class="w-6 h-6 mb-1">
                <span class="truncate">قراردادها</span>
            </a>

            <a href="{{ route('invoices.index') }}"
                class="flex flex-col items-center justify-center w-16 text-center text-xs font-semibold text-gray-700 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 {{ Route::is('invoices.*') ? 'bg-gray-400 dark:bg-gray-950 rounded-md px-2 py-1' : '' }}">
                <img src="{{ asset('assets/images/icons/invoice.ico') }}" alt="Icon" class="w-6 h-6 mb-1">
                <span class="truncate">فاکتور</span>
            </a>

            <a href="{{ route('equipments.index') }}"
                class="flex flex-col items-center justify-center w-16 text-center text-xs font-semibold text-gray-700 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 {{ Route::is('equipments.*') ? 'bg-gray-400 dark:bg-gray-950 rounded-md px-2 py-1' : '' }}">
                <img src="{{ asset('assets/images/icons/equipments.ico') }}" alt="Icon" class="w-6 h-6 mb-1">
                <span class="truncate">تجهیزات</span>
            </a>
        @endif

        <a href="{{ route('customers.index') }}"
            class="flex flex-col items-center justify-center w-16 text-center text-xs font-semibold text-gray-700 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 {{ Route::is('customers.*') ? 'bg-gray-400 dark:bg-gray-950 rounded-md px-2 py-1' : '' }}">
            <img src="{{ asset('assets/images/icons/customers.ico') }}" alt="Icon" class="w-6 h-6 mb-1">
            <span class="truncate">مشتریان</span>
        </a>

        <a href="{{ route('maintenances.index') }}"
            class="flex flex-col items-center justify-center w-16 text-center text-xs font-semibold text-gray-700 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 {{ Route::is('maintenances.*') ? 'bg-gray-400 dark:bg-gray-950 rounded-md px-2 py-1' : '' }}">
            <img src="{{ asset('assets/images/icons/services.ico') }}" alt="Icon" class="w-6 h-6 mb-1">
            <span class="truncate">سرویس‌ها</span>
        </a>

        <a href="{{ route('repairs.index') }}"
            class="flex flex-col items-center justify-center w-16 text-center text-xs font-semibold text-gray-700 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 {{ Route::is('repairs.*') ? 'bg-gray-400 dark:bg-gray-950 rounded-md px-2 py-1' : '' }}">
            <img src="{{ asset('assets/images/icons/repairs.ico') }}" alt="Icon" class="w-6 h-6 mb-1">
            <span class="truncate">تعمیرات</span>
        </a>
    </div>
</aside>
