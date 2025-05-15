<x-modal name="confirm-logout" focusable>
    <form method="POST" action="{{ route('logout') }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
        @csrf
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('آیا مطمئن هستید که می‌خواهید خارج شوید؟') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('با تأیید، حساب کاربری شما از این دستگاه خارج خواهد شد.') }}
        </p>

        <div class="mt-6 flex justify-end">
            <x-secondary-button x-on:click="$dispatch('close')">
                {{ __('انصراف') }}
            </x-secondary-button>
            <x-danger-button class="ms-3">
                {{ __('خروج') }}
            </x-danger-button>
        </div>
    </form>
</x-modal>
