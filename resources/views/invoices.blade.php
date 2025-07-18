@extends('layouts.base')

@section('sidebar')
    @include('layouts.sidebar')
@endsection

@section('content')
    @php
        // Set default values to prevent errors if they are not passed from the controller
        $editMode = $editMode ?? false;
        $invoice = $invoice ?? new \App\Models\Invoice();
        $currentJalaliDate = $currentJalaliDate ?? \Morilog\Jalali\Jalalian::now()->format('Y/m/d');
    @endphp

    <div class="container mx-auto px-4 py-6">
        <h2 class="text-xl font-bold mb-4 dark:text-white">
            @if ($editMode)
                ویرایش فاکتور #{{ $invoice->id }}
            @else
                مدیریت فاکتور ها
            @endif
        </h2>

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 dark:bg-green-700 text-green-700 dark:text-green-100 rounded-md">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-700 text-red-700 dark:text-red-100 rounded-md">
                <strong class="font-bold">خطا!</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- The Form is now ALWAYS at the top --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
            <form method="POST" action="{{ $editMode ? route('invoices.update', $invoice->id) : route('invoices.store') }}">
                @csrf
                @if ($editMode)
                    @method('PUT')
                @endif

                {{-- Main Invoice Info --}}
                <div class="grid md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium dark:text-white">مشتری</label>
                        <select name="customer_id"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-gray-700 dark:text-white">
                            <option value="">انتخاب کنید</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id', $invoice->customer_id) == $customer->id)>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-white">آدرس</label>
                        <select name="customer_address_id"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-gray-700 dark:text-white">
                            {{-- Populated by JS --}}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-white">تکنسین</label>
                        <select name="assigned_to"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-gray-700 dark:text-white">
                            <option value="">انتخاب کنید</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(old('assigned_to', $invoice->assigned_to) == $user->id)>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-white">تاریخ فاکتور</label>
                        <input type="text" name="invoice_date"
                            value="{{ old('invoice_date', $invoice->formatted_invoice_date ?? $currentJalaliDate) }}"
                            class="persian-date-picker mt-1 block w-full rounded border-gray-300 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium dark:text-white">وضعیت</label>
                        <select name="status"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-gray-700 dark:text-white">
                            <option value="draft" @selected(old('status', $invoice->status) == 'draft')>پیش‌نویس</option>
                            <option value="sent" @selected(old('status', $invoice->status) == 'sent')>ارسال شده</option>
                            <option value="paid" @selected(old('status', $invoice->status) == 'paid')>پرداخت شده</option>
                            <option value="cancelled" @selected(old('status', $invoice->status) == 'cancelled')>لغو شده</option>
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium dark:text-white">یادداشت ها</label>
                        <textarea name="notes" rows="2"
                            class="mt-1 block w-full rounded border-gray-300 dark:bg-gray-700 dark:text-white">{{ old('notes', $invoice->notes) }}</textarea>
                    </div>
                </div>

                {{-- Invoice Equipments --}}
                <div class="mt-8" id="invoice-equipments-section-wrapper">
                    <h3 class="text-lg font-semibold mb-3 dark:text-white border-b pb-2">تجهیزات</h3>
                    <div id="invoice-equipments-container">
                        @if ($invoice->equipments)
                            @foreach ($invoice->equipments as $index => $equipment)
                                <div class="p-3 mb-2 border rounded-md existing-item equipment-item">
                                    <input type="hidden" name="equipments[{{ $index }}][id]"
                                        value="{{ $equipment->id }}">
                                    <input type="hidden" name="equipments[{{ $index }}][equipment_id]"
                                        value="{{ $equipment->equipment_id }}">
                                    <p class="font-semibold text-sm mb-2">{{ $equipment->equipment?->name }} (موجودی:
                                        {{ $equipment->equipment?->stock_quantity + $equipment->quantity }})</p>
                                    <div class="grid md:grid-cols-5 gap-3 items-center">
                                        <div><label class="text-xs">تعداد</label><input type="number"
                                                name="equipments[{{ $index }}][quantity]"
                                                value="{{ $equipment->quantity }}"
                                                class="quantity-input mt-1 block w-full text-sm rounded border-gray-300 dark:bg-gray-700">
                                        </div>
                                        <div><label class="text-xs">قیمت واحد</label><input type="number"
                                                name="equipments[{{ $index }}][unit_price]"
                                                value="{{ $equipment->unit_price }}"
                                                class="unit-price-input mt-1 block w-full text-sm rounded border-gray-300 dark:bg-gray-700">
                                        </div>
                                        <div><label class="text-xs">قیمت کل</label><input type="text" readonly
                                                class="total-price-display mt-1 block w-full text-sm rounded border-gray-300 bg-gray-100 dark:bg-gray-800">
                                        </div>
                                        <div class="md:col-span-2"><label class="text-xs">یادداشت</label><input
                                                type="text" name="equipments[{{ $index }}][notes]"
                                                value="{{ $equipment->notes }}" placeholder="یادداشت"
                                                class="mt-1 block w-full text-sm rounded border-gray-300 dark:bg-gray-700">
                                        </div>
                                    </div>
                                    <label class="flex items-center mt-2 text-sm">
                                        <input type="checkbox" name="equipments[{{ $index }}][_remove]"
                                            value="1"
                                            class="rounded border-gray-300 dark:border-gray-600 text-red-600 shadow-sm focus:ring-red-500">
                                        <span class="ms-2 text-red-600 dark:text-red-400">حذف</span>
                                    </label>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <div id="new-equipments-placeholder"></div>
                    <button type="button" id="add-invoice-equipment-btn"
                        class="mt-2 text-sm px-3 py-1.5 bg-green-500 text-white rounded-md">+ افزودن تجهیز</button>
                </div>

                {{-- Invoice Custom Items --}}
                <div class="mt-8">
                    <h3 class="text-lg font-semibold mb-3 dark:text-white border-b pb-2">موارد سفارشی</h3>
                    <div id="invoice-items-container">
                        @if ($invoice->items)
                            @foreach ($invoice->items as $index => $item)
                                <div
                                    class="p-3 mb-2 border rounded-md grid md:grid-cols-3 gap-3 items-center existing-item custom-item">
                                    <input type="hidden" name="items[{{ $index }}][id]"
                                        value="{{ $item->id }}">
                                    <input type="text" name="items[{{ $index }}][name]"
                                        value="{{ $item->name }}" placeholder="شرح"
                                        class="rounded border-gray-300 dark:bg-gray-700">
                                    <input type="number" name="items[{{ $index }}][price]"
                                        value="{{ $item->price }}" placeholder="قیمت"
                                        class="item-price-input rounded border-gray-300 dark:bg-gray-700">
                                    <label class="flex items-center text-sm">
                                        <input type="checkbox" name="items[{{ $index }}][_remove]" value="1"
                                            class="rounded border-gray-300 dark:border-gray-600 text-red-600 shadow-sm focus:ring-red-500">
                                        <span class="ms-2 text-red-600 dark:text-red-400">حذف</span>
                                    </label>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <div id="new-items-placeholder"></div>
                    <button type="button" id="add-invoice-item-btn"
                        class="mt-2 text-sm px-3 py-1.5 bg-blue-500 text-white rounded-md">+ افزودن مورد سفارشی</button>
                </div>

                <div class="mt-8">
                    <h3 class="text-lg font-semibold mb-3 dark:text-white">مبلغ نهایی فاکتور</h3>
                    <input type="text"
                        class="total-grand-price mt-1 block w-full rounded border-gray-300 dark:bg-gray-700 dark:text-white"
                        readonly>
                    <input type="hidden" name="total_price"
                        value="{{ old('total_price', $invoice->total_price ?? 0) }}">
                </div>

                <div class="mt-8 pt-6 border-t">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded">
                        {{ $editMode ? 'بروزرسانی فاکتور' : 'ثبت فاکتور' }}
                    </button>
                    @if ($editMode)
                        <a href="{{ route('invoices.index') }}"
                            class="px-6 py-2 bg-gray-300 dark:bg-gray-600 rounded ms-2">لغو</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- The List now ONLY shows if we are on the index page (i.e., not in edit mode) --}}
        @if (!$editMode && isset($invoicesList))
            <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl shadow mt-8">
                <table class="w-full text-sm text-right text-gray-500 dark:text-gray-300">
                    <thead class="text-xs uppercase bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="p-3">#</th>
                            <th class="p-3">مشتری</th>
                            <th class="p-3">تاریخ</th>
                            <th class="p-3">مبلغ کل</th>
                            <th class="p-3">وضعیت</th>
                            <th class="p-3">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoicesList as $item)
                            <tr class="border-b dark:border-gray-700">
                                <td class="p-3">{{ $item->id }}</td>
                                <td class="p-3">{{ $item->customer->name ?? 'N/A' }}</td>
                                <td class="p-3">{{ $item->formatted_invoice_date }}</td>
                                <td class="p-3">{{ number_format($item->total_price) }} تومان</td>
                                <td class="p-3">
                                    @php
                                        $statusText = match ($item->status) {
                                            'draft' => 'پیش‌نویس',
                                            'sent' => 'ارسال شده',
                                            'paid' => 'پرداخت شده',
                                            'cancelled' => 'لغو شده',
                                            default => $item->status,
                                        };
                                        $statusColor = match ($item->status) {
                                            'draft' => 'bg-yellow-200 text-yellow-800',
                                            'sent' => 'bg-blue-200 text-blue-800',
                                            'paid' => 'bg-green-200 text-green-800',
                                            'cancelled' => 'bg-red-200 text-red-800',
                                            default => 'bg-gray-200 text-gray-800',
                                        };
                                    @endphp
                                    <span
                                        class="px-2 py-1 text-xs rounded-full {{ $statusColor }}">{{ $statusText }}</span>
                                </td>
                                <td class="p-3 whitespace-nowrap">
                                    <a href="{{ route('invoices.edit', $item->id) }}"
                                        class="text-blue-600 px-2">ویرایش</a>
                                    <form action="{{ route('invoices.destroy', $item->id) }}" method="POST"
                                        class="inline-block" onsubmit="return confirm('آیا مطمئن هستید؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 px-2">حذف</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-4 text-center">موردی یافت نشد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $invoicesList->links() }}</div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('form');
            if (!form) return;

            const customersWithAddresses = @json($customersWithAddresses ?? []);
            const availableEquipments = @json($availableEquipments ?? []);

            const customerSelect = document.querySelector('[name="customer_id"]');
            const addressSelect = document.querySelector('[name="customer_address_id"]');

            const populateAddresses = (customerId) => {
                const customer = customersWithAddresses.find(c => c.id == customerId);
                addressSelect.innerHTML = '<option value="">انتخاب کنید</option>';
                if (customer) {
                    customer.addresses.forEach(addr => {
                        const selected =
                            "{{ old('customer_address_id', $invoice->customer_address_id) }}" == addr
                            .id ? 'selected' : '';
                        addressSelect.innerHTML +=
                            `<option value="${addr.id}" ${selected}>${addr.label || 'آدرس'}: ${addr.address}</option>`;
                    });
                }
            };

            if (customerSelect && customerSelect.value) {
                populateAddresses(customerSelect.value);
            }

            if (customerSelect) customerSelect.addEventListener('change', () => populateAddresses(customerSelect
                .value));

            function updateEquipmentRowTotal(rowElement) {
                const quantityInput = rowElement.querySelector('.quantity-input');
                const unitPriceInput = rowElement.querySelector('.unit-price-input');
                const totalPriceDisplay = rowElement.querySelector('.total-price-display');
                if (!quantityInput || !unitPriceInput || !totalPriceDisplay) return;
                const quantity = parseFloat(quantityInput.value) || 0;
                const unitPrice = parseFloat(unitPriceInput.value) || 0;
                totalPriceDisplay.value = (quantity * unitPrice).toLocaleString('fa-IR');
            }

            const calculateGrandTotal = () => {
                let total = 0;
                document.querySelectorAll('.equipment-item, .custom-item').forEach(row => {
                    const removeCheckbox = row.querySelector('[name$="[_remove]"]');
                    if (removeCheckbox && removeCheckbox.checked) return;

                    let quantity = 1;
                    let price = 0;

                    if (row.classList.contains('equipment-item')) {
                        quantity = parseFloat(row.querySelector('.quantity-input')?.value) || 0;
                        price = parseFloat(row.querySelector('.unit-price-input')?.value) || 0;
                        total += quantity * price;
                    } else {
                        price = parseFloat(row.querySelector('.item-price-input')?.value) || 0;
                        total += price;
                    }
                });
                form.querySelector('[name="total_price"]').value = total;
                form.querySelector('.total-grand-price').value = total.toLocaleString('fa-IR');
            };

            let equipmentIndex = {{ $invoice->equipments?->count() ?? 0 }};
            const addEquipmentBtn = document.getElementById('add-invoice-equipment-btn');
            if (addEquipmentBtn) {
                addEquipmentBtn.addEventListener('click', () => {
                    const placeholder = document.getElementById('new-equipments-placeholder');
                    let equipmentOptions = '<option value="">انتخاب تجهیز</option>';
                    availableEquipments.forEach(eq => {
                        equipmentOptions +=
                            `<option value="${eq.id}" data-price="${eq.price}">(موجودی: ${eq.stock_quantity}) ${eq.name}</option>`;
                    });
                    const newRow = `
                    <div class="p-3 mb-2 border rounded-md new-item equipment-item">
                         <input type="hidden" name="equipments[new_${equipmentIndex}][id]" value="">
                         <div class="grid md:grid-cols-5 gap-3 items-center">
                            <select name="equipments[new_${equipmentIndex}][equipment_id]" class="new-equipment-select md:col-span-2 mt-1 block w-full text-sm rounded border-gray-300 dark:bg-gray-700">${equipmentOptions}</select>
                            <input type="number" name="equipments[new_${equipmentIndex}][quantity]" value="1" class="quantity-input mt-1 block w-full text-sm rounded border-gray-300 dark:bg-gray-700">
                            <input type="number" name="equipments[new_${equipmentIndex}][unit_price]" value="0" class="unit-price-input mt-1 block w-full text-sm rounded border-gray-300 dark:bg-gray-700">
                            <button type="button" class="remove-item text-red-500 justify-self-center">حذف</button>
                        </div>
                        <div class="grid md:grid-cols-5 gap-3 items-center mt-2">
                           <div class="md:col-span-2"><label class="text-xs">یادداشت</label><input type="text" name="equipments[new_${equipmentIndex}][notes]" placeholder="یادداشت" class="mt-1 block w-full text-sm rounded border-gray-300 dark:bg-gray-700"></div>
                           <div class="md:col-start-4"><label class="text-xs">قیمت کل</label><input type="text" readonly class="total-price-display mt-1 block w-full text-sm rounded border-gray-300 bg-gray-100 dark:bg-gray-800"></div>
                        </div>
                    </div>
                `;
                    placeholder.insertAdjacentHTML('beforeend', newRow);
                    equipmentIndex++;
                });
            }

            let itemIndex = {{ $invoice->items?->count() ?? 0 }};
            const addItemBtn = document.getElementById('add-invoice-item-btn');
            if (addItemBtn) {
                addItemBtn.addEventListener('click', () => {
                    const placeholder = document.getElementById('new-items-placeholder');
                    const newRow = `
                    <div class="p-3 mb-2 border rounded-md grid md:grid-cols-3 gap-3 items-center new-item custom-item">
                        <input type="hidden" name="items[new_${itemIndex}][id]" value="">
                        <input type="text" name="items[new_${itemIndex}][name]" placeholder="شرح" class="rounded border-gray-300 dark:bg-gray-700">
                        <input type="number" name="items[new_${itemIndex}][price]" placeholder="قیمت" class="item-price-input rounded border-gray-300 dark:bg-gray-700">
                        <button type="button" class="remove-item text-red-500">حذف</button>
                    </div>
                `;
                    placeholder.insertAdjacentHTML('beforeend', newRow);
                    itemIndex++;
                });
            }

            form.addEventListener('input', (e) => {
                const row = e.target.closest('.equipment-item');
                if (row) {
                    updateEquipmentRowTotal(row);
                }
                calculateGrandTotal();
            });

            form.addEventListener('click', e => {
                if (e.target.matches('[name$="[_remove]"]') || e.target.classList.contains('remove-item')) {
                    if (e.target.closest('.new-item')) {
                        e.target.closest('.new-item').remove();
                    } else if (e.target.closest('.existing-item')) {
                        // Just check the box, don't remove the element
                        e.target.closest('.existing-item').style.opacity = e.target.checked ? '0.5' : '1';
                    }
                    calculateGrandTotal();
                }
            });

            document.body.addEventListener('change', e => {
                if (e.target.classList.contains('new-equipment-select')) {
                    const price = e.target.options[e.target.selectedIndex].dataset.price || 0;
                    const row = e.target.closest('.equipment-item');
                    row.querySelector('.unit-price-input').value = price;
                    updateEquipmentRowTotal(row);
                    calculateGrandTotal();
                }
            });

            document.querySelectorAll('.equipment-item').forEach(updateEquipmentRowTotal);
            calculateGrandTotal();

            $('.persian-date-picker').persianDatepicker({
                format: 'YYYY/MM/DD',
                autoClose: true
            });
        });
    </script>
@endsection
