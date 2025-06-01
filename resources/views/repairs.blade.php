@extends('layouts.base')

@section('sidebar')
    @include('layouts.sidebar')
@endsection

@section('content')
    {{-- Helper for Str::words --}}
    @php
        use Illuminate\Support\Str;
        // Ensure $repair is defined, even if it's a new instance for create form
        $repair = $repair ?? new \App\Models\Repair();
        // Ensure $editMode is defined
        $editMode = $editMode ?? false;
    @endphp

    <div class="container mx-auto px-4 py-6">
        <h2 class="text-xl font-bold mb-4 dark:text-white">
            @if (Route::currentRouteName() === 'repairs.create')
                ایجاد تعمیر جدید
            @elseif($editMode)
                ویرایش تعمیر: {{ $repair->title ?? '' }} (مشتری: {{ $repair->customer->name ?? 'N/A' }})
            @else
                مدیریت تعمیرات
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
            // Make current Jalali date available to JS, ensure $currentJalaliDateTime is passed from controller
            const currentJalaliDateTimeForJS_Repairs = @json($currentJalaliDateTime ?? \Morilog\Jalali\Jalalian::now()->format('Y/m/d H:i'));
        </script>

        {{-- Form is shown for create route, edit route, or index route when not editing --}}
        @if (Route::currentRouteName() === 'repairs.create' ||
                $editMode ||
                (Route::currentRouteName() === 'repairs.index' && !$editMode))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
                <form method="POST" action="{{ $editMode ? route('repairs.update', $repair->id) : route('repairs.store') }}"
                    id="repair-form" novalidate>
                    @csrf
                    @if ($editMode)
                        @method('PUT')
                    @endif

                    <input type="hidden" name="id" value="{{ old('id', $repair->id ?? '') }}">

                    <h3 class="text-lg font-semibold mb-3 dark:text-white border-b pb-2 dark:border-gray-700">اطلاعات اصلی
                        تعمیر</h3>
                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        {{-- Customer --}}
                        <div>
                            <label class="block text-sm font-medium dark:text-white" for="repair_customer_id">مشتری</label>
                            <select id="repair_customer_id" name="customer_id"
                                class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                {{ $editMode && isset($repair) && $repair->customer_id ? 'disabled' : '' }}>
                                <option value="">انتخاب کنید</option>
                                @foreach ($customers ?? [] as $customerOption)
                                    <option value="{{ $customerOption->id }}"
                                        {{ old('customer_id', $repair->customer_id ?? '') == $customerOption->id ? 'selected' : '' }}>
                                        {{ $customerOption->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($editMode && isset($repair) && $repair->customer_id)
                                <input type="hidden" name="customer_id" value="{{ $repair->customer_id }}">
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">مشتری برای تعمیرات ثبت شده قابل
                                    تغییر نیست.</p>
                            @endif
                            @error('customer_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Customer Address --}}
                        <div>
                            <label class="block text-sm font-medium dark:text-white" for="repair_customer_address_id">آدرس
                                مشتری</label>
                            <select id="repair_customer_address_id" name="customer_address_id"
                                class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">ابتدا مشتری را انتخاب کنید</option>
                                @php
                                    $currentRepairCustomerId = old('customer_id', $repair->customer_id ?? null);
                                    $currentRepairAddressId = old(
                                        'customer_address_id',
                                        $repair->customer_address_id ?? null,
                                    );
                                    if ($currentRepairCustomerId && isset($customersWithAddresses)) {
                                        $customerForAddress = $customersWithAddresses->firstWhere(
                                            'id',
                                            $currentRepairCustomerId,
                                        );
                                        if ($customerForAddress && $customerForAddress->addresses) {
                                            foreach ($customerForAddress->addresses as $address) {
                                                echo "<option value='{$address->id}' " .
                                                    ($currentRepairAddressId == $address->id ? 'selected' : '') .
                                                    '>' .
                                                    e($address->label ? $address->label . ': ' : '') .
                                                    e($address->address) .
                                                    '</option>';
                                            }
                                        }
                                    }
                                @endphp
                            </select>
                            @error('customer_address_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Title --}}
                        <div>
                            <label class="block text-sm font-medium dark:text-white" for="repair_title">عنوان تعمیر</label>
                            <input type="text" id="repair_title" name="title"
                                value="{{ old('title', $repair->title ?? '') }}"
                                class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            @error('title')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Cost --}}
                        <div>
                            <label class="block text-sm font-medium dark:text-white" for="repair_cost">هزینه تخمینی
                                (تومان)</label>
                            <input type="number" id="repair_cost" name="cost"
                                value="{{ old('cost', $repair->cost ?? '0') }}" min="0"
                                class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            @error('cost')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Performed Date --}}
                        <div>
                            <label class="block text-sm font-medium dark:text-white" for="repair_performed_date">تاریخ
                                انجام</label>
                            <input type="text" id="repair_performed_date" name="performed_date"
                                value="{{ old('performed_date', $repair->performed_date ? ($repair->performed_date instanceof \Carbon\Carbon ? \Morilog\Jalali\Jalalian::fromCarbon($repair->performed_date)->format('Y/m/d') : $repair->performed_date) : \Morilog\Jalali\Jalalian::now()->format('Y/m/d')) }}"
                                placeholder="مثلا: 1403/01/15"
                                class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 persian-date-picker">
                            @error('performed_date')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Assigned To --}}
                        <div>
                            <label class="block text-sm font-medium dark:text-white" for="repair_assigned_to">تکنسین
                                مسئول</label>
                            <select id="repair_assigned_to" name="assigned_to"
                                class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="">انتخاب کنید</option>
                                @foreach ($users ?? [] as $user)
                                    <option value="{{ $user->id }}"
                                        {{ old('assigned_to', $repair->assigned_to ?? '') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_to')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium dark:text-white" for="repair_description">شرح
                                مشکل/تعمیر</label>
                            <textarea id="repair_description" name="description" rows="3"
                                class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('description', $repair->description ?? '') }}</textarea>
                            @error('description')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="flex items-center dark:text-white mt-2">
                                <input type="checkbox" name="sms_sent" value="1"
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    {{ old('sms_sent', $repair->sms_sent ?? false) ? 'checked' : '' }}>
                                <span class="ms-2">پیامک اطلاع رسانی ارسال شود</span>
                            </label>
                        </div>
                    </div>

                    {{-- Equipments Section --}}
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold mb-3 dark:text-white border-b pb-2 dark:border-gray-700">تجهیزات
                            مصرفی</h3>
                        <div id="repair-equipments-container">
                            @if ($editMode && $repair->equipments && $repair->equipments->count() > 0)
                                @foreach ($repair->equipments as $index => $repairEquipment)
                                    <div class="p-3 mb-2 border dark:border-gray-700 rounded-md equipment-item"
                                        data-index="{{ $index }}">
                                        <input type="hidden" name="equipments[{{ $index }}][id]"
                                            value="{{ $repairEquipment->id }}">
                                        <p class="font-semibold dark:text-indigo-400">
                                            {{ $repairEquipment->equipment->name ?? 'تجهیز نامشخص' }}
                                            (موجودی انبار:
                                            {{ $repairEquipment->equipment ? $repairEquipment->equipment->stock_quantity + $repairEquipment->quantity : 'N/A' }})
                                        </p>
                                        <div class="grid md:grid-cols-4 gap-3 mt-2">
                                            <div>
                                                <label class="block text-xs dark:text-gray-300">تعداد</label>
                                                <input type="number" name="equipments[{{ $index }}][quantity]"
                                                    value="{{ old("equipments.{$index}.quantity", $repairEquipment->quantity) }}"
                                                    min="0"
                                                    class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            </div>
                                            <div>
                                                <label class="block text-xs dark:text-gray-300">قیمت واحد (تومان)</label>
                                                <input type="number" name="equipments[{{ $index }}][unit_price]"
                                                    value="{{ old("equipments.{$index}.unit_price", $repairEquipment->unit_price) }}"
                                                    min="0"
                                                    class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-xs dark:text-gray-300">یادداشت</label>
                                                <input type="text" name="equipments[{{ $index }}][notes]"
                                                    value="{{ old("equipments.{$index}.notes", $repairEquipment->notes) }}"
                                                    class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
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
                        <div id="new-repair-equipments-placeholder"></div>
                        <button type="button" id="add-repair-equipment-btn"
                            class="mt-2 text-sm px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white rounded-md">+ افزودن
                            تجهیز جدید</button>
                    </div>

                    {{-- Payments Section --}}
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold mb-3 dark:text-white border-b pb-2 dark:border-gray-700">پرداخت‌ها
                        </h3>
                        <div id="repair-payments-container">
                            @if ($editMode && $repair->payments && $repair->payments->count() > 0)
                                @foreach ($repair->payments as $index => $payment)
                                    <div class="p-3 mb-2 border dark:border-gray-700 rounded-md payment-item"
                                        data-index="{{ $index }}">
                                        <input type="hidden" name="payments[{{ $index }}][id]"
                                            value="{{ $payment->id }}">
                                        <div class="grid md:grid-cols-4 gap-3">
                                            <div class="md:col-span-2"> {{-- Title for payment --}}
                                                <label class="block text-xs dark:text-gray-300">عنوان/شرح پرداخت</label>
                                                <input type="text" name="payments[{{ $index }}][title]"
                                                    value="{{ old("payments.{$index}.title", $payment->title ?? 'پرداخت تعمیر') }}"
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
                        <div id="new-repair-payments-placeholder"></div>
                        <button type="button" id="add-repair-payment-btn"
                            class="mt-2 text-sm px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded-md">+ افزودن
                            پرداخت جدید</button>
                    </div>

                    <div class="mt-8 pt-6 border-t dark:border-gray-700">
                        <button type="submit"
                            class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-base"
                            id="submit-repair-btn">
                            {{ $editMode ? 'بروزرسانی تعمیر' : 'ثبت تعمیر' }}
                        </button>
                        @if ($editMode)
                            <a href="{{ route('repairs.index') }}"
                                class="px-6 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white rounded hover:bg-gray-400 dark:hover:bg-gray-700 ms-2 text-base">لغو</a>
                        @endif
                    </div>
                    <p id="general-repair-error" class="text-red-600 mt-3 hidden"></p>
                </form>
            </div>
        @endif

        {{-- Repairs List --}}
        @if (isset($repairsList) && (!($editMode ?? false) || Route::currentRouteName() === 'repairs.index'))
            <div class="mb-4">
                <div class="flex flex-col md:flex-row justify-between items-center gap-2 md:gap-4">
                    <form method="GET" action="{{ route('repairs.index') }}"
                        class="w-full flex flex-col md:flex-row md:items-center gap-2">
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="جستجو در عنوان، توضیحات یا نام مشتری..."
                            class="w-full md:flex-grow rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white px-3 py-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                            <select name="sort_field"
                                class="w-full sm:w-auto rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white py-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="performed_date"
                                    {{ request('sort_field', 'performed_date') == 'performed_date' ? 'selected' : '' }}>
                                    تاریخ انجام</option>
                                <option value="cost" {{ request('sort_field') == 'cost' ? 'selected' : '' }}>هزینه
                                </option>
                                <option value="created_at" {{ request('sort_field') == 'created_at' ? 'selected' : '' }}>
                                    تاریخ ثبت</option>
                            </select>
                            <select name="sort_direction"
                                class="w-full sm:w-auto rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white py-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="desc"
                                    {{ request('sort_direction', 'desc') == 'desc' ? 'selected' : '' }}>نزولی</option>
                                <option value="asc" {{ request('sort_direction') == 'asc' ? 'selected' : '' }}>صعودی
                                </option>
                            </select>
                        </div>
                        <button type="submit"
                            class="w-full md:w-auto px-4 py-2 bg-indigo-500 text-white rounded shadow-sm hover:bg-indigo-600">اعمال</button>
                        @if (request('search') || request('sort_field') || request('sort_direction'))
                            <a href="{{ route('repairs.index') }}"
                                class="w-full md:w-auto text-center mt-2 md:mt-0 md:ms-2 text-red-600 hover:underline px-3 py-2 rounded-md border border-red-500 hover:bg-red-50 dark:hover:bg-red-900">پاک
                                کردن فیلتر</a>
                        @endif
                    </form>
                    {{-- No separate "Create New Repair" button here as form is above on index --}}
                </div>
            </div>

            <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl shadow">
                <table class="w-full text-sm text-right text-gray-500 dark:text-gray-300">
                    <thead class="text-xs uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400 dark:text-white">
                        <tr>
                            <th class="p-3">مشتری</th>
                            <th class="p-3">عنوان</th>
                            <th class="p-3">شرح مختصر</th>
                            <th class="p-3">هزینه (تومان)</th>
                            <th class="p-3">تکنسین</th>
                            <th class="p-3">تاریخ انجام</th>
                            <th class="p-3">تاریخ ثبت</th>
                            <th class="p-3">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($repairsList ?? [] as $repairItem)
                            <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="p-3">{{ $repairItem->customer->name ?? 'N/A' }}</td>
                                <td class="p-3">{{ $repairItem->title }}</td>
                                <td class="p-3">{{ Str::words($repairItem->description ?? '', 5, '...') }}</td>
                                <td class="p-3">{{ number_format($repairItem->cost) }}</td>
                                <td class="p-3">{{ $repairItem->user->name ?? 'مشخص نشده' }}</td>
                                {{-- Assuming 'user' is the relation for assigned_to --}}
                                <td class="p-3">{{ $repairItem->formatted_performed_date ?: 'N/A' }}</td>
                                <td class="p-3">{{ $repairItem->formatted_created_at ?: 'N/A' }}</td>
                                <td class="p-3 whitespace-nowrap">
                                    <a href="{{ route('repairs.edit', $repairItem->id) }}"
                                        class="text-blue-600 hover:text-blue-800 dark:hover:text-blue-400 px-2 py-1">ویرایش</a>
                                    <form action="{{ route('repairs.destroy', $repairItem->id) }}" method="POST"
                                        class="inline-block"
                                        onsubmit="return confirm('آیا از حذف این تعمیر مطمئن هستید؟ موجودی تجهیزات مصرفی به انبار بازگردانده خواهد شد.');">
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
                                    تعمیری یافت نشد.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                @if (isset($repairsList) &&
                        $repairsList instanceof \Illuminate\Pagination\LengthAwarePaginator &&
                        $repairsList->count() > 0)
                    {{ $repairsList->withQueryString()->links() }}
                @endif
            </div>
        @endif
    </div>

    <script>
        // Ensure currentJalaliDateTimeForJS_Repairs is defined (from the script tag above the main script)
        // const currentJalaliDateTimeForJS_Repairs = @json($currentJalaliDateTime ?? \Morilog\Jalali\Jalalian::now()->format('Y/m/d H:i'));
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const repairCustomerSelect = document.getElementById('repair_customer_id');
            const repairAddressSelect = document.getElementById('repair_customer_address_id');
            const repairCustomersWithAddressesData = typeof customersWithAddresses !== 'undefined' ?
                customersWithAddresses : @json($customersWithAddresses ?? []);
            const repairAvailableEquipmentsData = typeof availableEquipments !== 'undefined' ? availableEquipments :
                @json($availableEquipments ?? []);

            function populateRepairAddresses(customerId, selectedAddressId = null) {
                if (!repairAddressSelect) return;
                repairAddressSelect.innerHTML = '<option value="">درحال بارگذاری آدرس‌ها...</option>';
                const selectedCustomer = repairCustomersWithAddressesData.find(c => String(c.id) === String(
                    customerId));

                if (selectedCustomer && selectedCustomer.addresses && selectedCustomer.addresses.length > 0) {
                    repairAddressSelect.innerHTML = '<option value="">انتخاب کنید</option>';
                    selectedCustomer.addresses.forEach(address => {
                        const option = document.createElement('option');
                        option.value = address.id;
                        option.textContent = (address.label ? address.label + ': ' : '') + address.address;
                        if (selectedAddressId && String(address.id) === String(selectedAddressId)) {
                            option.selected = true;
                        }
                        repairAddressSelect.appendChild(option);
                    });
                } else {
                    repairAddressSelect.innerHTML = '<option value="">آدرسی برای این مشتری یافت نشد</option>';
                }
            }

            if (repairCustomerSelect) {
                if (repairCustomerSelect.value) {
                    const preselectedAddressId =
                        "{{ old('customer_address_id', ($editMode ?? false) && isset($repair) ? $repair->customer_address_id : '') }}";
                    if (repairAddressSelect && (!repairAddressSelect.value || repairAddressSelect.options.length <=
                            1 || (repairAddressSelect.value && preselectedAddressId && String(repairAddressSelect
                                .value) !== String(preselectedAddressId)))) {
                        populateRepairAddresses(repairCustomerSelect.value, preselectedAddressId);
                    }
                }
                repairCustomerSelect.addEventListener('change', (e) => {
                    const customerId = e.target.value;
                    populateRepairAddresses(customerId);
                });
            }

            // Dynamic Equipments for Repairs
            const addRepairEquipmentBtn = document.getElementById('add-repair-equipment-btn');
            const newRepairEquipmentsPlaceholder = document.getElementById('new-repair-equipments-placeholder');
            let newRepairEquipmentDynamicIndex =
                {{ ($editMode ?? false) && isset($repair) && $repair->equipments ? $repair->equipments->count() : 0 }};

            if (addRepairEquipmentBtn && newRepairEquipmentsPlaceholder) {
                addRepairEquipmentBtn.addEventListener('click', () => {
                    const div = document.createElement('div');
                    div.classList.add('p-3', 'mb-2', 'border', 'dark:border-gray-600', 'rounded-md',
                        'bg-gray-50', 'dark:bg-gray-700', 'new-equipment-item');
                    div.dataset.index = newRepairEquipmentDynamicIndex;
                    let equipmentOptions = '<option value="">انتخاب تجهیز</option>';
                    repairAvailableEquipmentsData.forEach(eq => {
                        equipmentOptions +=
                            `<option value="${eq.id}" data-price="${eq.price}" data-stock="${eq.stock_quantity}">${eq.name} (موجودی: ${eq.stock_quantity} - قیمت: ${eq.price})</option>`;
                    });
                    div.innerHTML = `
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="text-md font-semibold dark:text-indigo-300">تجهیز جدید #${newRepairEquipmentDynamicIndex + 1}</h4>
                        <button type="button" class="text-red-500 hover:text-red-700 remove-new-item-btn">&times; حذف</button>
                    </div>
                    <div class="grid md:grid-cols-4 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-xs dark:text-gray-300">انتخاب تجهیز</label>
                            <select name="new_equipments[${newRepairEquipmentDynamicIndex}][equipment_id]" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white new-equipment-select">
                                ${equipmentOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs dark:text-gray-300">تعداد</label>
                            <input type="number" name="new_equipments[${newRepairEquipmentDynamicIndex}][quantity]" value="1" min="1" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs dark:text-gray-300">قیمت واحد</label>
                            <input type="number" name="new_equipments[${newRepairEquipmentDynamicIndex}][unit_price]" value="0" min="0" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white new-equipment-price">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-xs dark:text-gray-300">یادداشت</label>
                            <input type="text" name="new_equipments[${newRepairEquipmentDynamicIndex}][notes]" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                        </div>
                    </div>
                `;
                    newRepairEquipmentsPlaceholder.appendChild(div);
                    newRepairEquipmentDynamicIndex++;
                });

                newRepairEquipmentsPlaceholder.addEventListener('change', function(event) {
                    if (event.target.classList.contains('new-equipment-select')) {
                        const selectedOption = event.target.options[event.target.selectedIndex];
                        const priceInput = event.target.closest('.new-equipment-item').querySelector(
                            '.new-equipment-price');
                        if (selectedOption && priceInput) {
                            priceInput.value = selectedOption.dataset.price || 0;
                        }
                    }
                });
            }

            // Dynamic Payments for Repairs
            const addRepairPaymentBtn = document.getElementById('add-repair-payment-btn');
            const newRepairPaymentsPlaceholder = document.getElementById('new-repair-payments-placeholder');
            let newRepairPaymentDynamicIndex =
                {{ ($editMode ?? false) && isset($repair) && $repair->payments ? $repair->payments->count() : 0 }};

            if (addRepairPaymentBtn && newRepairPaymentsPlaceholder) {
                addRepairPaymentBtn.addEventListener('click', () => {
                    const div = document.createElement('div');
                    div.classList.add('p-3', 'mb-2', 'border', 'dark:border-gray-600', 'rounded-md',
                        'bg-gray-50', 'dark:bg-gray-700', 'new-payment-item');
                    div.dataset.index = newRepairPaymentDynamicIndex;
                    const defaultPaidAtValue = (typeof currentJalaliDateTimeForJS_Repairs !== 'undefined' &&
                        currentJalaliDateTimeForJS_Repairs) ? currentJalaliDateTimeForJS_Repairs : '';

                    div.innerHTML = `
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="text-md font-semibold dark:text-indigo-300">پرداخت جدید #${newRepairPaymentDynamicIndex + 1}</h4>
                        <button type="button" class="text-red-500 hover:text-red-700 remove-new-item-btn">&times; حذف</button>
                    </div>
                    <div class="grid md:grid-cols-4 gap-3"> {{-- Changed to 4 columns --}}
                        <div class="md:col-span-2">
                            <label class="block text-xs dark:text-gray-300">عنوان/شرح پرداخت</label>
                            <input type="text" name="new_payments[${newRepairPaymentDynamicIndex}][title]" value="پرداخت هزینه تعمیر" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs dark:text-gray-300">مبلغ (تومان)</label>
                            <input type="number" name="new_payments[${newRepairPaymentDynamicIndex}][amount]" value="0" min="0" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs dark:text-gray-300">تاریخ پرداخت</label>
                            <input type="text" name="new_payments[${newRepairPaymentDynamicIndex}][paid_at]" value="${defaultPaidAtValue}" placeholder="مثلا: 1403/01/15 10:30" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white persian-date-time-picker">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-xs dark:text-gray-300">یادداشت</label>
                            <input type="text" name="new_payments[${newRepairPaymentDynamicIndex}][note]" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">
                        </div>
                    </div>
                `;
                    newRepairPaymentsPlaceholder.appendChild(div);

                    // If you add a JS date picker library later, initialize it here for the new field:
                    const newPaidAtInput = div.querySelector('.persian-date-time-picker');
                    if (newPaidAtInput && typeof initializePersianDateTimePicker ===
                        'function') { // Assuming you'll create this function
                        // initializePersianDateTimePicker(newPaidAtInput);
                    }
                    newRepairPaymentDynamicIndex++;
                });
            }

            document.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-new-item-btn')) {
                    event.target.closest('.new-equipment-item, .new-payment-item').remove();
                }
            });

            // Initialize date pickers for any existing persian-date-picker fields (for edit mode)
            // and for the main repair_performed_date field
            document.querySelectorAll('.persian-date-picker, .persian-date-time-picker').forEach(el => {
                if (typeof initializePersianDateTimePicker ===
                    'function') { // Assuming you'll create this function
                    // initializePersianDateTimePicker(el, el.classList.contains('persian-date-time-picker'));
                } else if (typeof $ !== 'undefined' && $.fn
                    .persianDatepicker) { // Basic fallback if jQuery persianDatepicker is loaded
                    $(el).persianDatepicker({
                        format: el.classList.contains('persian-date-time-picker') ?
                            'YYYY/MM/DD HH:mm' : 'YYYY/MM/DD',
                        initialValue: !el.value, // Default to today if empty
                        timePicker: {
                            enabled: el.classList.contains('persian-date-time-picker')
                        }
                    });
                }
            });


        });
    </script>
@endsection
