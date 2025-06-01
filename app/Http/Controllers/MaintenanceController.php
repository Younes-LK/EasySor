<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceEquipment;
use App\Models\MaintenancePayment;
use App\Models\Customer;
use App\Models\User;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\Route;

class MaintenanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Maintenance::with(['customer', 'address', 'user', 'logs', 'payments']);

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('customer', function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%');
            });
            // Add more search criteria if needed (e.g., on maintenance title if you add one)
        }

        $sortField = $request->input('sort_field', 'start_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $validSortFields = ['start_date', 'total_price', 'is_active', 'created_at'];
        if (!in_array($sortField, $validSortFields)) {
            $sortField = 'start_date';
        }
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }
        $query->orderBy($sortField, $sortDirection);

        $maintenancesList = $query->paginate(10);

        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $users = User::whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
        $customersWithAddresses = Customer::with('addresses:id,customer_id,label,address,is_default')->get(['id', 'name']);
        $availableEquipments = Equipment::orderBy('name')->get();
        $currentJalaliDateTime = Jalalian::now()->format('Y/m/d H:i');
        $currentJalaliDate = Jalalian::now()->format('Y/m/d');


        return view('maintenances', [
            'maintenancesList' => $maintenancesList,
            'customers' => $customers,
            'users' => $users,
            'editMode' => false,
            'maintenance' => new Maintenance(),
            'customersWithAddresses' => $customersWithAddresses,
            'selectedCustomerAddresses' => collect(),
            'availableEquipments' => $availableEquipments,
            'currentJalaliDateTime' => $currentJalaliDateTime,
            'currentJalaliDate' => $currentJalaliDate,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $users = User::whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
        $customersWithAddresses = Customer::with('addresses:id,customer_id,label,address,is_default')->get(['id', 'name']);
        $availableEquipments = Equipment::orderBy('name')->get();
        $currentJalaliDateTime = Jalalian::now()->format('Y/m/d H:i');
        $currentJalaliDate = Jalalian::now()->format('Y/m/d');

        return view('maintenances', [
            'editMode' => false,
            'maintenance' => new Maintenance(),
            'customers' => $customers,
            'users' => $users,
            'customersWithAddresses' => $customersWithAddresses,
            'selectedCustomerAddresses' => collect(),
            'availableEquipments' => $availableEquipments,
            'currentJalaliDateTime' => $currentJalaliDateTime,
            'currentJalaliDate' => $currentJalaliDate,
            'maintenancesList' => collect(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'customer_address_id' => 'required|exists:customer_addresses,id,customer_id,' . $request->customer_id,
            'assigned_to' => 'nullable|exists:users,id',
            'start_date' => 'required|string', // Parsed from Jalali
            'duration_in_months' => 'required|integer|min:1',
            'monthly_price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',

            // New Logs
            'new_logs.*.performed_by' => 'nullable|required_with:new_logs.*.performed_at|exists:users,id',
            'new_logs.*.performed_at' => 'nullable|required_with:new_logs.*.performed_by|string', // Parsed
            'new_logs.*.sms_sent' => 'nullable|boolean',
            'new_logs.*.note' => 'nullable|string',
            // New Equipment within New Logs
            'new_logs.*.new_equipments.*.equipment_id' => 'nullable|required_with:new_logs.*.new_equipments.*.quantity|exists:equipments,id',
            'new_logs.*.new_equipments.*.quantity' => 'nullable|required_with:new_logs.*.new_equipments.*.equipment_id|integer|min:1',
            'new_logs.*.new_equipments.*.unit_price' => 'nullable|numeric|min:0',
            'new_logs.*.new_equipments.*.notes' => 'nullable|string',

            // New Payments
            'new_payments.*.amount' => 'nullable|numeric|min:0',
            'new_payments.*.paid_at' => 'nullable|string', // Parsed
            'new_payments.*.note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('maintenances.create')
                        ->withErrors($validator)
                        ->withInput();
        }

        DB::beginTransaction();
        try {
            $maintenanceData = $validator->safe()->only([
                'customer_id', 'customer_address_id', 'assigned_to',
                'duration_in_months', 'monthly_price', 'total_price'
            ]);
            $maintenanceData['start_date'] = Jalalian::fromFormat('Y/m/d', $request->start_date)->toCarbon();
            $maintenanceData['is_active'] = $request->has('is_active');
            $maintenanceData['paid_amount'] = 0; // Initial
            $maintenanceData['completed_count'] = 0; // Initial

            $maintenance = Maintenance::create($maintenanceData);

            // Handle New Logs and their Equipment
            if ($request->has('new_logs')) {
                foreach ($request->input('new_logs', []) as $logData) {
                    if (empty($logData['performed_by']) || empty($logData['performed_at'])) {
                        continue;
                    }

                    $maintenanceLog = $maintenance->logs()->create([
                        'performed_by' => $logData['performed_by'],
                        'performed_at' => Jalalian::fromFormat('Y/m/d H:i', $logData['performed_at'])->toCarbon(),
                        'sms_sent' => isset($logData['sms_sent']),
                        'note' => $logData['note'] ?? null,
                    ]);
                    $maintenance->increment('completed_count');
                    $maintenance->last_completed_at = $maintenanceLog->performed_at;

                    if (isset($logData['new_equipments'])) {
                        foreach ($logData['new_equipments'] as $equipmentData) {
                            if (!empty($equipmentData['equipment_id']) && !empty($equipmentData['quantity'])) {
                                $equipment = Equipment::find($equipmentData['equipment_id']);
                                if ($equipment && $equipment->stock_quantity >= $equipmentData['quantity']) {
                                    $maintenanceLog->equipments()->create([
                                        'equipment_id' => $equipmentData['equipment_id'],
                                        'quantity' => $equipmentData['quantity'],
                                        'unit_price' => $equipmentData['unit_price'] ?? $equipment->price,
                                        'notes' => $equipmentData['notes'] ?? null,
                                    ]);
                                    $equipment->decrement('stock_quantity', $equipmentData['quantity']);
                                } else {
                                    throw new \Exception("موجودی انبار برای تجهیز " . ($equipment->name ?? 'انتخابی') . " در گزارش کافی نیست.");
                                }
                            }
                        }
                    }
                }
                $maintenance->save(); // Save updates to completed_count and last_completed_at
            }

            // Handle New Payments
            $totalPaid = 0;
            if ($request->has('new_payments')) {
                foreach ($request->input('new_payments', []) as $paymentData) {
                    if (isset($paymentData['amount']) && $paymentData['amount'] > 0) {
                        $payment = $maintenance->payments()->create([
                             'amount' => $paymentData['amount'],
                             'paid_at' => $paymentData['paid_at'] ? Jalalian::fromFormat('Y/m/d H:i', $paymentData['paid_at'])->toCarbon() : now(),
                             'note' => $paymentData['note'] ?? null,
                         ]);
                        $totalPaid += $payment->amount;
                    }
                }
            }
            if ($totalPaid > 0) {
                $maintenance->increment('paid_amount', $totalPaid);
            }


            DB::commit();
            return redirect()->route('maintenances.index')->with('success', 'سرویس دوره‌ای با موفقیت ایجاد شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error storing maintenance: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->route('maintenances.create')->withErrors(['general' => 'خطا در ایجاد سرویس دوره‌ای: ' . $e->getMessage()])->withInput();
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $maintenance = Maintenance::with([
            'customer.addresses',
            'user', // assigned_to
            'logs.user', // performed_by for each log
            'logs.equipments.equipment', // equipment details for each log's equipment
            'payments'
        ])->findOrFail($id);

        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $users = User::whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
        $customersWithAddresses = Customer::with('addresses:id,customer_id,label,address,is_default')->get(['id', 'name']);
        $availableEquipments = Equipment::orderBy('name')->get();
        $currentJalaliDateTime = Jalalian::now()->format('Y/m/d H:i');
        $currentJalaliDate = Jalalian::now()->format('Y/m/d');

        $selectedCustomerAddresses = $maintenance->customer ? $maintenance->customer->addresses : collect();

        $maintenancesList = collect();
        if (Route::currentRouteName() == 'maintenances.index' || !view()->exists('maintenances.edit')) {
            $query = Maintenance::with(['customer', 'address', 'user']);
            $maintenancesList = $query->orderBy('start_date', 'desc')->paginate(10);
        }

        return view('maintenances', [
            'editMode' => true,
            'maintenance' => $maintenance,
            'customers' => $customers,
            'users' => $users,
            'customersWithAddresses' => $customersWithAddresses,
            'selectedCustomerAddresses' => $selectedCustomerAddresses,
            'availableEquipments' => $availableEquipments,
            'currentJalaliDateTime' => $currentJalaliDateTime,
            'currentJalaliDate' => $currentJalaliDate,
            'maintenancesList' => $maintenancesList,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $maintenance = Maintenance::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'customer_address_id' => 'required|exists:customer_addresses,id,customer_id,' . $maintenance->customer_id,
            'assigned_to' => 'nullable|exists:users,id',
            'start_date' => 'required|string',
            'duration_in_months' => 'required|integer|min:1',
            'monthly_price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',

            // Existing Logs
            'logs.*.id' => 'required_with:logs.*.performed_by|exists:maintenances_logs,id,maintenance_service_id,'.$id,
            'logs.*.performed_by' => 'nullable|exists:users,id',
            'logs.*.performed_at' => 'nullable|string',
            'logs.*.sms_sent' => 'nullable|boolean',
            'logs.*.note' => 'nullable|string',
            'logs.*._remove' => 'nullable|boolean',
            // Existing Equipment within Existing Logs
            'logs.*.equipments.*.id' => 'required_with:logs.*.equipments.*.quantity|exists:maintenances_equipments,id', // Further validation for log_id needed if complex
            'logs.*.equipments.*.quantity' => 'required_with:logs.*.equipments.*.id|integer|min:0',
            'logs.*.equipments.*.unit_price' => 'nullable|numeric|min:0',
            'logs.*.equipments.*.notes' => 'nullable|string',
            'logs.*.equipments.*._remove' => 'nullable|boolean',
            // New Equipment within Existing Logs
            'logs.*.new_equipments.*.equipment_id' => 'nullable|required_with:logs.*.new_equipments.*.quantity|exists:equipments,id',
            'logs.*.new_equipments.*.quantity' => 'nullable|required_with:logs.*.new_equipments.*.equipment_id|integer|min:1',
            'logs.*.new_equipments.*.unit_price' => 'nullable|numeric|min:0',
            'logs.*.new_equipments.*.notes' => 'nullable|string',

            // New Logs
            'new_logs.*.performed_by' => 'nullable|required_with:new_logs.*.performed_at|exists:users,id',
            'new_logs.*.performed_at' => 'nullable|required_with:new_logs.*.performed_by|string',
            'new_logs.*.sms_sent' => 'nullable|boolean',
            'new_logs.*.note' => 'nullable|string',
            // New Equipment within New Logs
            'new_logs.*.new_equipments.*.equipment_id' => 'nullable|required_with:new_logs.*.new_equipments.*.quantity|exists:equipments,id',
            'new_logs.*.new_equipments.*.quantity' => 'nullable|required_with:new_logs.*.new_equipments.*.equipment_id|integer|min:1',
            'new_logs.*.new_equipments.*.unit_price' => 'nullable|numeric|min:0',
            'new_logs.*.new_equipments.*.notes' => 'nullable|string',

            // Existing Payments
            'payments.*.id' => 'required_with:payments.*.amount|exists:maintenances_payments,id,maintenance_service_id,'.$id,
            'payments.*.amount' => 'required_with:payments.*.id|numeric|min:0',
            'payments.*.paid_at' => 'required_with:payments.*.id|string',
            'payments.*.note' => 'nullable|string',
            'payments.*._remove' => 'nullable|boolean',
            // New Payments
            'new_payments.*.amount' => 'nullable|numeric|min:0',
            'new_payments.*.paid_at' => 'nullable|string',
            'new_payments.*.note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('maintenances.edit', $maintenance->id)
                        ->withErrors($validator)
                        ->withInput();
        }

        DB::beginTransaction();
        try {
            $maintenanceData = $validator->safe()->only([
                'customer_address_id', 'assigned_to',
                'duration_in_months', 'monthly_price', 'total_price'
            ]);
            $maintenanceData['start_date'] = Jalalian::fromFormat('Y/m/d', $request->start_date)->toCarbon();
            $maintenanceData['is_active'] = $request->has('is_active');

            $maintenance->update($maintenanceData);

            $completedCount = 0;
            $latestCompletionDate = null;

            // Handle Existing Logs
            if ($request->has('logs')) {
                foreach ($request->input('logs') as $logId => $logData) {
                    // The key $logId here is the index from the form, not necessarily the DB ID.
                    // The actual DB ID is $logData['id']
                    if (empty($logData['id'])) {
                        continue;
                    }
                    $maintenanceLog = MaintenanceLog::where('id', $logData['id'])
                                                 ->where('maintenance_service_id', $maintenance->id)
                                                 ->first();
                    if (!$maintenanceLog) {
                        continue;
                    }

                    if (isset($logData['_remove'])) {
                        // Restore stock for equipment in this log
                        foreach ($maintenanceLog->equipments as $logEquipment) {
                            $equipment = Equipment::find($logEquipment->equipment_id);
                            if ($equipment) {
                                $equipment->increment('stock_quantity', $logEquipment->quantity);
                            }
                        }
                        $maintenanceLog->equipments()->delete();
                        $maintenanceLog->delete();
                        continue; // Skip to next log
                    }

                    $maintenanceLog->update([
                        'performed_by' => $logData['performed_by'] ?? $maintenanceLog->performed_by,
                        'performed_at' => isset($logData['performed_at']) ? Jalalian::fromFormat('Y/m/d H:i', $logData['performed_at'])->toCarbon() : $maintenanceLog->performed_at,
                        'sms_sent' => isset($logData['sms_sent']),
                        'note' => $logData['note'] ?? $maintenanceLog->note,
                    ]);
                    $completedCount++;
                    if (!$latestCompletionDate || $maintenanceLog->performed_at->gt($latestCompletionDate)) {
                        $latestCompletionDate = $maintenanceLog->performed_at;
                    }

                    // Handle Equipment within this Existing Log
                    if (isset($logData['equipments'])) {
                        foreach ($logData['equipments'] as $logEqId => $logEqData) {
                            if (empty($logEqData['id'])) {
                                continue;
                            }
                            $logEquipmentItem = MaintenanceEquipment::find($logEqData['id']);
                            if (!$logEquipmentItem || $logEquipmentItem->maintenance_log_id != $maintenanceLog->id) {
                                continue;
                            }

                            $originalQuantity = $logEquipmentItem->quantity;
                            $mainEquipment = Equipment::find($logEquipmentItem->equipment_id);

                            if (isset($logEqData['_remove']) || (isset($logEqData['quantity']) && $logEqData['quantity'] == 0)) {
                                if ($mainEquipment) {
                                    $mainEquipment->increment('stock_quantity', $originalQuantity);
                                }
                                $logEquipmentItem->delete();
                            } else {
                                $newQuantity = (int)$logEqData['quantity'];
                                $quantityChange = $newQuantity - $originalQuantity;
                                if ($mainEquipment) {
                                    if ($quantityChange > 0 && $mainEquipment->stock_quantity < $quantityChange) {
                                        throw new \Exception("موجودی برای {$mainEquipment->name} در گزارش کافی نیست.");
                                    }
                                    $mainEquipment->decrement('stock_quantity', $quantityChange);
                                }
                                $logEquipmentItem->update([
                                    'quantity' => $newQuantity,
                                    'unit_price' => $logEqData['unit_price'] ?? $logEquipmentItem->unit_price,
                                    'notes' => $logEqData['notes'] ?? $logEquipmentItem->notes,
                                ]);
                            }
                        }
                    }
                    // Handle New Equipment within this Existing Log
                    if (isset($logData['new_equipments'])) {
                        foreach ($logData['new_equipments'] as $newEqData) {
                            if (!empty($newEqData['equipment_id']) && !empty($newEqData['quantity'])) {
                                $equipment = Equipment::find($newEqData['equipment_id']);
                                if ($equipment && $equipment->stock_quantity >= $newEqData['quantity']) {
                                    $maintenanceLog->equipments()->create([
                                        'equipment_id' => $newEqData['equipment_id'],
                                        'quantity' => $newEqData['quantity'],
                                        'unit_price' => $newEqData['unit_price'] ?? $equipment->price,
                                        'notes' => $newEqData['notes'] ?? null,
                                    ]);
                                    $equipment->decrement('stock_quantity', $newEqData['quantity']);
                                } else {
                                    throw new \Exception("موجودی برای تجهیز جدید " . ($equipment->name ?? '') . " در گزارش کافی نیست.");
                                }
                            }
                        }
                    }
                }
            }

            // Handle New Logs and their Equipment
            if ($request->has('new_logs')) {
                foreach ($request->input('new_logs', []) as $logData) {
                    if (empty($logData['performed_by']) || empty($logData['performed_at'])) {
                        continue;
                    }

                    $maintenanceLog = $maintenance->logs()->create([
                        'performed_by' => $logData['performed_by'],
                        'performed_at' => Jalalian::fromFormat('Y/m/d H:i', $logData['performed_at'])->toCarbon(),
                        'sms_sent' => isset($logData['sms_sent']),
                        'note' => $logData['note'] ?? null,
                    ]);
                    $completedCount++;
                    if (!$latestCompletionDate || $maintenanceLog->performed_at->gt($latestCompletionDate)) {
                        $latestCompletionDate = $maintenanceLog->performed_at;
                    }

                    if (isset($logData['new_equipments'])) {
                        foreach ($logData['new_equipments'] as $equipmentData) {
                            if (!empty($equipmentData['equipment_id']) && !empty($equipmentData['quantity'])) {
                                $equipment = Equipment::find($equipmentData['equipment_id']);
                                if ($equipment && $equipment->stock_quantity >= $equipmentData['quantity']) {
                                    $maintenanceLog->equipments()->create([
                                        'equipment_id' => $equipmentData['equipment_id'],
                                        'quantity' => $equipmentData['quantity'],
                                        'unit_price' => $equipmentData['unit_price'] ?? $equipment->price,
                                        'notes' => $equipmentData['notes'] ?? null,
                                    ]);
                                    $equipment->decrement('stock_quantity', $equipmentData['quantity']);
                                } else {
                                    throw new \Exception("موجودی انبار برای تجهیز " . ($equipment->name ?? 'انتخابی') . " در گزارش جدید کافی نیست.");
                                }
                            }
                        }
                    }
                }
            }
            // Update maintenance summary fields
            $maintenance->completed_count = $completedCount;
            $maintenance->last_completed_at = $latestCompletionDate;
            $maintenance->save();


            // Handle Payments
            $totalPaid = $maintenance->payments()->sum('amount'); // Start with existing sum before modifications
            if ($request->has('payments')) {
                foreach ($request->input('payments') as $paymentData) {
                    if (empty($paymentData['id'])) {
                        continue;
                    }
                    $maintenancePayment = MaintenancePayment::find($paymentData['id']);
                    if (!$maintenancePayment || $maintenancePayment->maintenance_service_id != $maintenance->id) {
                        continue;
                    }

                    if (isset($paymentData['_remove'])) {
                        $totalPaid -= $maintenancePayment->amount; // Subtract removed amount
                        $maintenancePayment->delete();
                    } else {
                        $totalPaid -= $maintenancePayment->amount; // Subtract old amount
                        $maintenancePayment->update([
                            // 'title' => $paymentData['title'], // No title in migration for maintenance_payments
                            'amount' => $paymentData['amount'],
                            'paid_at' => $paymentData['paid_at'] ? Jalalian::fromFormat('Y/m/d H:i', $paymentData['paid_at'])->toCarbon() : null,
                            'note' => $paymentData['note'] ?? $maintenancePayment->note,
                        ]);
                        $totalPaid += $maintenancePayment->amount; // Add new amount
                    }
                }
            }
            if ($request->has('new_payments')) {
                foreach ($request->input('new_payments', []) as $paymentData) {
                    if (isset($paymentData['amount']) && $paymentData['amount'] > 0) { // Ensure amount is set and positive
                        $payment = $maintenance->payments()->create([
                            // 'title' => $paymentData['title'], // No title
                            'amount' => $paymentData['amount'],
                            'paid_at' => $paymentData['paid_at'] ? Jalalian::fromFormat('Y/m/d H:i', $paymentData['paid_at'])->toCarbon() : now(),
                            'note' => $paymentData['note'] ?? null,
                        ]);
                        $totalPaid += $payment->amount;
                    }
                }
            }
            $maintenance->update(['paid_amount' => $totalPaid]);


            DB::commit();
            return redirect()->route('maintenances.index')->with('success', 'سرویس دوره‌ای با موفقیت بروزرسانی شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating maintenance: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->route('maintenances.edit', $maintenance->id)->withErrors(['general' => 'خطا در بروزرسانی سرویس دوره‌ای: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $maintenance = Maintenance::with('logs.equipments', 'payments')->findOrFail($id);

            foreach ($maintenance->logs as $log) {
                foreach ($log->equipments as $logEquipment) {
                    $equipment = Equipment::find($logEquipment->equipment_id);
                    if ($equipment) {
                        $equipment->increment('stock_quantity', $logEquipment->quantity);
                    }
                }
                $log->equipments()->delete(); // Delete equipment entries for the log
            }
            $maintenance->logs()->delete(); // Delete all logs for the maintenance
            $maintenance->payments()->delete(); // Delete all payments for the maintenance
            $maintenance->delete(); // Delete the maintenance itself

            DB::commit();
            return redirect()->route('maintenances.index')->with('success', 'سرویس دوره‌ای و اطلاعات مرتبط با موفقیت حذف شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting maintenance: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->route('maintenances.index')->withErrors(['general' => 'خطا در حذف سرویس دوره‌ای: ' . $e->getMessage()]);
        }
    }
}
