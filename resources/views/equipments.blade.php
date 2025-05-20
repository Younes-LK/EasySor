@extends('layouts.base')
@section('sidebar')
    @include('layouts.sidebar')
@endsection

@section('content')
    <div class="container mx-auto px-4 py-6">
        <h2 class="text-xl font-bold mb-4 dark:text-white">مدیریت تجهیزات</h2>



        <!-- Add/Edit Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
            <form method="POST"
                action="{{ $editMode ? route('equipments.update', $equipment->id) : route('equipments.store') }}"
                id="equipment-form" novalidate>
                @csrf
                @if ($editMode)
                    @method('PUT')
                @endif

                <input type="hidden" name="id" value="{{ old('id', $equipment->id ?? '') }}">

                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium dark:text-white" for="name">نام تجهیز</label>
                        <input type="text" id="name" name="name"
                            value="{{ old('name', $equipment->name ?? '') }}"
                            class="mt-1 block w-full rounded border border-gray-300 dark:bg-gray-700 dark:text-white">
                        <p class="text-red-600 text-sm mt-1 hidden" id="error-name"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium dark:text-white" for="price">قیمت</label>
                        <input type="number" step="0.01" min="0" id="price" name="price"
                            value="{{ old('price', $equipment->price ?? '') }}"
                            class="mt-1 block w-full rounded border border-gray-300 dark:bg-gray-700 dark:text-white">
                        <p class="text-red-600 text-sm mt-1 hidden" id="error-price"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium dark:text-white" for="stock_quantity">موجودی انبار</label>
                        <input type="number" min="0" id="stock_quantity" name="stock_quantity"
                            value="{{ old('stock_quantity', $equipment->stock_quantity ?? '') }}"
                            class="mt-1 block w-full rounded border border-gray-300 dark:bg-gray-700 dark:text-white">
                        <p class="text-red-600 text-sm mt-1 hidden" id="error-stock_quantity"></p>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded" id="submit-btn">
                        {{ $editMode ? 'ویرایش تجهیز' : 'ثبت تجهیز' }}
                    </button>
                </div>

                <p id="general-error" class="text-red-600 mt-3 hidden"></p>
            </form>
        </div>

        <!-- Search -->
        <div class="mb-4">
            <form method="GET" action="{{ route('equipments.index') }}"
                class="flex items-center space-x-2 rtl:space-x-reverse">
                <input type="search" name="search" placeholder="جستجو بر اساس نام تجهیز" value="{{ request('search') }}"
                    class="w-full max-w-xs rounded border border-gray-300 px-3 py-2 dark:bg-gray-700 dark:text-white" />
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">جستجو</button>
                @if (request('search'))
                    <a href="{{ route('equipments.index') }}" class="text-red-600 hover:underline px-3">پاک کردن</a>
                @endif
            </form>
        </div>

        <!-- Equipment List -->
        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl shadow">
            <table class="w-full text-sm text-right text-gray-500 dark:text-gray-300">
                <thead class="text-xs uppercase bg-gray-200 dark:bg-gray-700 dark:text-white">
                    <tr>
                        <th class="p-3">نام تجهیز</th>
                        <th class="p-3">قیمت</th>
                        <th class="p-3">موجودی انبار</th>
                        <th class="p-3">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($equipments as $eq)
                        <tr class="border-b dark:border-gray-600">
                            <td class="p-3">{{ $eq->name }}</td>
                            <td class="p-3">{{ number_format($eq->price) }}</td>
                            <td class="p-3">{{ $eq->stock_quantity }}</td>
                            <td class="p-3">
                                <a href="{{ route('equipments.edit', $eq->id) }}"
                                    class="text-blue-600 hover:text-blue-800">ویرایش</a>
                                <form action="{{ route('equipments.destroy', $eq->id) }}" method="POST"
                                    class="inline-block" onsubmit="return confirm('آیا مطمئن هستید؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 ml-2">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @if ($equipments->isEmpty())
                        <tr>
                            <td colspan="4" class="p-4 text-center text-gray-500 dark:text-gray-400">موردی یافت نشد.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $equipments->withQueryString()->links() }}
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('equipment-form');
            const generalError = document.getElementById('general-error');

            function clearErrors() {
                generalError.classList.add('hidden');
                generalError.textContent = '';

                const errorMessages = form.querySelectorAll('p[id^="error-"]');
                errorMessages.forEach(el => {
                    el.classList.add('hidden');
                    el.textContent = '';
                });

                const errorFields = form.querySelectorAll('.border-red-600');
                errorFields.forEach(el => el.classList.remove('border-red-600'));
            }

            function setError(field, message) {
                const errorId = 'error-' + field.id;
                let errorEl = document.getElementById(errorId);
                if (errorEl) {
                    errorEl.textContent = message;
                    errorEl.classList.remove('hidden');
                }
                field.classList.add('border-red-600');
            }

            function validateForm() {
                clearErrors();

                let valid = true;

                // Validate name
                const name = form.querySelector('#name');
                if (!name.value.trim()) {
                    setError(name, 'نام تجهیز الزامی است.');
                    valid = false;
                }

                // Validate price
                const price = form.querySelector('#price');
                if (!price.value.trim()) {
                    setError(price, 'قیمت الزامی است.');
                    valid = false;
                } else if (isNaN(price.value) || Number(price.value) < 0) {
                    setError(price, 'قیمت باید عددی مثبت باشد.');
                    valid = false;
                }

                // Validate stock_quantity
                const stock = form.querySelector('#stock_quantity');
                if (!stock.value.trim()) {
                    setError(stock, 'موجودی انبار الزامی است.');
                    valid = false;
                } else if (!Number.isInteger(Number(stock.value)) || Number(stock.value) < 0) {
                    setError(stock, 'موجودی انبار باید عدد صحیح غیر منفی باشد.');
                    valid = false;
                }

                return valid;
            }

            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    generalError.classList.remove('hidden');
                    generalError.textContent = 'لطفاً خطاهای فرم را اصلاح کنید.';
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
@endsection
