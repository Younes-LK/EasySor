<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\Customer;
use App\Models\User;
use App\Models\Equipment;
use App\Models\RepairEquipment;
use App\Models\RepairPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Optional: For logging errors
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\Route; // Added for Route::currentRouteName()

class RepairController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Repair::with(['customer', 'address', 'user']); // 'user' is the relation for assigned_to

        // Search logic
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('customer', function ($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', '%' . $searchTerm . '%');
                  });
            });
        }

        // Sort logic
        $sortField = $request->input('sort_field', 'performed_date'); // Default sort
        $sortDirection = $request->input('sort_direction', 'desc');
        $validSortFields = ['performed_date', 'cost', 'created_at'];

        if (!in_array($sortField, $validSortFields)) {
            $sortField = 'performed_date';
        }
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }
        $query->orderBy($sortField, $sortDirection);

        $repairsList = $query->paginate(10);

        // Data for the form if it's displayed on the index page
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $users = User::whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
        $customersWithAddresses = Customer::with('addresses:id,customer_id,label,address,is_default')->get(['id', 'name']);
        $availableEquipments = Equipment::orderBy('name')->get();
        $currentJalaliDateTime = Jalalian::now()->format('Y/m/d H:i');

        return view('repairs', [
            'repairsList' => $repairsList,
            'customers' => $customers,
            'users' => $users,
            'editMode' => false,
            'repair' => new Repair(), // For the create form part of the page
            'customersWithAddresses' => $customersWithAddresses,
            'selectedCustomerAddresses' => collect(),
            'availableEquipments' => $availableEquipments,
            'currentJalaliDateTime' => $currentJalaliDateTime,
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

        return view('repairs', [ // Assuming repairs.blade.php handles create form
            'editMode' => false,
            'repair' => new Repair(),
            'customers' => $customers,
            'users' => $users,
            'customersWithAddresses' => $customersWithAddresses,
            'selectedCustomerAddresses' => collect(),
            'availableEquipments' => $availableEquipments,
            'currentJalaliDateTime' => $currentJalaliDateTime,
            'repairsList' => collect(), // Empty list for create page context
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'performed_date' => 'required|string', // Will be parsed from Jalali
            'sms_sent' => 'nullable|boolean',

            'new_equipments.*.equipment_id' => 'nullable|required_with:new_equipments.*.quantity|exists:equipments,id',
            'new_equipments.*.quantity' => 'nullable|required_with:new_equipments.*.equipment_id|integer|min:1',
            'new_equipments.*.unit_price' => 'nullable|required_with:new_equipments.*.equipment_id|numeric|min:0',
            'new_equipments.*.notes' => 'nullable|string',

            'new_payments.*.title' => 'nullable|required_with:new_payments.*.amount|string|max:255',
            'new_payments.*.amount' => 'nullable|required_with:new_payments.*.title|numeric|min:0',
            'new_payments.*.paid_at' => 'nullable|required_with:new_payments.*.title|string', // Parsed from Jalali
            'new_payments.*.note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('repairs.create')
                        ->withErrors($validator)
                        ->withInput();
        }

        $validatedRepairData = $validator->safe()->only([
            'customer_id', 'customer_address_id', 'title', 'description',
            'cost', 'assigned_to'
        ]);
        $validatedRepairData['sms_sent'] = $request->has('sms_sent');
        $validatedRepairData['performed_date'] = Jalalian::fromFormat('Y/m/d', $request->performed_date)->toCarbon();


        DB::beginTransaction();
        try {
            $repair = Repair::create($validatedRepairData);

            // Handle new equipments
            if ($request->has('new_equipments')) {
                foreach ($request->input('new_equipments', []) as $equipmentData) {
                    if (!empty($equipmentData['equipment_id']) && !empty($equipmentData['quantity'])) {
                        $equipment = Equipment::find($equipmentData['equipment_id']);
                        if ($equipment && $equipment->stock_quantity >= $equipmentData['quantity']) {
                            $repair->equipments()->create([
                                'equipment_id' => $equipmentData['equipment_id'],
                                'quantity' => $equipmentData['quantity'],
                                'unit_price' => $equipmentData['unit_price'] ?? $equipment->price,
                                'notes' => $equipmentData['notes'] ?? null,
                            ]);
                            $equipment->decrement('stock_quantity', $equipmentData['quantity']);
                        } else {
                            throw new \Exception("موجودی انبار برای تجهیز " . ($equipment->name ?? 'انتخابی') . " کافی نیست یا تجهیز یافت نشد.");
                        }
                    }
                }
            }

            // Handle new payments
            if ($request->has('new_payments')) {
                foreach ($request->input('new_payments', []) as $paymentData) {
                    if (!empty($paymentData['title']) && isset($paymentData['amount'])) {
                        $repair->payments()->create([
                            'title' => $paymentData['title'],
                            'amount' => $paymentData['amount'],
                            'paid_at' => $paymentData['paid_at'] ? Jalalian::fromFormat('Y/m/d H:i', $paymentData['paid_at'])->toCarbon() : now(),
                            'note' => $paymentData['note'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('repairs.index')->with('success', 'تعمیر با موفقیت ایجاد شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error storing repair: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return redirect()->route('repairs.create')->withErrors(['general' => 'خطا در ایجاد تعمیر: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $repair = Repair::with([
            'customer.addresses',
            'user', // Relation for assigned_to
            'equipments.equipment',
            'payments'
        ])->findOrFail($id);

        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $users = User::whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
        $customersWithAddresses = Customer::with('addresses:id,customer_id,label,address,is_default')->get(['id', 'name']);
        $availableEquipments = Equipment::orderBy('name')->get();
        $currentJalaliDateTime = Jalalian::now()->format('Y/m/d H:i');

        $selectedCustomerAddresses = $repair->customer ? $repair->customer->addresses : collect();

        // For the list part of the blade, if you show it on the edit page
        $repairsList = collect(); // Typically, list is not needed on a dedicated edit page
        // If repairs.blade.php is also the index, then fetch the list like in index()
        if (Route::currentRouteName() == 'repairs.index' || !view()->exists('repairs.edit')) { // Heuristic
            $query = Repair::with(['customer', 'address', 'user']);
            $repairsList = $query->orderBy('performed_date', 'desc')->paginate(10);
        }


        return view('repairs', [ // Assuming repairs.blade.php handles edit form
            'editMode' => true,
            'repair' => $repair,
            'customers' => $customers,
            'users' => $users,
            'customersWithAddresses' => $customersWithAddresses,
            'selectedCustomerAddresses' => $selectedCustomerAddresses,
            'availableEquipments' => $availableEquipments,
            'currentJalaliDateTime' => $currentJalaliDateTime,
            'repairsList' => $repairsList,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $repair = Repair::findOrFail($id);

        $validator = Validator::make($request->all(), [
            // customer_id is disabled on edit, so not validated here for change
            'customer_address_id' => 'required|exists:customer_addresses,id,customer_id,' . $repair->customer_id,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'performed_date' => 'required|string', // Parsed from Jalali
            'sms_sent' => 'nullable|boolean',

            'equipments.*.id' => 'required_with:equipments.*.quantity|exists:repair_equipments,id,repair_id,'.$id,
            'equipments.*.quantity' => 'required_with:equipments.*.id|integer|min:0',
            'equipments.*.unit_price' => 'required_with:equipments.*.id|numeric|min:0',
            'equipments.*.notes' => 'nullable|string',
            'equipments.*._remove' => 'nullable|boolean',

            'new_equipments.*.equipment_id' => 'nullable|required_with:new_equipments.*.quantity|exists:equipments,id',
            'new_equipments.*.quantity' => 'nullable|required_with:new_equipments.*.equipment_id|integer|min:1',
            'new_equipments.*.unit_price' => 'nullable|required_with:new_equipments.*.equipment_id|numeric|min:0',
            'new_equipments.*.notes' => 'nullable|string',

            'payments.*.id' => 'required_with:payments.*.title|exists:repair_payments,id,repair_id,'.$id,
            'payments.*.title' => 'required_with:payments.*.id|string|max:255',
            'payments.*.amount' => 'required_with:payments.*.id|numeric|min:0',
            'payments.*.paid_at' => 'required_with:payments.*.id|string', // Parsed from Jalali
            'payments.*.note' => 'nullable|string',
            'payments.*._remove' => 'nullable|boolean',

            'new_payments.*.title' => 'nullable|required_with:new_payments.*.amount|string|max:255',
            'new_payments.*.amount' => 'nullable|required_with:new_payments.*.title|numeric|min:0',
            'new_payments.*.paid_at' => 'nullable|required_with:new_payments.*.title|string', // Parsed from Jalali
            'new_payments.*.note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('repairs.edit', $repair->id)
                        ->withErrors($validator)
                        ->withInput();
        }

        DB::beginTransaction();
        try {
            $validatedRepairData = $validator->safe()->only([
                'customer_address_id', 'title', 'description',
                'cost', 'assigned_to'
            ]);
            $validatedRepairData['sms_sent'] = $request->has('sms_sent');
            $validatedRepairData['performed_date'] = Jalalian::fromFormat('Y/m/d', $request->performed_date)->toCarbon();

            $repair->update($validatedRepairData);

            // Handle Existing Equipments
            if ($request->has('equipments')) {
                foreach ($request->input('equipments') as $eqData) {
                    if (empty($eqData['id'])) {
                        continue;
                    } // Should not happen with validation
                    $repairEquipment = RepairEquipment::find($eqData['id']);
                    if (!$repairEquipment || $repairEquipment->repair_id != $repair->id) {
                        continue;
                    }

                    $originalQuantity = $repairEquipment->quantity;
                    $mainEquipment = Equipment::find($repairEquipment->equipment_id);

                    if (isset($eqData['_remove']) || (isset($eqData['quantity']) && $eqData['quantity'] == 0)) {
                        if ($mainEquipment) {
                            $mainEquipment->increment('stock_quantity', $originalQuantity);
                        }
                        $repairEquipment->delete();
                    } else {
                        $newQuantity = (int)$eqData['quantity'];
                        $quantityChange = $newQuantity - $originalQuantity;

                        if ($mainEquipment) {
                            if ($quantityChange > 0 && $mainEquipment->stock_quantity < $quantityChange) {
                                throw new \Exception("موجودی انبار برای تجهیز {$mainEquipment->name} کافی نیست.");
                            }
                            $mainEquipment->decrement('stock_quantity', $quantityChange);
                        }
                        $repairEquipment->update([
                            'quantity' => $newQuantity,
                            'unit_price' => $eqData['unit_price'],
                            'notes' => $eqData['notes'] ?? $repairEquipment->notes,
                        ]);
                    }
                }
            }

            // Handle New Equipments
            if ($request->has('new_equipments')) {
                foreach ($request->input('new_equipments', []) as $equipmentData) {
                    if (!empty($equipmentData['equipment_id']) && !empty($equipmentData['quantity'])) {
                        $equipment = Equipment::find($equipmentData['equipment_id']);
                        if ($equipment && $equipment->stock_quantity >= $equipmentData['quantity']) {
                            $repair->equipments()->create([
                                'equipment_id' => $equipmentData['equipment_id'],
                                'quantity' => $equipmentData['quantity'],
                                'unit_price' => $equipmentData['unit_price'] ?? $equipment->price,
                                'notes' => $equipmentData['notes'] ?? null,
                            ]);
                            $equipment->decrement('stock_quantity', $equipmentData['quantity']);
                        } else {
                            throw new \Exception("موجودی انبار برای تجهیز جدید " . ($equipment->name ?? 'انتخابی') . " کافی نیست یا تجهیز یافت نشد.");
                        }
                    }
                }
            }

            // Handle Existing Payments
            if ($request->has('payments')) {
                foreach ($request->input('payments') as $paymentData) {
                    if (empty($paymentData['id'])) {
                        continue;
                    }
                    $repairPayment = RepairPayment::find($paymentData['id']);
                    if (!$repairPayment || $repairPayment->repair_id != $repair->id) {
                        continue;
                    }

                    if (isset($paymentData['_remove'])) {
                        $repairPayment->delete();
                    } else {
                        $repairPayment->update([
                            'title' => $paymentData['title'],
                            'amount' => $paymentData['amount'],
                            'paid_at' => $paymentData['paid_at'] ? Jalalian::fromFormat('Y/m/d H:i', $paymentData['paid_at'])->toCarbon() : null,
                            'note' => $paymentData['note'] ?? $repairPayment->note,
                        ]);
                    }
                }
            }
            // Handle New Payments
            if ($request->has('new_payments')) {
                foreach ($request->input('new_payments', []) as $paymentData) {
                    if (!empty($paymentData['title']) && isset($paymentData['amount'])) {
                        $repair->payments()->create([
                            'title' => $paymentData['title'],
                            'amount' => $paymentData['amount'],
                            'paid_at' => $paymentData['paid_at'] ? Jalalian::fromFormat('Y/m/d H:i', $paymentData['paid_at'])->toCarbon() : now(),
                            'note' => $paymentData['note'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('repairs.index')->with('success', 'تعمیر با موفقیت بروزرسانی شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating repair: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return redirect()->route('repairs.edit', $repair->id)->withErrors(['general' => 'خطا در بروزرسانی تعمیر: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $repair = Repair::with('equipments')->findOrFail($id);

            // Restore stock for equipments linked to this repair
            foreach ($repair->equipments as $repairEquipment) {
                $equipment = Equipment::find($repairEquipment->equipment_id);
                if ($equipment) {
                    $equipment->increment('stock_quantity', $repairEquipment->quantity);
                }
            }
            // Related repair_equipments and repair_payments will be deleted by cascading if set up in migrations,
            // or delete them manually here if not.
            $repair->equipments()->delete();
            $repair->payments()->delete();
            $repair->delete();

            DB::commit();
            return redirect()->route('repairs.index')->with('success', 'تعمیر با موفقیت حذف و موجودی تجهیزات بازگردانده شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting repair: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return redirect()->route('repairs.index')->withErrors(['general' => 'خطا در حذف تعمیر: ' . $e->getMessage()]);
        }
    }
}
