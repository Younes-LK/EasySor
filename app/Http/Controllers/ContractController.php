<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use App\Models\Equipment;
use App\Models\ContractEquipment;
use App\Models\ContractPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

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
        ]);

        if ($validator->fails()) {
            return redirect()->route('contracts.create')
                        ->withErrors($validator)
                        ->withInput();
        }

        $validatedContractData = $validator->safe()->only([
            'customer_id', 'customer_address_id', 'stop_count', 'total_price',
            'description', 'assigned_to', 'status'
        ]);
        $validatedContractData['sms_sent'] = $request->has('sms_sent');

        DB::beginTransaction();
        try {
            $contract = Contract::create($validatedContractData);

            if ($request->has('new_equipments')) {
                foreach ($request->input('new_equipments', []) as $equipmentData) {
                    if (!empty($equipmentData['equipment_id']) && !empty($equipmentData['quantity'])) {
                        $equipment = Equipment::find($equipmentData['equipment_id']);
                        if ($equipment && $equipment->stock_quantity >= $equipmentData['quantity']) {
                            $contract->equipments()->create([
                                'equipment_id' => $equipmentData['equipment_id'],
                                'quantity' => $equipmentData['quantity'],
                                'unit_price' => $equipmentData['unit_price'] ?? $equipment->price,
                                'notes' => $equipmentData['notes'] ?? null,
                            ]);
                            $equipment->decrement('stock_quantity', $equipmentData['quantity']);
                        } else {
                            throw new \Exception("موجودی انبار برای تجهیز {$equipment->name} کافی نیست یا تجهیز یافت نشد.");
                        }
                    }
                }
            }

            if ($request->has('new_payments')) {
                foreach ($request->input('new_payments', []) as $paymentData) {
                    if (!empty($paymentData['title']) && isset($paymentData['amount'])) {
                        $contract->payments()->create([
                            'title' => $paymentData['title'],
                            'amount' => $paymentData['amount'],
                            'paid_at' => $paymentData['paid_at'] ? Jalalian::fromFormat('Y/m/d H:i', $paymentData['paid_at'])->toCarbon() : now(),
                            'note' => $paymentData['note'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('contracts.index')->with('success', 'قرارداد با موفقیت ایجاد شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('contracts.create')->withErrors(['general' => 'خطا در ایجاد قرارداد: ' . $e->getMessage()])->withInput();
        }
    }

    public function edit($id)
    {
        $contract = Contract::with([
            'customer.addresses',
            'assignedUser',
            'equipments.equipment',
            'payments'
        ])->findOrFail($id);

        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $users = User::whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
        $customersWithAddresses = Customer::with('addresses:id,customer_id,label,address,is_default')->get(['id', 'name']);
        $availableEquipments = Equipment::orderBy('name')->get();
        $currentJalaliDateTime = Jalalian::now()->format('Y/m/d H:i'); // <-- MODIFIED: Added

        $selectedCustomerAddresses = $contract->customer ? $contract->customer->addresses : collect();

        // This logic for $contractsList might be for when the edit form is part of the index page
        // If 'contracts.blade.php' is a dedicated edit page, this might not be needed or could be simplified.
        $contractsListQuery = Contract::with(['customer', 'address', 'assignedUser']);
        if (request()->filled('search')) {
            $searchTerm = request('search');
            $contractsListQuery->where(function ($q) use ($searchTerm) {
                $q->where('description', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('customer', function ($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', '%' . $searchTerm . '%');
                  });
            });
        }
        $sortField = request()->input('sort_field', 'created_at');
        $sortDirection = request()->input('sort_direction', 'desc');
        $contractsListQuery->orderBy($sortField, $sortDirection);
        $contractsList = $contractsListQuery->paginate(10);


        return view('contracts', [
            'editMode' => true,
            'contract' => $contract,
            'customers' => $customers,
            'users' => $users,
            'customersWithAddresses' => $customersWithAddresses,
            'selectedCustomerAddresses' => $selectedCustomerAddresses,
            'availableEquipments' => $availableEquipments,
            'currentJalaliDateTime' => $currentJalaliDateTime, // Passed to view
            'contractsList' => $contractsList,
        ]);
    }

    public function update(Request $request, $id)
    {
        $contract = Contract::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'customer_address_id' => 'required|exists:customer_addresses,id,customer_id,' . $contract->customer_id,
            'stop_count' => 'required|integer|min:0',
            'total_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'required|in:draft,active,completed,cancelled',
            'sms_sent' => 'nullable|boolean',

            'equipments.*.id' => 'required|exists:contract_equipments,id,contract_id,'.$id,
            'equipments.*.quantity' => 'required|integer|min:0',
            'equipments.*.unit_price' => 'required|numeric|min:0',
            'equipments.*.notes' => 'nullable|string',
            'equipments.*._remove' => 'nullable|boolean',

            'new_equipments.*.equipment_id' => 'nullable|required_with:new_equipments.*.quantity|exists:equipments,id',
            'new_equipments.*.quantity' => 'nullable|required_with:new_equipments.*.equipment_id|integer|min:1',
            'new_equipments.*.unit_price' => 'nullable|required_with:new_equipments.*.equipment_id|numeric|min:0',
            'new_equipments.*.notes' => 'nullable|string',

            'payments.*.id' => 'required|exists:contract_payments,id,contract_id,'.$id,
            'payments.*.title' => 'required|string|max:255',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.paid_at' => 'required|string',
            'payments.*.note' => 'nullable|string',
            'payments.*._remove' => 'nullable|boolean',

            'new_payments.*.title' => 'nullable|required_with:new_payments.*.amount|string|max:255',
            'new_payments.*.amount' => 'nullable|required_with:new_payments.*.title|numeric|min:0',
            'new_payments.*.paid_at' => 'nullable|required_with:new_payments.*.title|string',
            'new_payments.*.note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('contracts.edit', $contract->id)
                        ->withErrors($validator)
                        ->withInput();
        }

        DB::beginTransaction();
        try {
            $validatedContractData = $validator->safe()->only([
                'customer_address_id', 'stop_count', 'total_price',
                'description', 'assigned_to', 'status'
            ]);
            $validatedContractData['sms_sent'] = $request->has('sms_sent');
            $contract->update($validatedContractData);

            if ($request->has('equipments')) {
                foreach ($request->input('equipments') as $eqData) {
                    $contractEquipment = ContractEquipment::find($eqData['id']);
                    if (!$contractEquipment || $contractEquipment->contract_id != $contract->id) {
                        continue;
                    }

                    $originalQuantity = $contractEquipment->quantity;
                    $mainEquipment = Equipment::find($contractEquipment->equipment_id);

                    if (isset($eqData['_remove']) || (isset($eqData['quantity']) && $eqData['quantity'] == 0)) {
                        if ($mainEquipment) {
                            $mainEquipment->increment('stock_quantity', $originalQuantity);
                        }
                        $contractEquipment->delete();
                    } else {
                        $newQuantity = (int)$eqData['quantity'];
                        $quantityChange = $newQuantity - $originalQuantity;

                        if ($mainEquipment) {
                            if ($quantityChange > 0 && $mainEquipment->stock_quantity < $quantityChange) {
                                throw new \Exception("موجودی انبار برای تجهیز {$mainEquipment->name} کافی نیست.");
                            }
                            $mainEquipment->decrement('stock_quantity', $quantityChange);
                        }
                        $contractEquipment->update([
                            'quantity' => $newQuantity,
                            'unit_price' => $eqData['unit_price'],
                            'notes' => $eqData['notes'] ?? $contractEquipment->notes,
                        ]);
                    }
                }
            }

            if ($request->has('new_equipments')) {
                foreach ($request->input('new_equipments', []) as $equipmentData) {
                    if (!empty($equipmentData['equipment_id']) && !empty($equipmentData['quantity'])) {
                        $equipment = Equipment::find($equipmentData['equipment_id']);
                        if ($equipment && $equipment->stock_quantity >= $equipmentData['quantity']) {
                            $contract->equipments()->create([
                                'equipment_id' => $equipmentData['equipment_id'],
                                'quantity' => $equipmentData['quantity'],
                                'unit_price' => $equipmentData['unit_price'] ?? $equipment->price,
                                'notes' => $equipmentData['notes'] ?? null,
                            ]);
                            $equipment->decrement('stock_quantity', $equipmentData['quantity']);
                        } else {
                            throw new \Exception("موجودی انبار برای تجهیز جدید {$equipment->name} کافی نیست یا تجهیز یافت نشد.");
                        }
                    }
                }
            }

            if ($request->has('payments')) {
                foreach ($request->input('payments') as $paymentData) {
                    $contractPayment = ContractPayment::find($paymentData['id']);
                    if (!$contractPayment || $contractPayment->contract_id != $contract->id) {
                        continue;
                    }

                    if (isset($paymentData['_remove'])) {
                        $contractPayment->delete();
                    } else {
                        $contractPayment->update([
                            'title' => $paymentData['title'],
                            'amount' => $paymentData['amount'],
                            'paid_at' => $paymentData['paid_at'] ? Jalalian::fromFormat('Y/m/d H:i', $paymentData['paid_at'])->toCarbon() : null,
                            'note' => $paymentData['note'] ?? $contractPayment->note,
                        ]);
                    }
                }
            }
            if ($request->has('new_payments')) {
                foreach ($request->input('new_payments', []) as $paymentData) {
                    if (!empty($paymentData['title']) && isset($paymentData['amount'])) {
                        $contract->payments()->create([
                            'title' => $paymentData['title'],
                            'amount' => $paymentData['amount'],
                            'paid_at' => $paymentData['paid_at'] ? Jalalian::fromFormat('Y/m/d H:i', $paymentData['paid_at'])->toCarbon() : now(),
                            'note' => $paymentData['note'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('contracts.index')->with('success', 'قرارداد با موفقیت بروزرسانی شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('contracts.edit', $contract->id)->withErrors(['general' => 'خطا در بروزرسانی قرارداد: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $contract = Contract::with('equipments')->findOrFail($id);

            foreach ($contract->equipments as $contractEquipment) {
                $equipment = Equipment::find($contractEquipment->equipment_id);
                if ($equipment) {
                    $equipment->increment('stock_quantity', $contractEquipment->quantity);
                }
            }
            $contract->equipments()->delete();
            $contract->payments()->delete();
            $contract->delete();

            DB::commit();
            return redirect()->route('contracts.index')->with('success', 'قرارداد با موفقیت حذف و موجودی تجهیزات بازگردانده شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('contracts.index')->withErrors(['general' => 'خطا در حذف قرارداد: ' . $e->getMessage()]);
        }
    }
}
