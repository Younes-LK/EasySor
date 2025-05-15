@props(['disabled' => false])

<input @disabled($disabled)
    {{ $attributes->merge([
        'class' => '
                                border-gray-300
                                focus:border-indigo-500
                                focus:ring-indigo-500
                                rounded-md
                                shadow-sm
                                text-gray-900
                                dark:border-gray-600
                                dark:bg-dark
                                dark:text-gray-100
                                dark:focus:border-indigo-400
                                dark:focus:ring-indigo-400
                            ',
    ]) }} />
