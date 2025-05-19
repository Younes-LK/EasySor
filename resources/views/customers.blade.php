@extends('layouts.base')
@section('sidebar')
    @include('layouts.sidebar')
@endsection

@section('content')
    <div class="container mx-auto px-4 py-6">
        <h2 class="text-xl font-bold mb-4 dark:text-white">مدیریت مشتریان</h2>

        <!-- Add/Edit Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
            <form method="POST"
                action="{{ $editMode ? route('customers.update', $customer->id) : route('customers.store') }}"
                id="customer-form" novalidate>
                @csrf
                @if ($editMode)
                    @method('PUT')
                @endif

                <input type="hidden" name="id" value="{{ old('id', $customer->id ?? '') }}">

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium dark:text-white" for="type">نوع مشتری</label>
                        <select id="type" name="type"
                            class="mt-1 block w-full rounded border border-gray-300 dark:bg-gray-700 dark:text-white">
                            <option value="">انتخاب کنید</option>
                            <option value="individual"
                                {{ old('type', $customer->type ?? '') === 'individual' ? 'selected' : '' }}>حقیقی
                            </option>
                            <option value="company"
                                {{ old('type', $customer->type ?? '') === 'company' ? 'selected' : '' }}>حقوقی</option>
                        </select>
                        <p class="text-red-600 text-sm mt-1 hidden" id="error-type"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-white" for="name">نام</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $customer->name ?? '') }}"
                            class="mt-1 block w-full rounded border border-gray-300 dark:bg-gray-700 dark:text-white">
                        <p class="text-red-600 text-sm mt-1 hidden" id="error-name"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-white" for="national_code">کد ملی</label>
                        <input type="text" id="national_code" name="national_code"
                            value="{{ old('national_code', $customer->national_code ?? '') }}"
                            class="mt-1 block w-full rounded border border-gray-300 dark:bg-gray-700 dark:text-white">
                        <p class="text-red-600 text-sm mt-1 hidden" id="error-national_code"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-white" for="register_number">شماره ثبت</label>
                        <input type="text" id="register_number" name="register_number"
                            value="{{ old('register_number', $customer->register_number ?? '') }}"
                            class="mt-1 block w-full rounded border border-gray-300 dark:bg-gray-700 dark:text-white">
                        <p class="text-red-600 text-sm mt-1 hidden" id="error-register_number"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-white" for="father_name">نام پدر</label>
                        <input type="text" id="father_name" name="father_name"
                            value="{{ old('father_name', $customer->father_name ?? '') }}"
                            class="mt-1 block w-full rounded border border-gray-300 dark:bg-gray-700 dark:text-white">
                        <p class="text-red-600 text-sm mt-1 hidden" id="error-father_name"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-white" for="phone">شماره تلفن</label>
                        <input type="text" id="phone" name="phone"
                            value="{{ old('phone', $customer->phone ?? '') }}"
                            class="mt-1 block w-full rounded border border-gray-300 dark:bg-gray-700 dark:text-white">
                        <p class="text-red-600 text-sm mt-1 hidden" id="error-phone"></p>
                    </div>
                </div>

                <!-- Address Fields -->
                <div class="mt-6">
                    <label class="block text-sm font-medium dark:text-white">آدرس‌ها</label>
                    <div id="addresses">
                        @php
                            $oldAddresses = old(
                                'addresses',
                                $customer->addresses ?? [['address' => '', 'label' => '', 'is_default' => false]],
                            );
                        @endphp
                        @foreach ($oldAddresses as $i => $address)
                            <div class="grid md:grid-cols-4 gap-4 mt-2 address-block items-center">
                                {{-- Hidden id for existing addresses --}}
                                @if (isset($address['id']))
                                    <input type="hidden" name="addresses[{{ $i }}][id]"
                                        value="{{ $address['id'] }}">
                                @elseif(isset($address->id))
                                    <input type="hidden" name="addresses[{{ $i }}][id]"
                                        value="{{ $address->id }}">
                                @endif

                                <input type="text" name="addresses[{{ $i }}][label]"
                                    value="{{ $address['label'] ?? ($address->label ?? '') }}" placeholder="برچسب"
                                    class="rounded border border-gray-300 dark:bg-gray-700 dark:text-white px-2 py-1">
                                <input type="text" name="addresses[{{ $i }}][address]"
                                    value="{{ $address['address'] ?? ($address->address ?? '') }}" placeholder="آدرس"
                                    class="rounded border border-gray-300 dark:bg-gray-700 dark:text-white px-2 py-1 address-input">
                                <label class="flex items-center space-x-2 dark:text-white">
                                    <input type="checkbox" name="addresses[{{ $i }}][is_default]" value="1"
                                        {{ !empty($address['is_default']) || !empty($address->is_default) ? 'checked' : '' }}>
                                    <span>پیش‌فرض</span>
                                </label>
                                <button type="button" class="remove-address text-red-500 text-lg font-bold px-2"
                                    title="حذف آدرس">&times;</button>
                                <p class="text-red-600 text-sm mt-1 hidden error-address" style="grid-column: span 4;"></p>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" id="add-address" class="mt-2 text-blue-600 dark:text-blue-400">+ آدرس
                        جدید</button>
                </div>

                <div class="mt-4">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded" id="submit-btn">
                        {{ $editMode ? 'ویرایش مشتری' : 'ثبت مشتری' }}
                    </button>
                </div>
                <p id="general-error" class="text-red-600 mt-3 hidden"></p>
            </form>
        </div>

        <!-- Search + Sort -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="جستجو..."
                    class="rounded border-gray-300 dark:bg-gray-700 dark:text-white px-2 py-1">
                <select name="sort" class="rounded border-gray-300 dark:bg-gray-700 dark:text-white">
                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>جدیدترین</option>
                    <option value="alphabet" {{ request('sort') == 'alphabet' ? 'selected' : '' }}>مرتب‌سازی الفبایی
                    </option>
                </select>
                <button class="px-3 py-1 bg-indigo-500 text-white rounded">اعمال</button>
            </form>
        </div>

        <!-- Customer List -->
        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl shadow">
            <table class="w-full text-sm text-right text-gray-500 dark:text-gray-300">
                <thead class="text-xs uppercase bg-gray-200 dark:bg-gray-700 dark:text-white">
                    <tr>
                        <th class="p-3">نام</th>
                        <th class="p-3">نوع</th>
                        <th class="p-3">تلفن</th>
                        <th class="p-3">آدرس‌ها</th>
                        <th class="p-3">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $c)
                        <tr class="border-b dark:border-gray-600">
                            <td class="p-3">{{ $c->name }}</td>
                            <td class="p-3">{{ $c->type === 'individual' ? 'حقیقی' : 'حقوقی' }}</td>
                            <td class="p-3">{{ $c->phone }}</td>
                            <td class="p-3">
                                @foreach ($c->addresses as $addr)
                                    <div>{{ $addr->label }}: {{ $addr->address }} @if ($addr->is_default)
                                            (پیش‌فرض)
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                            <td class="p-3">
                                <a href="{{ route('customers.edit', $c->id) }}"
                                    class="text-blue-600 hover:text-blue-800">ویرایش</a>
                                <form action="{{ route('customers.destroy', $c->id) }}" method="POST"
                                    class="inline-block" onsubmit="return confirm('آیا مطمئن هستید؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 ml-2">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @if ($customers->isEmpty())
                        <tr>
                            <td colspan="5" class="p-4 text-center text-gray-500 dark:text-gray-400">موردی یافت نشد.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $customers->withQueryString()->links() }}
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const addressesContainer = document.getElementById('addresses');
            const addBtn = document.getElementById('add-address');

            function getNextIndex() {
                let maxIndex = -1;
                [...addressesContainer.querySelectorAll('.address-block')].forEach(block => {
                    const inputs = block.querySelectorAll('input[name]');
                    inputs.forEach(input => {
                        const match = input.name.match(/addresses\[(\d+)\]/);
                        if (match) {
                            const idx = parseInt(match[1]);
                            if (idx > maxIndex) maxIndex = idx;
                        }
                    });
                });
                return maxIndex + 1;
            }

            addBtn.addEventListener('click', () => {
                const index = getNextIndex();
                const div = document.createElement('div');
                div.classList.add('grid', 'md:grid-cols-4', 'gap-4', 'mt-2', 'address-block',
                    'items-center');
                div.innerHTML = `
                <input type="text" name="addresses[${index}][label]" placeholder="برچسب" class="rounded border border-gray-300 dark:bg-gray-700 dark:text-white px-2 py-1">
                <input type="text" name="addresses[${index}][address]" placeholder="آدرس" class="rounded border border-gray-300 dark:bg-gray-700 dark:text-white px-2 py-1 address-input">
                <label class="flex items-center space-x-2 dark:text-white">
                    <input type="checkbox" name="addresses[${index}][is_default]" value="1">
                    <span>پیش‌فرض</span>
                </label>
                <button type="button" class="remove-address text-red-500 text-lg font-bold px-2" title="حذف آدرس">&times;</button>
                <p class="text-red-600 text-sm mt-1 hidden error-address" style="grid-column: span 4;"></p>
            `;
                addressesContainer.appendChild(div);
            });

            addressesContainer.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-address')) {
                    const block = e.target.closest('.address-block');
                    if (block) block.remove();
                }
            });

            // Optional: Add client-side validation here if desired

            const form = document.getElementById('customer-form');
            const generalError = document.getElementById('general-error');

            function clearErrors() {
                generalError.classList.add('hidden');
                generalError.textContent = '';

                // Clear all error messages and red borders
                const errorMessages = form.querySelectorAll('p[id^="error-"], .error-address');
                errorMessages.forEach(el => {
                    el.classList.add('hidden');
                    el.textContent = '';
                });

                const errorFields = form.querySelectorAll('.border-red-600');
                errorFields.forEach(el => el.classList.remove('border-red-600'));
            }

            function setError(field, message) {
                const errorId = 'error-' + field.id || field.name;
                let errorEl = document.getElementById(errorId);
                if (!errorEl) {
                    // for addresses error messages (class error-address)
                    errorEl = field.closest('.address-block')?.querySelector('.error-address');
                }
                if (errorEl) {
                    errorEl.textContent = message;
                    errorEl.classList.remove('hidden');
                }
                field.classList.add('border-red-600');
            }

            function validateForm() {
                clearErrors();

                let valid = true;

                // Validate type
                const type = form.querySelector('select[name="type"]');
                if (!type.value.trim()) {
                    setError(type, 'نوع مشتری الزامی است.');
                    valid = false;
                }

                // Validate name
                const name = form.querySelector('input[name="name"]');
                if (!name.value.trim()) {
                    setError(name, 'نام الزامی است.');
                    valid = false;
                }

                // Validate national_code
                const national_code = form.querySelector('input[name="national_code"]');
                if (!national_code.value.trim()) {
                    setError(national_code, 'کد ملی الزامی است.');
                    valid = false;
                } else if (!/^\d{10}$/.test(national_code.value.trim())) {
                    setError(national_code, 'کد ملی باید ۱۰ رقم باشد.');
                    valid = false;
                }

                // Validate phone
                const phone = form.querySelector('input[name="phone"]');
                if (!phone.value.trim()) {
                    setError(phone, 'شماره تلفن الزامی است.');
                    valid = false;
                } else if (!/^\d{8,15}$/.test(phone.value.trim())) {
                    setError(phone, 'شماره تلفن نامعتبر است.');
                    valid = false;
                }

                // Validate addresses: address field required
                const addressInputs = form.querySelectorAll('.address-input');
                if (addressInputs.length === 0) {
                    generalError.textContent = 'حداقل یک آدرس باید وارد شود.';
                    generalError.classList.remove('hidden');
                    valid = false;
                } else {
                    addressInputs.forEach(input => {
                        if (!input.value.trim()) {
                            setError(input, 'آدرس الزامی است.');
                            valid = false;
                        }
                    });
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
