<footer
    class="mx-auto w-full max-w-container px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-600 {{ Auth::check() ? 'pb-20 lg:pb-0' : 'pb-0' }}">
    <div class="border-t border-slate-200 dark:border-slate-700 py-2">
        <div
            class="flex flex-col sm:flex-row items-center justify-between sm:space-x-6 space-y-4 sm:space-y-0 text-sm text-slate-700 dark:text-slate-300 text-center sm:text-left">

            <!-- Logo -->
            <a href="#"
                class="flex items-center justify-center sm:justify-start text-2xl font-semibold text-gray-900 dark:text-white">
                <img src="{{ asset('assets/images/logos/EasySor.png') }}" class="h-8 mr-2" alt="EasySor logo">
                EasySor
            </a>

            <!-- Middle Text -->
            <span class="text-slate-500 dark:text-slate-400">
                توسعه توسط Younes LK
            </span>

            <!-- تماس با ما -->
            <a href="{{ route('contact') }}"
                class="hover:text-blue-600 dark:hover:text-blue-400 transition font-semibold">
                تماس با ما
            </a>
        </div>
    </div>
</footer>
