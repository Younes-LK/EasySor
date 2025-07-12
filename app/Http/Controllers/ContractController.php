<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractLog;
use App\Models\Customer;
use App\Models\User;
use App\Models\Equipment;
use App\Models\ContractEquipment;
use App\Models\ContractPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\TemplateProcessor;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $query = Contract::with(['customer', 'address', 'assignedUser']);

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('description', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('customer', function ($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', '%' . $searchTerm . '%');
                  });
            });
        }

        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        if (!in_array($sortField, ['created_at', 'total_price', 'status'])) {
            $sortField = 'created_at';
        }
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }
        $query->orderBy($sortField, $sortDirection);

        $contractsList = $query->paginate(10);

        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $users = User::whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
        $customersWithAddresses = Customer::with('addresses:id,customer_id,label,address,is_default')->get(['id', 'name']);
        $availableEquipments = Equipment::orderBy('name')->get();
        $currentJalaliDateTime = Jalalian::now()->format('Y/m/d H:i');


        return view('contracts', [
            'contractsList' => $contractsList,
            'customers' => $customers,
            'users' => $users,
            'editMode' => false,
            'contract' => new Contract(),
            'customersWithAddresses' => $customersWithAddresses,
            'selectedCustomerAddresses' => collect(),
            'availableEquipments' => $availableEquipments,
            'currentJalaliDateTime' => $currentJalaliDateTime,
        ]);
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $users = User::whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
        $customersWithAddresses = Customer::with('addresses:id,customer_id,label,address,is_default')->get(['id', 'name']);
        $availableEquipments = Equipment::orderBy('name')->get();
        $currentJalaliDateTime = Jalalian::now()->format('Y/m/d H:i');

        return view('contracts', [
            'editMode' => false,
            'contract' => new Contract(),
            'customers' => $customers,
            'users' => $users,
            'customersWithAddresses' => $customersWithAddresses,
            'selectedCustomerAddresses' => collect(),
            'availableEquipments' => $availableEquipments,
            'currentJalaliDateTime' => $currentJalaliDateTime,
            'contractsList' => collect(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'customer_address_id' => 'required|exists:customer_addresses,id,customer_id,' . $request->customer_id,
            'stop_count' => 'required|integer|min:0',
            'total_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'required|in:draft,active,completed,cancelled',
            'sms_sent' => 'nullable|boolean',
            'new_equipments.*.equipment_id' => 'nullable|required_with:new_equipments.*.quantity|exists:equipments,id',
            'new_equipments.*.quantity' => 'nullable|required_with:new_equipments.*.equipment_id|integer|min:1',
            'new_equipments.*.unit_price' => 'nullable|required_with:new_equipments.*.equipment_id|numeric|min:0',
            'new_equipments.*.notes' => 'nullable|string',
            'new_payments.*.title' => 'nullable|required_with:new_payments.*.amount|string|max:255',
            'new_payments.*.amount' => 'nullable|required_with:new_payments.*.title|numeric|min:0',
            'new_payments.*.paid_at' => 'nullable|required_with:new_payments.*.title|string',
            'new_payments.*.note' => 'nullable|string',
            // CORRECTED: Validation for new logs
            'new_logs.*.performed_by' => 'nullable|exists:users,id',
            'new_logs.*.performed_at' => 'nullable|string',
            'new_logs.*.description' => 'nullable|required_with:new_logs.*.performed_by|string|max:5000',
        ]);

        if ($validator->fails()) {
            return redirect()->route('contracts.create')->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $validatedContractData = $validator->safe()->only(['customer_id', 'customer_address_id', 'stop_count', 'total_price', 'description', 'assigned_to', 'status']);
            $validatedContractData['sms_sent'] = $request->has('sms_sent');
            $contract = Contract::create($validatedContractData);

            if ($request->has('new_equipments')) {
                foreach ($request->input('new_equipments', []) as $equipmentData) {
                    if (!empty($equipmentData['equipment_id']) && !empty($equipmentData['quantity'])) {
                        $equipment = Equipment::find($equipmentData['equipment_id']);
                        if ($equipment && $equipment->stock_quantity >= $equipmentData['quantity']) {
                            $contract->equipments()->create(['equipment_id' => $equipmentData['equipment_id'], 'quantity' => $equipmentData['quantity'], 'unit_price' => $equipmentData['unit_price'] ?? $equipment->price, 'notes' => $equipmentData['notes'] ?? null, ]);
                            $equipment->decrement('stock_quantity', $equipmentData['quantity']);
                        } else {
                            throw new \Exception("موجودی انبار برای تجهیز " . ($equipment->name ?? 'انتخابی') . " کافی نیست یا تجهیز یافت نشد.");
                        }
                    }
                }
            }
            if ($request->has('new_payments')) {
                foreach ($request->input('new_payments', []) as $paymentData) {
                    if (!empty($paymentData['title']) && isset($paymentData['amount'])) {
                        $contract->payments()->create(['title' => $paymentData['title'], 'amount' => $paymentData['amount'], 'paid_at' => $paymentData['paid_at'] ? Jalalian::fromFormat('Y/m/d H:i', $paymentData['paid_at'])->toCarbon() : now(), 'note' => $paymentData['note'] ?? null, ]);
                    }
                }
            }

            // CORRECTED: Handle New Logs
            if ($request->has('new_logs')) {
                foreach ($request->input('new_logs', []) as $logData) {
                    // Save if a description is provided, as that's the main content of the log.
                    if (!empty($logData['description'])) {
                        $contract->logs()->create([
                            'performed_by' => $logData['performed_by'] ?? Auth::id(), // Default to logged in user if not set
                            'performed_at' => !empty($logData['performed_at']) ? Jalalian::fromFormat('Y/m/d H:i', $logData['performed_at'])->toCarbon() : now(),
                            'description' => $logData['description'],
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('contracts.index')->with('success', 'قرارداد با موفقیت ایجاد شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error storing contract: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->route('contracts.create')->withErrors(['general' => 'خطا در ایجاد قرارداد: ' . $e->getMessage()])->withInput();
        }
    }

    public function edit(Contract $contract)
    {
        $contract->load(['customer.addresses', 'assignedUser', 'equipments.equipment', 'payments', 'logs.user']);

        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $users = User::whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
        $customersWithAddresses = Customer::with('addresses:id,customer_id,label,address,is_default')->get(['id', 'name']);
        $availableEquipments = Equipment::orderBy('name')->get();
        $currentJalaliDateTime = Jalalian::now()->format('Y/m/d H:i');

        $selectedCustomerAddresses = $contract->customer ? $contract->customer->addresses : collect();
        $contractsList = Contract::with(['customer', 'address', 'assignedUser'])->latest()->paginate(10);

        return view('contracts', [
            'editMode' => true,
            'contract' => $contract,
            'customers' => $customers,
            'users' => $users,
            'customersWithAddresses' => $customersWithAddresses,
            'selectedCustomerAddresses' => $selectedCustomerAddresses,
            'availableEquipments' => $availableEquipments,
            'currentJalaliDateTime' => $currentJalaliDateTime,
            'contractsList' => $contractsList,
        ]);
    }

    public function update(Request $request, Contract $contract)
    {
        $validator = Validator::make($request->all(), [
            'customer_address_id' => 'required|exists:customer_addresses,id,customer_id,' . $contract->customer_id,
            'stop_count' => 'required|integer|min:0',
            'total_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'required|in:draft,active,completed,cancelled',
            'sms_sent' => 'nullable|boolean',
            'equipments.*.id' => 'required_with:equipments.*.quantity|exists:contract_equipments,id,contract_id,'.$contract->id,
            'equipments.*.quantity' => 'required_with:equipments.*.id|integer|min:0',
            'equipments.*.unit_price' => 'required_with:equipments.*.id|numeric|min:0',
            'equipments.*.notes' => 'nullable|string',
            'equipments.*._remove' => 'nullable|boolean',
            'new_equipments.*.equipment_id' => 'nullable|required_with:new_equipments.*.quantity|exists:equipments,id',
            'new_equipments.*.quantity' => 'nullable|required_with:new_equipments.*.equipment_id|integer|min:1',
            'new_equipments.*.unit_price' => 'nullable|required_with:new_equipments.*.equipment_id|numeric|min:0',
            'new_equipments.*.notes' => 'nullable|string',
            'payments.*.id' => 'required_with:payments.*.title|exists:contract_payments,id,contract_id,'.$contract->id,
            'payments.*.title' => 'required_with:payments.*.id|string|max:255',
            'payments.*.amount' => 'required_with:payments.*.id|numeric|min:0',
            'payments.*.paid_at' => 'required_with:payments.*.id|string',
            'payments.*.note' => 'nullable|string',
            'payments.*._remove' => 'nullable|boolean',
            'new_payments.*.title' => 'nullable|required_with:new_payments.*.amount|string|max:255',
            'new_payments.*.amount' => 'nullable|required_with:new_payments.*.title|numeric|min:0',
            'new_payments.*.paid_at' => 'nullable|required_with:new_payments.*.title|string',
            'new_payments.*.note' => 'nullable|string',
            // CORRECTED: Validation for logs
            'logs.*.id' => 'required_with:logs.*.description|exists:contract_logs,id,contract_id,'.$contract->id,
            'logs.*.performed_by' => 'required_with:logs.*.description|exists:users,id',
            'logs.*.performed_at' => 'required_with:logs.*.description|string',
            'logs.*.description' => 'required_with:logs.*.id|string|max:5000',
            'logs.*._remove' => 'nullable|boolean',
            'new_logs.*.performed_by' => 'nullable|exists:users,id',
            'new_logs.*.performed_at' => 'nullable|string',
            'new_logs.*.description' => 'nullable|required_with:new_logs.*.performed_by|string|max:5000',
        ]);

        if ($validator->fails()) {
            return redirect()->route('contracts.edit', $contract->id)->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $validatedContractData = $validator->safe()->only(['customer_address_id', 'stop_count', 'total_price', 'description', 'assigned_to', 'status']);
            $validatedContractData['sms_sent'] = $request->has('sms_sent');
            $contract->update($validatedContractData);

            // ... existing equipment and payment update logic ...
            if ($request->has('equipments')) {
                foreach ($request->input('equipments') as $eqData) {
                    if (empty($eqData['id'])) {
                        continue;
                    } $contractEquipment = ContractEquipment::find($eqData['id']);
                    if (!$contractEquipment || $contractEquipment->contract_id != $contract->id) {
                        continue;
                    } $originalQuantity = $contractEquipment->quantity;
                    $mainEquipment = Equipment::find($contractEquipment->equipment_id);
                    if (isset($eqData['_remove']) || (isset($eqData['quantity']) && $eqData['quantity'] == 0)) {
                        if ($mainEquipment) {
                            $mainEquipment->increment('stock_quantity', $originalQuantity);
                        } $contractEquipment->delete();
                    } else {
                        $newQuantity = (int)$eqData['quantity'];
                        $quantityChange = $newQuantity - $originalQuantity;
                        if ($mainEquipment) {
                            if ($quantityChange > 0 && $mainEquipment->stock_quantity < $quantityChange) {
                                throw new \Exception("موجودی انبار برای تجهیز {$mainEquipment->name} کافی نیست.");
                            } $mainEquipment->decrement('stock_quantity', $quantityChange);
                        } $contractEquipment->update(['quantity' => $newQuantity, 'unit_price' => $eqData['unit_price'], 'notes' => $eqData['notes'] ?? $contractEquipment->notes, ]);
                    }
                }
            }
            if ($request->has('new_equipments')) {
                foreach ($request->input('new_equipments', []) as $equipmentData) {
                    if (!empty($equipmentData['equipment_id']) && !empty($equipmentData['quantity'])) {
                        $equipment = Equipment::find($equipmentData['equipment_id']);
                        if ($equipment && $equipment->stock_quantity >= $equipmentData['quantity']) {
                            $contract->equipments()->create(['equipment_id' => $equipmentData['equipment_id'], 'quantity' => $equipmentData['quantity'], 'unit_price' => $equipmentData['unit_price'] ?? $equipment->price, 'notes' => $equipmentData['notes'] ?? null, ]);
                            $equipment->decrement('stock_quantity', $equipmentData['quantity']);
                        } else {
                            throw new \Exception("موجودی انبار برای تجهیز جدید " . ($equipment->name ?? 'انتخابی') . " کافی نیست یا تجهیز یافت نشد.");
                        }
                    }
                }
            }
            if ($request->has('payments')) {
                foreach ($request->input('payments') as $paymentData) {
                    if (empty($paymentData['id'])) {
                        continue;
                    } $contractPayment = ContractPayment::find($paymentData['id']);
                    if (!$contractPayment || $contractPayment->contract_id != $contract->id) {
                        continue;
                    } if (isset($paymentData['_remove'])) {
                        $contractPayment->delete();
                    } else {
                        $contractPayment->update(['title' => $paymentData['title'], 'amount' => $paymentData['amount'], 'paid_at' => $paymentData['paid_at'] ? Jalalian::fromFormat('Y/m/d H:i', $paymentData['paid_at'])->toCarbon() : null, 'note' => $paymentData['note'] ?? $contractPayment->note, ]);
                    }
                }
            }
            if ($request->has('new_payments')) {
                foreach ($request->input('new_payments', []) as $paymentData) {
                    if (!empty($paymentData['title']) && isset($paymentData['amount'])) {
                        $contract->payments()->create(['title' => $paymentData['title'], 'amount' => $paymentData['amount'], 'paid_at' => $paymentData['paid_at'] ? Jalalian::fromFormat('Y/m/d H:i', $paymentData['paid_at'])->toCarbon() : now(), 'note' => $paymentData['note'] ?? null, ]);
                    }
                }
            }

            // CORRECTED: Handle Existing Logs
            if ($request->has('logs')) {
                foreach ($request->input('logs') as $logData) {
                    if (empty($logData['id'])) {
                        continue;
                    }
                    $contractLog = ContractLog::find($logData['id']);
                    if (!$contractLog || $contractLog->contract_id != $contract->id) {
                        continue;
                    }

                    if (isset($logData['_remove'])) {
                        $contractLog->delete();
                    } else {
                        $contractLog->update([
                            'performed_by' => $logData['performed_by'],
                            'performed_at' => !empty($logData['performed_at']) ? Jalalian::fromFormat('Y/m/d H:i', $logData['performed_at'])->toCarbon() : $contractLog->performed_at,
                            'description' => $logData['description'],
                        ]);
                    }
                }
            }

            // CORRECTED: Handle New Logs
            if ($request->has('new_logs')) {
                foreach ($request->input('new_logs', []) as $logData) {
                    if (!empty($logData['description'])) {
                        $contract->logs()->create([
                            'performed_by' => $logData['performed_by'] ?? Auth::id(),
                            'performed_at' => !empty($logData['performed_at']) ? Jalalian::fromFormat('Y/m/d H:i', $logData['performed_at'])->toCarbon() : now(),
                            'description' => $logData['description'],
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('contracts.index')->with('success', 'قرارداد با موفقیت بروزرسانی شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating contract: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->route('contracts.edit', $contract->id)->withErrors(['general' => 'خطا در بروزرسانی قرارداد: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(Contract $contract)
    {
        DB::beginTransaction();
        try {
            $contract->load('equipments', 'payments', 'logs');
            foreach ($contract->equipments as $contractEquipment) {
                $equipment = Equipment::find($contractEquipment->equipment_id);
                if ($equipment) {
                    $equipment->increment('stock_quantity', $contractEquipment->quantity);
                }
            }
            $contract->equipments()->delete();
            $contract->payments()->delete();
            $contract->logs()->delete();
            $contract->delete();
            DB::commit();
            return redirect()->route('contracts.index')->with('success', 'قرارداد با موفقیت حذف شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting contract: " . $e->getMessage());
            return redirect()->route('contracts.index')->withErrors(['general' => 'خطا در حذف قرارداد: ' . $e->getMessage()]);
        }
    }

    public function print(Contract $contract)
    {
        $contract->load('customer', 'address', 'equipments.equipment', 'payments');
        $companyInfo = [
            'name' => 'شرکت آسانسور شما',
            'registration_number' => '۱۲۳۴۵۶',
            'address' => 'آدرس شرکت شما، خیابان اصلی، پلاک ۱',
            'representative_name' => 'نام نماینده شما',
            'representative_title' => 'مدیر عامل',
            'phone' => '۰۲۱-۵۵۵۵۵۵۵۵',
        ];
        return view('contracts.print', compact('contract', 'companyInfo'));
    }
}
