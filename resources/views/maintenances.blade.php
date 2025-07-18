@extends('layouts.base')

@section('sidebar')
    @include('layouts.sidebar')
@endsection

@section('content')
    @php
        use Illuminate\Support\Str;
        use Morilog\Jalali\Jalalian;
        $maintenance = $maintenance ?? new \App\Models\Maintenance();
        $editMode = $editMode ?? false;
        $canCreate = Auth::check() && Auth::user()->isAdmin();
        $canEditThis =
            Auth::check() &&
            (Auth::user()->isAdmin() ||
                ($editMode && Auth::user()->isStaff() && $maintenance->assigned_to == Auth::id()));
        $showForm =
            Route::currentRouteName() === 'maintenances.create' ||
            $editMode ||
            (Auth::check() &&
                Auth::user()->isAdmin() &&
                Route::currentRouteName() === 'maintenances.index' &&
                !$editMode);
    @endphp

    <div class="container mx-auto px-4 py-6">
        <h2 class="text-xl font-bold mb-4 dark:text-white">
            @if (Route::currentRouteName() === 'maintenances.create' && $canCreate)
                ایجاد سرویس دوره‌ای جدید
            @elseif($editMode)
                ویرایش سرویس دوره‌ای: (مشتری: {{ $maintenance->customer->name ?? 'N/A' }})
            @else
                مدیریت سرویس‌های دوره‌ای
                @if (Auth::check() && Auth::user()->isStaff())
                    (فقط موارد ارجاع شده به شما)
                @endif
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

        {{-- This script block makes PHP data available to the main JS block below --}}
        <script>
            const currentJalaliDateTimeForJS_Maintenances = @json($currentJalaliDateTime ?? Jalalian::now()->format('Y/m/d H:i'));
            const currentJalaliDateForJS_Maintenances = @json($currentJalaliDate ?? Jalalian::now()->format('Y/m/d'));
            const availableEquipmentsData_Maintenances = @json($availableEquipments ?? []);
            const usersData_Maintenances = @json($users ?? []);
            const customersWithAddressesData_Maintenances = @json($customersWithAddresses ?? []);
        </script>

        @if ($showForm || ($editMode && $canEditThis))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
                <form method="POST"
                    action="{{ $editMode ? route('maintenances.update', $maintenance->id) : route('maintenances.store') }}"
                    id="maintenance-form" novalidate>
                    @csrf
                    @if ($editMode)
                        @method('PUT')
                    @endif

                    <input type="hidden" name="id" value="{{ old('id', $maintenance->id ?? '') }}">

                    <h3 class="text-lg font-semibold mb-3 dark:text-white border-b pb-2 dark:border-gray-700">اطلاعات اصلی
                        سرویس دوره‌ای</h3>
                    <div class="grid md:grid-cols-3 gap-6 mb-6">
                        {{-- Customer --}}
                        <div>
                            <label class="block text-sm font-medium dark:text-white"
                                for="maintenance_customer_id">مشتری</label>
                            <select id="maintenance_customer_id" name="customer_id"
                                class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                {{ ($editMode && $maintenance->customer_id) || ($editMode && Auth::user()->isStaff()) ? 'disabled' : '' }}>
                                <option value="">انتخاب کنید</option>
                                @foreach ($customers ?? [] as $customerOption)
                                    <option value="{{ $customerOption->id }}"
                                        {{ old('customer_id', $maintenance->customer_id ?? '') == $customerOption->id ? 'selected' : '' }}>
                                        {{ $customerOption->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if (($editMode && $maintenance->customer_id) || ($editMode && Auth::user()->isStaff()))
                                <input type="hidden" name="customer_id" value="{{ $maintenance->customer_id }}">
                            @endif
                            @error('customer_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Customer Address --}}
                        <div>
                            <label class="block text-sm font-medium dark:text-white"
                                for="maintenance_customer_address_id">آدرس مشتری</label>
                            <select id="maintenance_customer_address_id" name="customer_address_id"
                                class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                {{ $editMode && Auth::user()->isStaff() ? 'disabled' : '' }}>
                                <option value="">ابتدا مشتری را انتخاب کنید</option>
                                {{-- Options will be populated by JS or pre-filled by PHP if editing --}}
                                @php
                                    $currentMaintenanceCustomerId = old(
                                        'customer_id',
                                        $maintenance->customer_id ?? null,
                                    );
                                    $currentMaintenanceAddressId = old(
                                        'customer_address_id',
                                        $maintenance->customer_address_id ?? null,
                                    );
                                    if ($currentMaintenanceCustomerId && isset($customersWithAddresses)) {
                                        $customerForAddress = $customersWithAddresses->firstWhere(
                                            'id',
                                            $currentMaintenanceCustomerId,
                                        );
                                        if ($customerForAddress && $customerForAddress->addresses) {
                                            foreach ($customerForAddress->addresses as $address) {
                                                echo "<option value='{$address->id}' " .
                                                    ($currentMaintenanceAddressId == $address->id ? 'selected' : '') .
                                                    '>' .
                                                    e($address->label ? $address->label . ': ' : '') .
                                                    e($address->address) .
                                                    '</option>';
                                            }
                                        }
                                    }
                                @endphp
                            </select>
                            @if ($editMode && Auth::user()->isStaff())
                                <input type="hidden" name="customer_address_id"
                                    value="{{ $maintenance->customer_address_id }}">
                            @endif
                            @error('customer_address_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Assigned To --}}
                        <div>
                            <label class="block text-sm font-medium dark:text-white" for="maintenance_assigned_to">تکنسین
                                مسئول سرویس</label>
                            <select id="maintenance_assigned_to" name="assigned_to"
                                class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                {{ $editMode && Auth::user()->isStaff() ? 'disabled' : '' }}>
                                <option value="">انتخاب کنید</option>
                                @foreach ($users ?? [] as $user)
                                    <option value="{{ $user->id }}"
                                        {{ old('assigned_to', $maintenance->assigned_to ?? '') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($editMode && Auth::user()->isStaff())
                                <input type="hidden" name="assigned_to" value="{{ $maintenance->assigned_to }}">
                            @endif
                            @error('assigned_to')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Start Date --}}
                        <div>
                            <label class="block text-sm font-medium dark:text-white" for="maintenance_start_date">تاریخ شروع
                                سرویس</label>
                            <input type="text" id="maintenance_start_date" name="start_date"
                                value="{{ old('start_date', $maintenance->start_date ? $maintenance->formatted_start_date : $currentJalaliDate ?? '') }}"
                                placeholder="مثلا: 1403/01/15"
                                class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 persian-date-picker"
                                {{ $editMode && Auth::user()->isStaff() ? 'readonly' : '' }}>
                            @error('start_date')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Duration in Months --}}
                        <div>
                            <label class="block text-sm font-medium dark:text-white"
                                for="maintenance_duration_in_months">مدت قرارداد (ماه)</label>
                            <input type="number" id="maintenance_duration_in_months" name="duration_in_months"
                                value="{{ old('duration_in_months', $maintenance->duration_in_months ?? '12') }}"
                                min="1"
                                class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                {{ $editMode && Auth::user()->isStaff() ? 'readonly' : '' }}>
                            @error('duration_in_months')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Monthly Price --}}
                        @if (Auth::user()->isAdmin())
                            <div>
                                <label class="block text-sm font-medium dark:text-white"
                                    for="maintenance_monthly_price">هزینه
                                    ماهانه (تومان)</label>
                                <input type="number" id="maintenance_monthly_price" name="monthly_price"
                                    value="{{ old('monthly_price', $maintenance->monthly_price ?? '0') }}" min="0"
                                    class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    {{ $editMode && Auth::user()->isStaff() ? 'readonly' : '' }}>
                                @error('monthly_price')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-1">
                                <label class="block text-sm font-medium dark:text-white" for="maintenance_total_price">مبلغ
                                    کل
                                    قرارداد (تومان)</label>
                                <input type="number" id="maintenance_total_price" name="total_price"
                                    value="{{ old('total_price', $maintenance->total_price ?? '0') }}" min="0"
                                    class="mt-1 block w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    {{ $editMode && Auth::user()->isStaff() ? 'readonly' : '' }}>
                                @error('total_price')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-2 flex items-end pb-2">
                                <button type="button" id="calculate_total_price_btn"
                                    class="text-xs px-3 py-1.5 bg-cyan-500 hover:bg-cyan-600 text-white rounded-md">محاسبه
                                    خودکار مبلغ کل</button>
                            </div>
                        @endif

                        <div class="md:col-span-3">
                            <label class="flex items-center dark:text-white mt-2">
                                <input type="checkbox" name="is_active" value="1"
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    {{ old('is_active', $maintenance->is_active ?? true) ? 'checked' : '' }}
                                    {{ $editMode && Auth::user()->isStaff() ? 'disabled' : '' }}>
                                <span class="ms-2">سرویس فعال است</span>
                            </label>
                            @if ($editMode && Auth::user()->isStaff())
                                <input type="hidden" name="is_active" value="{{ $maintenance->is_active ? 1 : 0 }}">
                            @endif
                        </div>
                    </div>

                    {{-- Maintenance Logs Section --}}
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold mb-3 dark:text-white border-b pb-2 dark:border-gray-700">گزارشات
                            سرویس انجام شده</h3>
                        <div id="maintenance-logs-container">
                            @if ($editMode && $maintenance->logs && $maintenance->logs->count() > 0)
                                @foreach ($maintenance->logs as $logIndex => $log)
                                    <div class="p-4 mb-3 border dark:border-gray-600 rounded-lg maintenance-log-item bg-gray-50 dark:bg-gray-700/50"
                                        data-log-index="{{ $logIndex }}">
                                        <input type="hidden" name="logs[{{ $logIndex }}][id]"
                                            value="{{ $log->id }}">
                                        <div class="flex justify-between items-center mb-2">
                                            <h4 class="text-md font-semibold dark:text-indigo-300">گزارش سرویس
                                                #{{ $logIndex + 1 }}
                                                @if ($log->user)
                                                    (توسط: {{ $log->user->name }})
                                                @endif
                                                @if ($log->performed_at)
                                                    - {{ $log->formatted_performed_at }}
                                                @endif
                                            </h4>
                                            @if (Auth::user()->isAdmin() || (Auth::user()->isStaff() && $log->performed_by == Auth::id()))
                                                <label class="flex items-center text-sm">
                                                    <input type="checkbox" name="logs[{{ $logIndex }}][_remove]"
                                                        value="1"
                                                        class="rounded border-gray-300 dark:border-gray-600 text-red-600 shadow-sm focus:ring-red-500">
                                                    <span class="ms-2 text-red-600 dark:text-red-400">حذف این گزارش</span>
                                                </label>
                                            @endif
                                        </div>
                                        <div class="grid md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-xs dark:text-gray-300">انجام شده توسط</label>
                                                <select name="logs[{{ $logIndex }}][performed_by]"
                                                    class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white"
                                                    {{ Auth::user()->isStaff() && $log->performed_by != Auth::id() ? 'disabled' : '' }}>
                                                    <option value="">انتخاب تکنسین</option>
                                                    @foreach ($users ?? [] as $userOption)
                                                        <option value="{{ $userOption->id }}"
                                                            {{ old("logs.{$logIndex}.performed_by", $log->performed_by) == $userOption->id ? 'selected' : '' }}>
                                                            {{ $userOption->name }}</option>
                                                    @endforeach
                                                </select>
                                                @if (Auth::user()->isStaff() && $log->performed_by != Auth::id())
                                                    <input type="hidden" name="logs[{{ $logIndex }}][performed_by]"
                                                        value="{{ $log->performed_by }}">
                                                @endif
                                            </div>
                                            <div>
                                                <label class="block text-xs dark:text-gray-300">تاریخ انجام</label>
                                                <input type="text" name="logs[{{ $logIndex }}][performed_at]"
                                                    value="{{ old("logs.{$logIndex}.performed_at", $log->formatted_performed_at) }}"
                                                    placeholder="مثلا: 1403/01/15 10:30"
                                                    class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white persian-date-time-picker"
                                                    {{ Auth::user()->isStaff() && $log->performed_by != Auth::id() ? 'readonly' : '' }}>
                                            </div>
                                            <div class="flex items-end">
                                                <label class="flex items-center text-sm dark:text-white">
                                                    <input type="checkbox" name="logs[{{ $logIndex }}][sms_sent]"
                                                        value="1" class="rounded"
                                                        {{ old("logs.{$logIndex}.sms_sent", $log->sms_sent) ? 'checked' : '' }}
                                                        {{ Auth::user()->isStaff() && $log->performed_by != Auth::id() ? 'disabled' : '' }}>
                                                    <span class="ms-2">پیامک ارسال شد</span>
                                                </label>
                                                @if (Auth::user()->isStaff() && $log->performed_by != Auth::id())
                                                    <input type="hidden" name="logs[{{ $logIndex }}][sms_sent]"
                                                        value="{{ $log->sms_sent ? 1 : 0 }}">
                                                @endif
                                            </div>
                                            <div class="md:col-span-3">
                                                <label class="block text-xs dark:text-gray-300">یادداشت گزارش</label>
                                                <textarea name="logs[{{ $logIndex }}][note]" rows="2"
                                                    class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white"
                                                    {{ Auth::user()->isStaff() && $log->performed_by != Auth::id() ? 'readonly' : '' }}>{{ old("logs.{$logIndex}.note", $log->note) }}</textarea>
                                            </div>
                                        </div>

                                        <div class="mt-3 pt-3 border-t dark:border-gray-500">
                                            <h5 class="text-sm font-semibold mb-1 dark:text-gray-200">تجهیزات مصرفی در این
                                                سرویس:</h5>
                                            <div id="log-{{ $logIndex }}-equipments-container">
                                                @if ($log->equipments && $log->equipments->count() > 0)
                                                    @foreach ($log->equipments as $equipIndex => $logEquipment)
                                                        <div class="p-2 mb-2 border dark:border-gray-500 rounded-md log-equipment-item bg-gray-100 dark:bg-gray-600/50"
                                                            data-log-index="{{ $logIndex }}"
                                                            data-equip-index="{{ $equipIndex }}">
                                                            <input type="hidden"
                                                                name="logs[{{ $logIndex }}][equipments][{{ $equipIndex }}][id]"
                                                                value="{{ $logEquipment->id }}">
                                                            <p class="text-xs font-semibold dark:text-indigo-200">
                                                                {{ $logEquipment->equipment->name ?? 'تجهیز نامشخص' }}
                                                                (موجودی:
                                                                {{ $logEquipment->equipment ? $logEquipment->equipment->stock_quantity + $logEquipment->quantity : 'N/A' }})
                                                            </p>
                                                            <div class="grid md:grid-cols-4 gap-2 mt-1">
                                                                <div>
                                                                    <label
                                                                        class="block text-xs dark:text-gray-400">تعداد</label>
                                                                    <input type="number"
                                                                        name="logs[{{ $logIndex }}][equipments][{{ $equipIndex }}][quantity]"
                                                                        value="{{ old("logs.{$logIndex}.equipments.{$equipIndex}.quantity", $logEquipment->quantity) }}"
                                                                        min="0"
                                                                        class="mt-1 block w-full text-xs rounded border-gray-300 dark:border-gray-500 dark:bg-gray-500 dark:text-white"
                                                                        {{ Auth::user()->isStaff() && $log->performed_by != Auth::id() ? 'readonly' : '' }}>
                                                                </div>
                                                                <div>
                                                                    <label class="block text-xs dark:text-gray-400">قیمت
                                                                        واحد</label>
                                                                    <input type="number"
                                                                        name="logs[{{ $logIndex }}][equipments][{{ $equipIndex }}][unit_price]"
                                                                        value="{{ old("logs.{$logIndex}.equipments.{$equipIndex}.unit_price", $logEquipment->unit_price) }}"
                                                                        min="0"
                                                                        class="mt-1 block w-full text-xs rounded border-gray-300 dark:border-gray-500 dark:bg-gray-500 dark:text-white"
                                                                        {{ Auth::user()->isStaff() && $log->performed_by != Auth::id() ? 'readonly' : '' }}>
                                                                </div>
                                                                <div class="md:col-span-2">
                                                                    <label class="block text-xs dark:text-gray-400">یادداشت
                                                                        تجهیز</label>
                                                                    <input type="text"
                                                                        name="logs[{{ $logIndex }}][equipments][{{ $equipIndex }}][notes]"
                                                                        value="{{ old("logs.{$logIndex}.equipments.{$equipIndex}.notes", $logEquipment->notes) }}"
                                                                        class="mt-1 block w-full text-xs rounded border-gray-300 dark:border-gray-500 dark:bg-gray-500 dark:text-white"
                                                                        {{ Auth::user()->isStaff() && $log->performed_by != Auth::id() ? 'readonly' : '' }}>
                                                                </div>
                                                            </div>
                                                            @if (Auth::user()->isAdmin() || (Auth::user()->isStaff() && $log->performed_by == Auth::id()))
                                                                <label class="flex items-center mt-1 text-xs">
                                                                    <input type="checkbox"
                                                                        name="logs[{{ $logIndex }}][equipments][{{ $equipIndex }}][_remove]"
                                                                        value="1" class="rounded text-red-500">
                                                                    <span
                                                                        class="ms-1 text-red-500 dark:text-red-400">حذف</span>
                                                                </label>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                            {{-- This placeholder is for adding NEW equipment to an EXISTING log --}}
                                            <div id="new-log-{{ $logIndex }}-equipments-placeholder"></div>
                                            @if (Auth::user()->isAdmin() || (Auth::user()->isStaff() && $log->performed_by == Auth::id()))
                                                <button type="button"
                                                    class="add-log-equipment-btn mt-1 text-xs px-2 py-1 bg-teal-500 hover:bg-teal-600 text-white rounded-md"
                                                    data-log-index="{{ $logIndex }}">+ افزودن تجهیز به این
                                                    گزارش</button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <div id="new-maintenance-logs-placeholder"></div>
                        {{-- Staff can add logs to their own assigned maintenance contracts --}}
                        @if (Auth::user()->isAdmin() ||
                                ($editMode && Auth::user()->isStaff() && $maintenance->assigned_to == Auth::id()) ||
                                (!$editMode && Auth::user()->isAdmin()))
                            <button type="button" id="add-maintenance-log-btn"
                                class="mt-2 text-sm px-3 py-1.5 bg-purple-500 hover:bg-purple-600 text-white rounded-md">+
                                افزودن گزارش سرویس جدید</button>
                        @endif
                    </div>

                    {{-- Maintenance Payments Section --}}
                    @if (Auth::user()->isAdmin())
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold mb-3 dark:text-white border-b pb-2 dark:border-gray-700">
                                پرداخت‌های سرویس دوره‌ای</h3>
                            <div id="maintenance-payments-container">
                                @if ($editMode && $maintenance->payments && $maintenance->payments->count() > 0)
                                    @foreach ($maintenance->payments as $index => $payment)
                                        <div class="p-3 mb-2 border dark:border-gray-700 rounded-md payment-item"
                                            data-index="{{ $index }}">
                                            <input type="hidden" name="payments[{{ $index }}][id]"
                                                value="{{ $payment->id }}">
                                            <div class="grid md:grid-cols-3 gap-3">
                                                <div class="md:col-span-1">
                                                    <label class="block text-xs dark:text-gray-300">مبلغ (تومان)</label>
                                                    <input type="number" name="payments[{{ $index }}][amount]"
                                                        value="{{ old("payments.{$index}.amount", $payment->amount) }}"
                                                        min="0"
                                                        class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                        {{ $editMode && Auth::user()->isStaff() ? 'readonly' : '' }}>
                                                </div>
                                                <div>
                                                    <label class="block text-xs dark:text-gray-300">تاریخ پرداخت</label>
                                                    <input type="text" name="payments[{{ $index }}][paid_at]"
                                                        value="{{ old("payments.{$index}.paid_at", $payment->formatted_paid_at) }}"
                                                        placeholder="مثلا: 1403/01/15 10:30"
                                                        class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white persian-date-time-picker"
                                                        {{ $editMode && Auth::user()->isStaff() ? 'readonly' : '' }}>
                                                </div>
                                                <div class="md:col-span-3">
                                                    <label class="block text-xs dark:text-gray-300">یادداشت</label>
                                                    <input type="text" name="payments[{{ $index }}][note]"
                                                        value="{{ old("payments.{$index}.note", $payment->note) }}"
                                                        class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                        {{ $editMode && Auth::user()->isStaff() ? 'readonly' : '' }}>
                                                </div>
                                            </div>
                                            @if (Auth::user()->isAdmin())
                                                <label class="flex items-center mt-2 text-sm">
                                                    <input type="checkbox" name="payments[{{ $index }}][_remove]"
                                                        value="1"
                                                        class="rounded border-gray-300 dark:border-gray-600 text-red-600 shadow-sm focus:ring-red-500">
                                                    <span class="ms-2 text-red-600 dark:text-red-400">حذف این پرداخت</span>
                                                </label>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div id="new-maintenance-payments-placeholder"></div>
                            <button type="button" id="add-maintenance-payment-btn"
                                class="mt-2 text-sm px-3 py-1.5 bg-sky-500 hover:bg-sky-600 text-white rounded-md">+ افزودن
                                پرداخت جدید</button>
                        </div>
                    @endif

                    <div class="mt-8 pt-6 border-t dark:border-gray-700">
                        <button type="submit"
                            class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-base"
                            id="submit-maintenance-btn">
                            {{ $editMode ? 'بروزرسانی سرویس' : 'ثبت سرویس دوره‌ای' }}
                        </button>
                        @if ($editMode)
                            <a href="{{ route('maintenances.index') }}"
                                class="px-6 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white rounded hover:bg-gray-400 dark:hover:bg-gray-700 ms-2 text-base">لغو</a>
                        @endif
                    </div>
                    <p id="general-maintenance-error" class="text-red-600 mt-3 hidden"></p>
                </form>
            </div>
        @endif

        {{-- Maintenances List --}}
        @if (isset($maintenancesList) && (!($editMode ?? false) || Route::currentRouteName() === 'maintenances.index'))
            <div class="mb-4">
                <div class="flex flex-col md:flex-row justify-between items-center gap-2 md:gap-4">
                    <form method="GET" action="{{ route('maintenances.index') }}"
                        class="w-full flex flex-col md:flex-row md:items-center gap-2">
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="جستجو در نام مشتری..."
                            class="w-full md:flex-grow rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white py-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                            <select name="sort_field"
                                class="w-full sm:w-auto rounded border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white py-2 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="start_date"
                                    {{ request('sort_field', 'start_date') == 'start_date' ? 'selected' : '' }}>تاریخ شروع
                                </option>
                                <option value="total_price"
                                    {{ request('sort_field') == 'total_price' ? 'selected' : '' }}>مبلغ کل</option>
                                <option value="is_active" {{ request('sort_field') == 'is_active' ? 'selected' : '' }}>
                                    وضعیت</option>
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
                            <a href="{{ route('maintenances.index') }}"
                                class="w-full md:w-auto text-center mt-2 md:mt-0 md:ms-2 text-red-600 hover:underline px-3 py-2 rounded-md border border-red-500 hover:bg-red-50 dark:hover:bg-red-900">پاک
                                کردن فیلتر</a>
                        @endif
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl shadow">
                <table class="w-full text-sm text-right text-gray-500 dark:text-gray-300">
                    <thead class="text-xs uppercase bg-gray-100 dark:bg-gray-700 dark:text-white">
                        <tr>
                            <th class="p-3">مشتری</th>
                            <th class="p-3">آدرس</th>
                            <th class="p-3">تاریخ شروع</th>
                            <th class="p-3">مدت (ماه)</th>
                            @if (Auth::user()->isAdmin())
                                <th class="p-3">هزینه ماهانه</th>
                                <th class="p-3">مبلغ کل</th>
                            @endif
                            <th class="p-3">تکمیل شده</th>
                            <th class="p-3">آخرین سرویس</th>
                            <th class="p-3">وضعیت</th>
                            <th class="p-3">تکنسین</th>
                            <th class="p-3">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($maintenancesList ?? [] as $item)
                            <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="p-3">{{ $item->customer->name ?? 'N/A' }}</td>
                                <td class="p-3">{{ $item->address->address ?? 'N/A' }}</td>
                                <td class="p-3">{{ $item->formatted_start_date ?: 'N/A' }}</td>
                                <td class="p-3">{{ $item->duration_in_months }}</td>
                                @if (Auth::user()->isAdmin())
                                    <td class="p-3">{{ number_format($item->monthly_price) }}</td>
                                    <td class="p-3">{{ number_format($item->total_price) }}</td>
                                @endif
                                <td class="p-3">{{ $item->completed_count }} / {{ $item->duration_in_months }}</td>
                                <td class="p-3">{{ $item->formatted_last_completed_at ?: '-' }}</td>
                                <td class="p-3">
                                    <span
                                        class="px-2 py-1 text-xs rounded-full {{ $item->is_active ? 'bg-green-200 text-green-800 dark:bg-green-700 dark:text-green-100' : 'bg-red-200 text-red-800 dark:bg-red-700 dark:text-red-100' }}">
                                        {{ $item->is_active ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                </td>
                                <td class="p-3">{{ $item->user->name ?? 'N/A' }}</td>
                                <td class="p-3 whitespace-nowrap">
                                    @if (Auth::user()->isAdmin() || (Auth::user()->isStaff() && $item->assigned_to == Auth::id()))
                                        <a href="{{ route('maintenances.edit', $item->id) }}"
                                            class="text-blue-600 hover:text-blue-800 dark:hover:text-blue-400 px-2 py-1">ویرایش</a>
                                    @endif
                                    @if (Auth::user()->isAdmin())
                                        {{-- Only Admins can delete --}}
                                        <form action="{{ route('maintenances.destroy', $item->id) }}" method="POST"
                                            class="inline-block"
                                            onsubmit="return confirm('آیا از حذف این سرویس دوره‌ای و تمام گزارشات و پرداخت‌های مرتبط مطمئن هستید؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-red-600 hover:text-red-800 dark:hover:text-red-400 px-2 py-1">حذف</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="p-4 text-center text-gray-500 dark:text-gray-400">
                                    سرویس دوره‌ای یافت نشد.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                @if (isset($maintenancesList) &&
                        $maintenancesList instanceof \Illuminate\Pagination\LengthAwarePaginator &&
                        $maintenancesList->count() > 0)
                    {{ $maintenancesList->withQueryString()->links() }}
                @endif
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const maintenanceCustomerSelect = document.getElementById('maintenance_customer_id');
            const maintenanceAddressSelect = document.getElementById('maintenance_customer_address_id');

            function populateMaintenanceAddresses(customerId, selectedAddressId = null) {
                if (!maintenanceAddressSelect) return;
                maintenanceAddressSelect.innerHTML = '<option value="">درحال بارگذاری آدرس‌ها...</option>';
                const selectedCustomer = customersWithAddressesData_Maintenances.find(c => String(c.id) === String(
                    customerId));

                if (selectedCustomer && selectedCustomer.addresses && selectedCustomer.addresses.length > 0) {
                    maintenanceAddressSelect.innerHTML = '<option value="">انتخاب کنید</option>';
                    selectedCustomer.addresses.forEach(address => {
                        const option = document.createElement('option');
                        option.value = address.id;
                        option.textContent = (address.label ? address.label + ': ' : '') + address.address;
                        if (selectedAddressId && String(address.id) === String(selectedAddressId)) {
                            option.selected = true;
                        }
                        maintenanceAddressSelect.appendChild(option);
                    });
                } else {
                    maintenanceAddressSelect.innerHTML = '<option value="">آدرسی برای این مشتری یافت نشد</option>';
                }
            }

            if (maintenanceCustomerSelect) {
                // If a customer is already selected on page load (e.g., validation error), populate its addresses.
                if (maintenanceCustomerSelect.value) {
                    const preselectedAddressId =
                        "{{ old('customer_address_id', $editMode && isset($maintenance) ? $maintenance->customer_address_id : '') }}";
                    populateMaintenanceAddresses(maintenanceCustomerSelect.value, preselectedAddressId);
                }
                maintenanceCustomerSelect.addEventListener('change', (e) => {
                    populateMaintenanceAddresses(e.target.value);
                });
            }

            // Auto-calculate total price
            const durationInput = document.getElementById('maintenance_duration_in_months');
            const monthlyPriceInput = document.getElementById('maintenance_monthly_price');
            const totalPriceInput = document.getElementById('maintenance_total_price');
            const calculateBtn = document.getElementById('calculate_total_price_btn');

            function calculateMaintenanceTotalPrice() {
                if (durationInput && monthlyPriceInput && totalPriceInput) {
                    const duration = parseInt(durationInput.value) || 0;
                    const monthlyPrice = parseInt(monthlyPriceInput.value) || 0;
                    totalPriceInput.value = duration * monthlyPrice;
                }
            }
            if (calculateBtn) calculateBtn.addEventListener('click', calculateMaintenanceTotalPrice);
            if (durationInput) durationInput.addEventListener('input', calculateMaintenanceTotalPrice);
            if (monthlyPriceInput) monthlyPriceInput.addEventListener('input', calculateMaintenanceTotalPrice);

            // --- Dynamic Maintenance Logs ---
            const addMaintenanceLogBtn = document.getElementById('add-maintenance-log-btn');
            const newMaintenanceLogsPlaceholder = document.getElementById('new-maintenance-logs-placeholder');
            let newMaintenanceLogDynamicIndex =
                {{ $editMode && isset($maintenance) && $maintenance->logs ? $maintenance->logs->count() : 0 }};

            if (addMaintenanceLogBtn && newMaintenanceLogsPlaceholder) {
                addMaintenanceLogBtn.addEventListener('click', () => {
                    const logIndex = newMaintenanceLogDynamicIndex;
                    const logDiv = document.createElement('div');
                    logDiv.classList.add('p-4', 'mb-3', 'border', 'dark:border-gray-600', 'rounded-lg',
                        'maintenance-log-item', 'bg-gray-50', 'dark:bg-gray-700/50');
                    logDiv.dataset.logIndex = logIndex;
                    let userOptions = '<option value="">انتخاب تکنسین</option>';
                    usersData_Maintenances.forEach(user => {
                        userOptions +=
                            `<option value="${user.id}" ${user.id == {{ Auth::id() }} ? 'selected' : ''}>${user.name}</option>`;
                    });
                    const defaultPerformedAt = currentJalaliDateTimeForJS_Maintenances || '';
                    logDiv.innerHTML = `
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="text-md font-semibold dark:text-indigo-300">گزارش سرویس جدید #${logIndex + 1}</h4>
                            <button type="button" class="text-red-500 hover:text-red-700 remove-new-item-btn" data-type="log">&times; حذف گزارش</button>
                        </div>
                        <div class="grid md:grid-cols-3 gap-4">
                            <div><label class="block text-xs dark:text-gray-300">انجام شده توسط</label><select name="new_logs[${logIndex}][performed_by]" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white">${userOptions}</select></div>
                            <div><label class="block text-xs dark:text-gray-300">تاریخ انجام</label><input type="text" name="new_logs[${logIndex}][performed_at]" value="${defaultPerformedAt}" placeholder="مثلا: 1403/01/15 10:30" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white persian-date-time-picker"></div>
                            <div class="flex items-end"><label class="flex items-center text-sm dark:text-white"><input type="checkbox" name="new_logs[${logIndex}][sms_sent]" value="1" class="rounded"><span class="ms-2">پیامک ارسال شد</span></label></div>
                            <div class="md:col-span-3"><label class="block text-xs dark:text-gray-300">یادداشت گزارش</label><textarea name="new_logs[${logIndex}][note]" rows="2" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white"></textarea></div>
                        </div>
                        <div class="mt-3 pt-3 border-t dark:border-gray-500">
                            <h5 class="text-sm font-semibold mb-1 dark:text-gray-200">تجهیزات مصرفی در این سرویس:</h5>
                            <div id="new-log-${logIndex}-equipments-placeholder"></div>
                            <button type="button" class="add-log-equipment-btn mt-1 text-xs px-2 py-1 bg-teal-500 hover:bg-teal-600 text-white rounded-md" data-log-index="${logIndex}">+ افزودن تجهیز به این گزارش</button>
                        </div>`;
                    newMaintenanceLogsPlaceholder.appendChild(logDiv);
                    initializeNewPersianDatePickers(logDiv);
                    newMaintenanceLogDynamicIndex++;
                });
            }

            // --- Dynamic Maintenance Payments ---
            const addMaintenancePaymentBtn = document.getElementById('add-maintenance-payment-btn');
            const newMaintenancePaymentsPlaceholder = document.getElementById(
                'new-maintenance-payments-placeholder');
            if (addMaintenancePaymentBtn && newMaintenancePaymentsPlaceholder) {
                let newMaintenancePaymentDynamicIndex =
                    {{ $editMode && isset($maintenance) && $maintenance->payments ? $maintenance->payments->count() : 0 }};
                addMaintenancePaymentBtn.addEventListener('click', () => {
                    const div = document.createElement('div');
                    div.classList.add('p-3', 'mb-2', 'border', 'dark:border-gray-600', 'rounded-md',
                        'bg-gray-50', 'dark:bg-gray-700', 'new-payment-item');
                    div.dataset.index = newMaintenancePaymentDynamicIndex;
                    const defaultPaidAtValue = currentJalaliDateTimeForJS_Maintenances || '';
                    div.innerHTML = `
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="text-md font-semibold dark:text-indigo-300">پرداخت جدید #${newMaintenancePaymentDynamicIndex + 1}</h4>
                            <button type="button" class="text-red-500 hover:text-red-700 remove-new-item-btn" data-type="payment">&times; حذف</button>
                        </div>
                        <div class="grid md:grid-cols-3 gap-3">
                            <div class="md:col-span-1"><label class="block text-xs dark:text-gray-300">مبلغ (تومان)</label><input type="number" name="new_payments[${newMaintenancePaymentDynamicIndex}][amount]" value="0" min="0" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white"></div>
                            <div><label class="block text-xs dark:text-gray-300">تاریخ پرداخت</label><input type="text" name="new_payments[${newMaintenancePaymentDynamicIndex}][paid_at]" value="${defaultPaidAtValue}" placeholder="مثلا: 1403/01/15 10:30" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white persian-date-time-picker"></div>
                            <div class="md:col-span-3"><label class="block text-xs dark:text-gray-300">یادداشت</label><input type="text" name="new_payments[${newMaintenancePaymentDynamicIndex}][note]" class="mt-1 block w-full text-sm rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white"></div>
                        </div>`;
                    newMaintenancePaymentsPlaceholder.appendChild(div);
                    initializeNewPersianDatePickers(div);
                    newMaintenancePaymentDynamicIndex++;
                });
            }

            // --- FIXED: Delegated Event Listeners for Dynamic Content ---
            document.addEventListener('click', function(event) {
                // Handle adding equipment to a log (both existing and new logs)
                if (event.target.classList.contains('add-log-equipment-btn')) {
                    const logIndex = event.target.dataset.logIndex;
                    const parentLogItem = event.target.closest('.maintenance-log-item');
                    if (!parentLogItem) return;

                    const logEquipmentsPlaceholder = parentLogItem.querySelector(
                        `#new-log-${logIndex}-equipments-placeholder`) || parentLogItem.querySelector(
                        `#log-${logIndex}-equipments-container`);
                    if (!logEquipmentsPlaceholder) return;

                    const isNewLog = parentLogItem.parentElement.id === 'new-maintenance-logs-placeholder';
                    const namePrefix = isNewLog ? `new_logs[${logIndex}]` : `logs[${logIndex}]`;

                    const equipCount = logEquipmentsPlaceholder.querySelectorAll(
                        '.log-equipment-item, .new-log-equipment-item').length;
                    const equipDiv = document.createElement('div');
                    equipDiv.classList.add('p-2', 'mb-2', 'border', 'dark:border-gray-500', 'rounded-md',
                        'bg-gray-100', 'dark:bg-gray-600/50', 'new-log-equipment-item');

                    let equipmentOptions = '<option value="">انتخاب تجهیز</option>';
                    availableEquipmentsData_Maintenances.forEach(eq => {
                        equipmentOptions +=
                            `<option value="${eq.id}" data-price="${eq.price}" data-stock="${eq.stock_quantity}">${eq.name} (موجودی: ${eq.stock_quantity})</option>`;
                    });

                    equipDiv.innerHTML = `
                        <div class="flex justify-between items-center mb-1">
                            <h6 class="text-xs font-semibold dark:text-indigo-200">تجهیز جدید</h6>
                            <button type="button" class="text-red-400 hover:text-red-600 remove-new-item-btn" data-type="equipment">&times; حذف</button>
                        </div>
                        <div class="grid md:grid-cols-4 gap-2 mt-1">
                            <div class="md:col-span-2"><label class="block text-xs dark:text-gray-400">انتخاب تجهیز</label><select name="${namePrefix}[new_equipments][${equipCount}][equipment_id]" class="mt-1 block w-full text-xs rounded border-gray-300 dark:border-gray-500 dark:bg-gray-500 dark:text-white new-equipment-select">${equipmentOptions}</select></div>
                            <div><label class="block text-xs dark:text-gray-400">تعداد</label><input type="number" name="${namePrefix}[new_equipments][${equipCount}][quantity]" value="1" min="1" class="mt-1 block w-full text-xs rounded border-gray-300 dark:border-gray-500 dark:bg-gray-500 dark:text-white"></div>
                            <div><label class="block text-xs dark:text-gray-400">قیمت واحد</label><input type="number" name="${namePrefix}[new_equipments][${equipCount}][unit_price]" value="0" min="0" class="mt-1 block w-full text-xs rounded border-gray-300 dark:border-gray-500 dark:bg-gray-500 dark:text-white new-equipment-price"></div>
                            <div class="md:col-span-4"><label class="block text-xs dark:text-gray-400">یادداشت تجهیز</label><input type="text" name="${namePrefix}[new_equipments][${equipCount}][notes]" class="mt-1 block w-full text-xs rounded border-gray-300 dark:border-gray-500 dark:bg-gray-500 dark:text-white"></div>
                        </div>`;
                    logEquipmentsPlaceholder.appendChild(equipDiv);
                }

                // Handle removing any new item (log, equipment, payment)
                if (event.target.classList.contains('remove-new-item-btn')) {
                    const itemType = event.target.dataset.type;
                    if (itemType === 'log') {
                        event.target.closest('.maintenance-log-item').remove();
                    } else if (itemType === 'equipment') {
                        event.target.closest('.new-log-equipment-item').remove();
                    } else if (itemType === 'payment') {
                        event.target.closest('.new-payment-item').remove();
                    }
                }
            });

            document.addEventListener('change', function(event) {
                // Handle auto-filling price when a new equipment is selected
                if (event.target.classList.contains('new-equipment-select')) {
                    const selectedOption = event.target.options[event.target.selectedIndex];
                    const priceInput = event.target.closest('.new-log-equipment-item').querySelector(
                        '.new-equipment-price');
                    if (selectedOption && priceInput) {
                        priceInput.value = selectedOption.dataset.price || 0;
                    }
                }
            });

            // Function to initialize Persian Date Pickers on new elements
            function initializeNewPersianDatePickers(parentElement) {
                parentElement.querySelectorAll('.persian-date-picker, .persian-date-time-picker').forEach(el => {
                    if (typeof $ !== 'undefined' && $.fn.persianDatepicker) {
                        $(el).persianDatepicker({
                            format: el.classList.contains('persian-date-time-picker') ?
                                'YYYY/MM/DD HH:mm' : 'YYYY/MM/DD',
                            initialValue: false, // Don't set initial value for dynamically added pickers unless they have a value
                            timePicker: {
                                enabled: el.classList.contains('persian-date-time-picker'),
                                meridiem: {
                                    enabled: false
                                }
                            },
                            toolbox: {
                                calendarSwitch: {
                                    enabled: true
                                }
                            },
                            observer: true,
                            altField: el,
                            altFormat: el.classList.contains('persian-date-time-picker') ?
                                'YYYY/MM/DD HH:mm' : 'YYYY/MM/DD',
                        });
                    }
                });
            }
            // Initial call for existing elements on page load
            initializeNewPersianDatePickers(document);
        });
    </script>
@endsection
