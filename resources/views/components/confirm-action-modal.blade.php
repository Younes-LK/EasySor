@props([
    'name', // Unique name for this modal instance
    'title' => 'تایید عملیات',
    'confirmButtonText' => 'تایید',
    'cancelButtonText' => 'انصراف',
    'confirmButtonClass' => 'bg-green-600 hover:bg-green-700 focus:ring-green-500',
    'actionFormId' => null, // Will be set by JS to the ID of the form to submit
])

<x-modal :name="$name" focusable>
    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl" x-data="{ formIdToSubmit: '{{ $actionFormId }}' }">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ $title }}
        </h2>

        <div class="mt-2">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $slot }} {{-- This is where the specific confirmation message will go --}}
            </p>
        </div>

        <div class="mt-6 flex justify-end space-x-3 rtl:space-x-reverse">
            <x-secondary-button x-on:click="$dispatch('close')">
                {{ $cancelButtonText }}
            </x-secondary-button>

            <button type="button"
                x-on:click="if (formIdToSubmit) { document.getElementById(formIdToSubmit).submit(); } $dispatch('close');"
                class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150 {{ $confirmButtonClass }}">
                {{ $confirmButtonText }}
            </button>
        </div>
    </div>
</x-modal>
