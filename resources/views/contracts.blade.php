@extends('layouts.base')

@if (Auth::check())
    @section('sidebar')
        @include('layouts.sidebar')
    @endsection
@endif

@section('content')
    @php
        use Illuminate\Support\Str;
        $contract = $contract ?? new \App\Models\Contract();
        $editMode = $editMode ?? false;
    @endphp

    <div class="container mx-auto px-4 py-6">
        <h2 class="text-xl font-bold mb-4 dark:text-white">
            @if (Route::currentRouteName() === 'contracts.create')
                ایجاد قرارداد جدید
            @elseif($editMode)
                ویرایش قرارداد: {{ $contract->customer->name ?? '' }} - #{{ $contract->id }}
            @else
                مدیریت قراردادها
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

        <script>
            const currentJalaliDateTimeForJS = @json($currentJalaliDateTime ?? \Morilog\Jalali\Jalalian::now()->format('Y/m/d H:i'));
            const usersDataForJS = @json($users ?? []);
            const availableEquipmentsData = @json($availableEquipments ?? []);
        </script>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
            <form method="POST"
                action="{{ $editMode ? route('contracts.update', $contract->id) : route('contracts.store') }}"
                id="contract-form" novalidate>
                @csrf
                @if ($editMode)
                    @method('PUT')
                @endif

                <input type="hidden" name="id" value="{{ old('id', $contract->id ?? '') }}">

                {{-- Main Contract Info Section --}}
                <h3 class="text-lg font-semibold mb-3 dark:text-white border-b pb-2 dark:border-gray-700">اطلاعات اصلی
                    قرارداد</h3>
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium dark:text-white" for="customer_id">مشتری</label>
                        <select id="customer_id" name="customer_id"
                            class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            {{ $editMode && $contract->customer_id ? 'disabled' : '' }}>
                            <option value="">انتخاب کنید</option>
                            @foreach ($customers ?? [] as $customerOption)
                                <option value="{{ $customerOption->id }}"
                                    {{ old('customer_id', $contract->customer_id ?? '') == $customerOption->id ? 'selected' : '' }}>
                                    {{ $customerOption->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($editMode && $contract->customer_id)
                            <input type="hidden" name="customer_id" value="{{ $contract->customer_id }}">
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium dark:text-white" for="customer_address_id">آدرس
                            مشتری</label>
                        <select id="customer_address_id" name="customer_address_id"
                            class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">ابتدا مشتری را انتخاب کنید</option>
                            @php
                                $currentContractCustomerId = old('customer_id', $contract->customer_id ?? null);
                                $currentContractAddressId = old(
                                    'customer_address_id',
                                    $contract->customer_address_id ?? null,
                                );
                                if ($currentContractCustomerId && isset($customersWithAddresses)) {
                                    $customerForAddress = $customersWithAddresses->firstWhere(
                                        'id',
                                        $currentContractCustomerId,
                                    );
                                    if ($customerForAddress && $customerForAddress->addresses) {
                                        foreach ($customerForAddress->addresses as $address) {
                                            echo "<option value='{$address->id}' " .
                                                ($currentContractAddressId == $address->id ? 'selected' : '') .
                                                '>' .
                                                e($address->label ? $address->label . ': ' : '') .
                                                e($address->address) .
                                                '</option>';
                                        }
                                    }
                                }
                            @endphp
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium dark:text-white" for="stop_count">تعداد توقف</label>
                        <input type="number" id="stop_count" name="stop_count"
                            value="{{ old('stop_count', $contract->stop_count ?? '') }}" min="0"
                            class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>

                    <div>
                        <label class="block text-sm font-medium dark:text-white" for="total_price">مبلغ کل (تومان)</label>
                        <input type="number" id="total_price" name="total_price"
                            value="{{ old('total_price', $contract->total_price ?? '') }}" min="0"
                            class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium dark:text-white" for="description">توضیحات</label>
                        <textarea id="description" name="description" rows="3"
                            class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('description', $contract->description ?? '') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium dark:text-white" for="assigned_to">تکنسین مسئول</label>
                        <select id="assigned_to" name="assigned_to"
                            class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">انتخاب کنید</option>
                            @foreach ($users ?? [] as $user)
                                <option value="{{ $user->id }}"
                                    {{ old('assigned_to', $contract->assigned_to ?? '') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium dark:text-white" for="status">وضعیت قرارداد</label>
                        <select id="status" name="status"
                            class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="draft"
                                {{ old('status', $contract->status ?? 'draft') === 'draft' ? 'selected' : '' }}>پیش‌نویس
                            </option>
                            <option value="active"
                                {{ old('status', $contract->status ?? '') === 'active' ? 'selected' : '' }}>فعال</option>
                            <option value="completed"
                                {{ old('status', $contract->status ?? '') === 'completed' ? 'selected' : '' }}>تکمیل شده
                            </option>
                            <option value="cancelled"
                                {{ old('status', $contract->status ?? '') === 'cancelled' ? 'selected' : '' }}>لغو شده
                            </option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="flex items-center dark:text-white mt-2">
                            <input type="checkbox" name="sms_sent" value="1"
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                {{ old('sms_sent', $contract->sms_sent ?? false) ? 'checked' : '' }}>
                            <span class="ms-2">پیامک ارسال شود</span>
                        </label>
                    </div>
                </div>

                {{-- Equipments Section --}}
                <div class="mt-8" id="equipments-section-wrapper">
                    <h3 class="text-lg font-semibold mb-3 dark:text-white border-b pb-2 dark:border-gray-700">تجهیزات
                        قرارداد</h3>
                    <div id="contract-equipments-container">
                        @if ($editMode && $contract->equipments->count() > 0)
                            @foreach ($contract->equipments as $index => $contractEquipment)
                                <div class="p-3 mb-2 border dark:border-gray-700 rounded-md equipment-item"
                                    data-index="{{ $index }}">
                                    <input type="hidden" name="equipments[{{ $index }}][id]"
                                        value="{{ $contractEquipment->id }}">

                                    <p class="font-semibold dark:text-indigo-400">
                                        {{ $contractEquipment->equipment->name ?? 'تجهیز نامشخص' }}
                                        (موجودی:
                                        {{ $contractEquipment->equipment->stock_quantity + $contractEquipment->quantity }})
                                    </p>

                                    <div class="grid md:grid-cols-5 gap-3 mt-2">
                                        <div>
                                            <label class="block text-xs dark:text-gray-300">تعداد</label>
                                            <input type="number" name="equipments[{{ $index }}][quantity]"
                                                value="{{ old("equipments.{$index}.quantity", $contractEquipment->quantity) }}"
                                                min="0"
                                                class="quantity-input mt-1 block w-full text-sm rounded border-gray-300 bg-gray-50 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-xs dark:text-gray-300">قیمت واحد (تومان)</label>
                                            <input type="number" name="equipments[{{ $index }}][unit_price]"
                                                value="{{ old("equipments.{$index}.unit_price", $contractEquipment->unit_price) }}"
                                                min="0"
                                                class="unit-price-input mt-1 block w-full text-sm rounded border-gray-300 bg-gray-50 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-xs dark:text-gray-300">قیمت کل (تومان)</label>
                                            <input type="text" readonly
                                                class="total-price-display mt-1 block w-full text-sm rounded border-gray-300 bg-gray-200 text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-xs dark:text-gray-300">یادداشت</label>
                                            <input type="text" name="equipments[{{ $index }}][notes]"
                                                value="{{ old("equipments.{$index}.notes", $contractEquipment->notes) }}"
                                                class="mt-1 block w-full text-sm rounded border-gray-300 bg-gray-50 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                    </div>


                                    <label class="flex items-center mt-2 text-sm">
                                        <input type="checkbox" name="equipments[{{ $index }}][_remove]"
                                            value="1"
                                            class="rounded border-gray-300 dark:border-gray-600 text-red-600 shadow-sm focus:ring-red-500">
                                        <span class="ms-2 text-red-600 dark:text-red-400">حذف این تجهیز</span>
                                    </label>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <div id="new-equipments-placeholder"></div>
                    <button type="button" id="add-contract-equipment-btn"
                        class="mt-2 text-sm px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white rounded-md">+ افزودن
                        تجهیز جدید</button>
                </div>
                {{-- Payments Section --}}
                <div class="mt-8">
                    <h3 class="text-lg font-semibold mb-3 dark:text-white border-b pb-2 dark:border-gray-700">پرداخت‌های
                        قرارداد</h3>
                    <div id="contract-payments-container">
                        @if ($editMode && $contract->payments->count() > 0)
                            @foreach ($contract->payments as $index => $payment)
                                <div class="p-3 mb-2 border dark:border-gray-700 rounded-md payment-item"
                                    data-index="{{ $index }}">
                                    <input type="hidden" name="payments[{{ $index }}][id]"
                                        value="{{ $payment->id }}">
                                    <div class="grid md:grid-cols-4 gap-3">
                                        <div>
                                            <label class="block text-xs dark:text-gray-300">عنوان</label>
                                            <input type="text" name="payments[{{ $index }}][title]"
                                                value="{{ old("payments.{$index}.title", $payment->title) }}"
                                                class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-xs dark:text-gray-300">مبلغ (تومان)</label>
                                            <input type="number" name="payments[{{ $index }}][amount]"
                                                value="{{ old("payments.{$index}.amount", $payment->amount) }}"
                                                min="0"
                                                class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-xs dark:text-gray-300">تاریخ پرداخت</label>
                                            <input type="text" name="payments[{{ $index }}][paid_at]"
                                                value="{{ old("payments.{$index}.paid_at", $payment->formatted_paid_at) }}"
                                                placeholder="مثلا: 1403/01/15 10:30"
                                                class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white persian-date-time-picker">
                                        </div>
                                        <div class="md:col-span-4">
                                            <label class="block text-xs dark:text-gray-300">یادداشت</label>
                                            <input type="text" name="payments[{{ $index }}][note]"
                                                value="{{ old("payments.{$index}.note", $payment->note) }}"
                                                class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                    </div>
                                    <label class="flex items-center mt-2 text-sm">
                                        <input type="checkbox" name="payments[{{ $index }}][_remove]"
                                            value="1"
                                            class="rounded border-gray-300 dark:border-gray-600 text-red-600 shadow-sm focus:ring-red-500">
                                        <span class="ms-2 text-red-600 dark:text-red-400">حذف این پرداخت</span>
                                    </label>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <div id="new-payments-placeholder"></div>
                    <button type="button" id="add-contract-payment-btn"
                        class="mt-2 text-sm px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded-md">+ افزودن
                        پرداخت جدید</button>
                </div>

                {{-- Checks Section --}}
                <div class="mt-8">
                    <h3 class="text-lg font-semibold mb-3 dark:text-white border-b pb-2 dark:border-gray-700">چک‌های
                        قرارداد</h3>
                    <div id="contract-checks-container">
                        @if ($editMode && $contract->checks->count() > 0)
                            @foreach ($contract->checks as $index => $check)
                                <div class="p-3 mb-2 border dark:border-gray-700 rounded-md check-item"
                                    data-index="{{ $index }}">
                                    <input type="hidden" name="checks[{{ $index }}][id]"
                                        value="{{ $check->id }}">
                                    <div class="grid md:grid-cols-4 gap-3">
                                        <div>
                                            <label class="block text-xs dark:text-gray-300">تاریخ چک</label>
                                            <input type="text" name="checks[{{ $index }}][date]"
                                                value="{{ old("checks.{$index}.date", $check->formatted_date) }}"
                                                placeholder="مثلا: 1403/01/15"
                                                class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white persian-date-picker">
                                        </div>
                                        <div>
                                            <label class="block text-xs dark:text-gray-300">مبلغ (تومان)</label>
                                            <input type="number" name="checks[{{ $index }}][amount]"
                                                value="{{ old("checks.{$index}.amount", $check->amount) }}"
                                                min="0"
                                                class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-xs dark:text-gray-300">نام بانک</label>
                                            <input type="text" name="checks[{{ $index }}][bank_name]"
                                                value="{{ old("checks.{$index}.bank_name", $check->bank_name) }}"
                                                class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-xs dark:text-gray-300">شماره سریال</label>
                                            <input type="text" name="checks[{{ $index }}][serial_number]"
                                                value="{{ old("checks.{$index}.serial_number", $check->serial_number) }}"
                                                class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-xs dark:text-gray-300">شماره صیادی</label>
                                            <input type="text" name="checks[{{ $index }}][sayadi_number]"
                                                value="{{ old("checks.{$index}.sayadi_number", $check->sayadi_number) }}"
                                                class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-xs dark:text-gray-300">در وجه</label>
                                            <input type="text" name="checks[{{ $index }}][in_name_of]"
                                                value="{{ old("checks.{$index}.in_name_of", $check->in_name_of) }}"
                                                class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-xs dark:text-gray-300">وضعیت</label>
                                            <select name="checks[{{ $index }}][status]"
                                                class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                <option value="draft"
                                                    {{ old("checks.{$index}.status", $check->status) == 'draft' ? 'selected' : '' }}>
                                                    پیش نویس</option>
                                                <option value="cashed"
                                                    {{ old("checks.{$index}.status", $check->status) == 'cashed' ? 'selected' : '' }}>
                                                    پاس شده</option>
                                                <option value="bounced"
                                                    {{ old("checks.{$index}.status", $check->status) == 'bounced' ? 'selected' : '' }}>
                                                    برگشت خورده</option>
                                                <option value="referred"
                                                    {{ old("checks.{$index}.status", $check->status) == 'referred' ? 'selected' : '' }}>
                                                    ارجاع شده</option>
                                            </select>
                                        </div>
                                    </div>
                                    <label class="flex items-center mt-2 text-sm">
                                        <input type="checkbox" name="checks[{{ $index }}][_remove]"
                                            value="1"
                                            class="rounded border-gray-300 dark:border-gray-600 text-red-600 shadow-sm focus:ring-red-500">
                                        <span class="ms-2 text-red-600 dark:text-red-400">حذف این چک</span>
                                    </label>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <div id="new-checks-placeholder"></div>
                    <button type="button" id="add-contract-check-btn"
                        class="mt-2 text-sm px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded-md">+ افزودن
                        چک
                        جدید</button>
                </div>

                {{-- Logs Section --}}

                <div class="mt-8">
                    <h3 class="text-lg font-semibold mb-3 dark:text-white border-b pb-2 dark:border-gray-700">گزارشات و
                        مراحل انجام کار</h3>
                    <div id="contract-logs-container">
                        @if ($editMode && $contract->logs && $contract->logs->count() > 0)
                            @foreach ($contract->logs as $index => $log)
                                <div class="p-3 mb-2 border dark:border-gray-700 rounded-md log-item bg-gray-50 dark:bg-gray-700/50"
                                    data-index="{{ $index }}">
                                    <input type="hidden" name="logs[{{ $index }}][id]"
                                        value="{{ $log->id }}">
                                    <div class="grid md:grid-cols-3 gap-3">
                                        <div>
                                            <label class="block text-xs dark:text-gray-300">انجام شده توسط</label>
                                            <select name="logs[{{ $index }}][performed_by]"
                                                class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                <option value="">انتخاب کاربر</option>
                                                @foreach ($users ?? [] as $user)
                                                    <option value="{{ $user->id }}"
                                                        {{ old("logs.{$index}.performed_by", $log->performed_by) == $user->id ? 'selected' : '' }}>
                                                        {{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs dark:text-gray-300">تاریخ انجام</label>
                                            <input type="text" name="logs[{{ $index }}][performed_at]"
                                                value="{{ old("logs.{$index}.performed_at", $log->formatted_performed_at) }}"
                                                placeholder="مثلا: 1403/01/15 10:30"
                                                class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white persian-date-time-picker">
                                        </div>
                                        <div class="md:col-span-3">
                                            <label class="block text-xs dark:text-gray-300">شرح فعالیت</label>
                                            <textarea name="logs[{{ $index }}][description]" rows="2"
                                                class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old("logs.{$index}.description", $log->description) }}</textarea>
                                        </div>
                                    </div>
                                    <label class="flex items-center mt-2 text-sm">
                                        <input type="checkbox" name="logs[{{ $index }}][_remove]" value="1"
                                            class="rounded border-gray-300 dark:border-gray-600 text-red-600 shadow-sm focus:ring-red-500">
                                        <span class="ms-2 text-red-600 dark:text-red-400">حذف این گزارش</span>
                                    </label>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <div id="new-logs-placeholder"></div>
                    <button type="button" id="add-contract-log-btn"
                        class="mt-2 text-sm px-3 py-1.5 bg-purple-500 hover:bg-purple-600 text-white rounded-md">+ افزودن
                        گزارش جدید</button>
                </div>

                <div class="mt-8 pt-6 border-t dark:border-gray-700">
                    <button type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-base" id="submit-btn">
                        {{ $editMode ? 'بروزرسانی قرارداد' : 'ثبت قرارداد' }}
                    </button>
                    @if ($editMode)
                        <a href="{{ route('contracts.index') }}"
                            class="px-6 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white rounded hover:bg-gray-400 dark:hover:bg-gray-700 ms-2 text-base">لغو</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Contracts List Section --}}
        @if (isset($contractsList) && !$editMode)
            <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
                {{-- CORRECTED: Added responsive flex classes --}}
                <form method="GET" action="{{ route('contracts.index') }}"
                    class="w-full flex flex-col md:flex-row md:items-center gap-2">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="جستجو در توضیحات یا نام مشتری..."
                        class="w-full md:flex-grow rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white py-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                        <select name="sort_field"
                            class="w-full sm:w-auto rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white py-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="created_at"
                                {{ request('sort_field', 'created_at') == 'created_at' ? 'selected' : '' }}>تاریخ ایجاد
                            </option>
                            <option value="total_price" {{ request('sort_field') == 'total_price' ? 'selected' : '' }}>
                                مبلغ کل</option>
                            <option value="status" {{ request('sort_field') == 'status' ? 'selected' : '' }}>وضعیت
                            </option>
                        </select>
                        <select name="sort_direction"
                            class="w-full sm:w-auto rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white py-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="desc" {{ request('sort_direction', 'desc') == 'desc' ? 'selected' : '' }}>
                                نزولی</option>
                            <option value="asc" {{ request('sort_direction') == 'asc' ? 'selected' : '' }}>صعودی
                            </option>
                        </select>
                    </div>
                    <button type="submit"
                        class="w-full md:w-auto px-4 py-2 bg-indigo-500 text-white rounded shadow-sm hover:bg-indigo-600">اعمال</button>
                    @if (request('search') || request('sort_field') || request('sort_direction'))
                        <a href="{{ route('contracts.index') }}"
                            class="w-full md:w-auto text-center mt-2 md:mt-0 md:ms-2 text-red-600 hover:underline px-3 py-2 rounded-md border border-red-500 hover:bg-red-50 dark:hover:bg-red-900">پاک
                            کردن فیلتر</a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl shadow">
                <table class="w-full text-sm text-right text-gray-500 dark:text-gray-300">
                    <thead class="text-xs uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400 dark:text-white">
                        <tr>
                            <th class="p-3">مشتری</th>
                            <th class="p-3">آدرس</th>
                            <th class="p-3">تعداد توقف</th>
                            <th class="p-3">مبلغ کل</th>
                            <th class="p-3">تکنسین</th>
                            <th class="p-3">وضعیت</th>
                            <th class="p-3">تاریخ ایجاد</th>
                            <th class="p-3">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contractsList ?? [] as $contractItem)
                            <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="p-3">{{ $contractItem->customer->name ?? 'N/A' }}</td>
                                <td class="p-3">{{ $contractItem->address->address ?? 'N/A' }}</td>
                                <td class="p-3">{{ $contractItem->stop_count }}</td>
                                <td class="p-3">{{ number_format($contractItem->total_price) }} تومان</td>
                                <td class="p-3">{{ $contractItem->assignedUser->name ?? 'مشخص نشده' }}</td>
                                <td class="p-3">
                                    @php
                                        $statusText = match ($contractItem->status) {
                                            'draft' => 'پیش‌نویس',
                                            'active' => 'فعال',
                                            'completed' => 'تکمیل شده',
                                            'cancelled' => 'لغو شده',
                                            default => $contractItem->status,
                                        };
                                        $statusColor = match ($contractItem->status) {
                                            'draft'
                                                => 'bg-yellow-200 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100',
                                            'active'
                                                => 'bg-green-200 text-green-800 dark:bg-green-700 dark:text-green-100',
                                            'completed'
                                                => 'bg-blue-200 text-blue-800 dark:bg-blue-700 dark:text-blue-100',
                                            'cancelled' => 'bg-red-200 text-red-800 dark:bg-red-700 dark:text-red-100',
                                            default => 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-100',
                                        };
                                    @endphp
                                    <span
                                        class="px-2 py-1 text-xs rounded-full {{ $statusColor }}">{{ $statusText }}</span>
                                </td>
                                <td class="p-3">{{ $contractItem->formatted_created_at ?: 'N/A' }}</td>
                                <td class="p-3 whitespace-nowrap">
                                    <a href="{{ route('contracts.edit', $contractItem->id) }}"
                                        class="text-blue-600 hover:text-blue-800 dark:hover:text-blue-400 px-2 py-1">ویرایش</a>
                                    <a href="{{ route('contracts.print', $contractItem->id) }}" target="_blank"
                                        class="text-green-600 hover:text-green-800 dark:hover:text-green-400 px-2 py-1">چاپ</a>
                                    <form action="{{ route('contracts.destroy', $contractItem->id) }}" method="POST"
                                        class="inline-block"
                                        onsubmit="return confirm('آیا از حذف این قرارداد مطمئن هستید؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="text-red-600 hover:text-red-800 dark:hover:text-red-400 px-2 py-1">حذف</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-4 text-center text-gray-500 dark:text-gray-400">
                                    قراردادی یافت نشد.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                @if (isset($contractsList) && $contractsList->count() > 0)
                    {{ $contractsList->withQueryString()->links() }}
                @endif
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Customer Address Logic (Unchanged)
            const customerSelect = document.getElementById('customer_id');
            const addressSelect = document.getElementById('customer_address_id');
            const customersWithAddresses = @json($customersWithAddresses ?? []);
            // ... (all the address population logic remains the same)
            function populateAddresses(customerId, selectedAddressId = null) {
                if (!addressSelect) return;
                addressSelect.innerHTML = '<option value="">درحال بارگذاری آدرس‌ها...</option>';
                const selectedCustomer = customersWithAddresses.find(c => c.id == customerId);
                if (selectedCustomer && selectedCustomer.addresses && selectedCustomer.addresses.length > 0) {
                    addressSelect.innerHTML = '<option value="">انتخاب کنید</option>';
                    selectedCustomer.addresses.forEach(address => {
                        const option = document.createElement('option');
                        option.value = address.id;
                        option.textContent = (address.label ? address.label + ': ' : '') + address.address;
                        if (selectedAddressId && address.id == selectedAddressId) {
                            option.selected = true;
                        }
                        addressSelect.appendChild(option);
                    });
                } else {
                    addressSelect.innerHTML = '<option value="">آدرسی برای این مشتری یافت نشد</option>';
                }
            }
            if (customerSelect && customerSelect.value) {
                const preselectedAddressId =
                    "{{ old('customer_address_id', $contract->customer_address_id ?? '') }}";
                if (!addressSelect.value || addressSelect.options.length <= 1) {
                    populateAddresses(customerSelect.value, preselectedAddressId);
                }
            }
            if (customerSelect) {
                customerSelect.addEventListener('change', (e) => populateAddresses(e.target.value));
            }

            // --- START: EQUIPMENT & PRICE CALCULATION LOGIC (REVISED) ---

            const equipmentsWrapper = document.getElementById('equipments-section-wrapper');

            function updateTotalPrice(rowElement) {
                const quantityInput = rowElement.querySelector('.quantity-input');
                const unitPriceInput = rowElement.querySelector('.unit-price-input');
                const totalPriceDisplay = rowElement.querySelector('.total-price-display');
                if (!quantityInput || !unitPriceInput || !totalPriceDisplay) return;

                const quantity = parseFloat(quantityInput.value) || 0;
                const unitPrice = parseFloat(unitPriceInput.value) || 0;
                const total = quantity * unitPrice;
                totalPriceDisplay.value = total.toLocaleString('fa-IR');
            }

            // Initial calculation for existing items
            equipmentsWrapper.querySelectorAll('.equipment-item').forEach(updateTotalPrice);
            equipmentsWrapper.addEventListener('input', function(event) {
                if (event.target.classList.contains('quantity-input') || event.target.classList.contains(
                        'unit-price-input')) {
                    const row = event.target.closest('.equipment-item, .new-equipment-item');
                    if (row) {
                        updateTotalPrice(row);
                    }
                }
            });

            // Dynamic Equipments Logic (unchanged, but now works with the corrected listener)
            const addEquipmentBtn = document.getElementById('add-contract-equipment-btn');
            const newEquipmentsPlaceholder = document.getElementById('new-equipments-placeholder');
            let newEquipmentIndex =
                {{ ($editMode ?? false) && $contract->equipments ? $contract->equipments->count() : 0 }};

            if (addEquipmentBtn && newEquipmentsPlaceholder) {
                addEquipmentBtn.addEventListener('click', () => {
                    const div = document.createElement('div');
                    div.classList.add('p-3', 'mb-2', 'border', 'dark:border-gray-600', 'rounded-md',
                        'bg-gray-50', 'dark:bg-gray-700', 'new-equipment-item');
                    div.dataset.index = newEquipmentIndex;
                    let equipmentOptions = '<option value="">انتخاب تجهیز</option>';
                    if (typeof availableEquipmentsData !== 'undefined') {
                        availableEquipmentsData.forEach(eq => {
                            equipmentOptions +=
                                `<option value="${eq.id}" data-price="${eq.price}" data-stock="${eq.stock_quantity}">${eq.name} (موجودی: ${eq.stock_quantity} - قیمت: ${eq.price})</option>`;
                        });
                    }
                    div.innerHTML = `
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="text-md font-semibold dark:text-indigo-300">تجهیز جدید #${newEquipmentIndex + 1}</h4>
                        <button type="button" class="text-red-500 hover:text-red-700 remove-new-item-btn">&times; حذف</button>
                    </div>
                    <div class="grid md:grid-cols-5 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-xs dark:text-gray-300">انتخاب تجهیز</label>
                            <select name="new_equipments[${newEquipmentIndex}][equipment_id]" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white new-equipment-select">${equipmentOptions}</select>
                        </div>
                        <div>
                            <label class="block text-xs dark:text-gray-300">تعداد</label>
                            <input type="number" name="new_equipments[${newEquipmentIndex}][quantity]" value="1" min="1" class="quantity-input mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs dark:text-gray-300">قیمت واحد</label>
                            <input type="number" name="new_equipments[${newEquipmentIndex}][unit_price]" value="0" min="0" class="unit-price-input mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white new-equipment-price">
                        </div>
                        <div>
                            <label class="block text-xs dark:text-gray-300">قیمت کل (تومان)</label>
                            <input type="text" readonly class="total-price-display mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        </div>
                        <div class="md:col-span-5">
                            <label class="block text-xs dark:text-gray-300">یادداشت</label>
                            <input type="text" name="new_equipments[${newEquipmentIndex}][notes]" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                        </div>
                    </div>`;
                    newEquipmentsPlaceholder.appendChild(div);
                    newEquipmentIndex++;
                });

                newEquipmentsPlaceholder.addEventListener('change', function(event) {
                    if (event.target.classList.contains('new-equipment-select')) {
                        const selectedOption = event.target.options[event.target.selectedIndex];
                        const row = event.target.closest('.new-equipment-item');
                        const priceInput = row.querySelector('.new-equipment-price');
                        if (selectedOption && priceInput) {
                            priceInput.value = selectedOption.dataset.price || 0;
                            updateTotalPrice(row);
                        }
                    }
                });
            }
            // --- Other Dynamic Sections (Payments, Logs - Unchanged) ---
            // Dynamic Payments
            const addPaymentBtn = document.getElementById('add-contract-payment-btn');
            const newPaymentsPlaceholder = document.getElementById('new-payments-placeholder');
            let newPaymentIndex =
                {{ ($editMode ?? false) && $contract->payments ? $contract->payments->count() : 0 }};

            if (addPaymentBtn && newPaymentsPlaceholder) {
                addPaymentBtn.addEventListener('click', () => {
                    const div = document.createElement('div');
                    div.classList.add('p-3', 'mb-2', 'border', 'dark:border-gray-600', 'rounded-md',
                        'bg-gray-50', 'dark:bg-gray-700', 'new-payment-item');
                    div.dataset.index = newPaymentIndex;
                    const defaultPaidAtValue = (typeof currentJalaliDateTimeForJS !== 'undefined') ?
                        currentJalaliDateTimeForJS : '';

                    div.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <h4 class="text-md font-semibold dark:text-indigo-300">پرداخت جدید #${newPaymentIndex + 1}</h4>
                    <button type="button" class="text-red-500 hover:text-red-700 remove-new-item-btn">&times; حذف</button>
                </div>
                <div class="grid md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs dark:text-gray-300">عنوان پرداخت</label>
                        <input type="text" name="new_payments[${newPaymentIndex}][title]" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs dark:text-gray-300">مبلغ (تومان)</label>
                        <input type="number" name="new_payments[${newPaymentIndex}][amount]" value="0" min="0" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs dark:text-gray-300">تاریخ پرداخت</label>
                        <input type="text" name="new_payments[${newPaymentIndex}][paid_at]" value="${defaultPaidAtValue}" placeholder="مثلا: 1403/01/15 10:30" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white persian-date-time-picker">
                    </div>
                    <div class="md:col-span-4">
                        <label class="block text-xs dark:text-gray-300">یادداشت</label>
                        <input type="text" name="new_payments[${newPaymentIndex}][note]" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                    </div>
                </div>
            `;
                    newPaymentsPlaceholder.appendChild(div);
                    newPaymentIndex++;
                });
            }

            // Dynamic Checks
            const addCheckBtn = document.getElementById('add-contract-check-btn');
            const newChecksPlaceholder = document.getElementById('new-checks-placeholder');
            let newCheckIndex = {{ ($editMode ?? false) && $contract->checks ? $contract->checks->count() : 0 }};

            if (addCheckBtn && newChecksPlaceholder) {
                addCheckBtn.addEventListener('click', () => {
                    const div = document.createElement('div');
                    div.classList.add('p-3', 'mb-2', 'border', 'dark:border-gray-600', 'rounded-md',
                        'bg-gray-50', 'dark:bg-gray-700', 'new-check-item');
                    div.dataset.index = newCheckIndex;
                    const defaultDateValue = (typeof currentJalaliDateForJS !== 'undefined') ?
                        currentJalaliDateForJS : '';

                    div.innerHTML = `
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="text-md font-semibold dark:text-indigo-300">چک جدید #${newCheckIndex + 1}</h4>
                        <button type="button" class="text-red-500 hover:text-red-700 remove-new-item-btn">&times; حذف</button>
                    </div>
                    <div class="grid md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs dark:text-gray-300">تاریخ چک</label>
                            <input type="text" name="new_checks[${newCheckIndex}][date]" value="${defaultDateValue}" placeholder="مثلا: 1403/01/15" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white persian-date-picker">
                        </div>
                        <div>
                            <label class="block text-xs dark:text-gray-300">مبلغ (تومان)</label>
                            <input type="number" name="new_checks[${newCheckIndex}][amount]" value="0" min="0" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs dark:text-gray-300">نام بانک</label>
                            <input type="text" name="new_checks[${newCheckIndex}][bank_name]" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs dark:text-gray-300">شماره سریال</label>
                            <input type="text" name="new_checks[${newCheckIndex}][serial_number]" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs dark:text-gray-300">شماره صیادی</label>
                            <input type="text" name="new_checks[${newCheckIndex}][sayadi_number]" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs dark:text-gray-300">در وجه</label>
                            <input type="text" name="new_checks[${newCheckIndex}][in_name_of]" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                        </div>
                        <div class="md:col-span-2">
                             <label class="block text-xs dark:text-gray-300">وضعیت</label>
                             <select name="new_checks[${newCheckIndex}][status]" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                                 <option value="draft" selected>پیش نویس</option>
                                 <option value="cashed">پاس شده</option>
                                 <option value="bounced">برگشت خورده</option>
                                 <option value="referred">ارجاع شده</option>
                             </select>
                        </div>
                    </div>`;
                    newChecksPlaceholder.appendChild(div);
                    newCheckIndex++;
                });
            }


            // Dynamic Logs
            const addContractLogBtn = document.getElementById('add-contract-log-btn');
            const newLogsPlaceholder = document.getElementById('new-logs-placeholder');
            let newLogIndex = {{ ($editMode ?? false) && $contract->logs ? $contract->logs->count() : 0 }};

            if (addContractLogBtn && newLogsPlaceholder) {
                addContractLogBtn.addEventListener('click', () => {
                    const div = document.createElement('div');
                    div.classList.add('p-3', 'mb-2', 'border', 'dark:border-gray-600', 'rounded-md',
                        'bg-gray-50', 'dark:bg-gray-700', 'new-log-item');
                    div.dataset.index = newLogIndex;

                    let userOptions = '<option value="">انتخاب کاربر</option>';
                    if (typeof usersDataForJS !== 'undefined') {
                        usersDataForJS.forEach(user => {
                            let selected = user.id == {{ Auth::id() }} ? 'selected' : '';
                            userOptions +=
                                `<option value="${user.id}" ${selected}>${user.name}</option>`;
                        });
                    }

                    const defaultPerformedAtValue = (typeof currentJalaliDateTimeForJS !== 'undefined') ?
                        currentJalaliDateTimeForJS : '';

                    div.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <h4 class="text-md font-semibold dark:text-indigo-300">گزارش جدید #${newLogIndex + 1}</h4>
                    <button type="button" class="text-red-500 hover:text-red-700 remove-new-item-btn">&times; حذف</button>
                </div>
                <div class="grid md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs dark:text-gray-300">انجام شده توسط</label>
                        <select name="new_logs[${newLogIndex}][performed_by]" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                            ${userOptions}
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs dark:text-gray-300">تاریخ انجام</label>
                        <input type="text" name="new_logs[${newLogIndex}][performed_at]" value="${defaultPerformedAtValue}" placeholder="مثلا: 1403/01/15 10:30" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white persian-date-time-picker">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs dark:text-gray-300">شرح فعالیت</label>
                        <textarea name="new_logs[${newLogIndex}][description]" rows="2" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white"></textarea>
                    </div>
                </div>
            `;
                    newLogsPlaceholder.appendChild(div);
                    newLogIndex++;
                });
            }

            // Generic remove button listener
            document.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-new-item-btn')) {
                    const itemToRemove = event.target.closest(
                        '.new-equipment-item, .new-payment-item, .new-check-item, .new-log-item');
                    if (itemToRemove) {
                        itemToRemove.remove();
                    }
                }
            });
        });
    </script>
@endsection
