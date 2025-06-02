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
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\Auth; // Import Auth facade
use Illuminate\Support\Facades\Gate; // Import Gate facade (optional, for more complex policies)
use Illuminate\Support\Facades\Route;

class RepairController extends Controller
{
    public function index(Request $request)
    {
        $query = Repair::with(['customer', 'address', 'user']); // 'user' is the relation for assigned_to

        // Authorization: Filter for staff
        if (Auth::check() && Auth::user()->isStaff()) {
            $query->where('assigned_to', Auth::id());
        }
        // Admins will see all by default

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

        $sortField = $request->input('sort_field', 'performed_date');
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
            'repair' => new Repair(),
            'customersWithAddresses' => $customersWithAddresses,
            'selectedCustomerAddresses' => collect(),
            'availableEquipments' => $availableEquipments,
            'currentJalaliDateTime' => $currentJalaliDateTime,
        ]);
    }

    public function create()
    {
        // Authorization: Only admins can create
        if (Auth::check() && !Auth::user()->isAdmin()) {
            return redirect()->route('repairs.index')->withErrors(['general' => 'شما اجازه ایجاد تعمیر جدید را ندارید.']);
        }

        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $users = User::whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
        $customersWithAddresses = Customer::with('addresses:id,customer_id,label,address,is_default')->get(['id', 'name']);
        $availableEquipments = Equipment::orderBy('name')->get();
        $currentJalaliDateTime = Jalalian::now()->format('Y/m/d H:i');
        $currentJalaliDate = Jalalian::now()->format('Y/m/d');


        return view('repairs', [
            'editMode' => false,
            'repair' => new Repair(),
            'customers' => $customers,
            'users' => $users,
            'customersWithAddresses' => $customersWithAddresses,
            'selectedCustomerAddresses' => collect(),
            'availableEquipments' => $availableEquipments,
            'currentJalaliDateTime' => $currentJalaliDateTime,
            'currentJalaliDate' => $currentJalaliDate,
            'repairsList' => collect(),
        ]);
    }

    public function store(Request $request)
    {
        // Authorization: Only admins can store new repairs
        if (Auth::check() && !Auth::user()->isAdmin()) {
            // Or use abort(403, 'Unauthorized action.');
            return redirect()->route('repairs.index')->withErrors(['general' => 'شما اجازه ثبت تعمیر جدید را ندارید.']);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'customer_address_id' => 'required|exists:customer_addresses,id,customer_id,' . $request->customer_id,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'performed_date' => 'required|string',
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

    public function edit($id)
    {
        $repair = Repair::with([
            'customer.addresses',
            'user',
            'equipments.equipment',
            'payments'
        ])->findOrFail($id);

        // Authorization: Staff can only edit their own repairs
        if (Auth::check() && Auth::user()->isStaff() && $repair->assigned_to !== Auth::id()) {
            return redirect()->route('repairs.index')->withErrors(['general' => 'شما اجازه ویرایش این تعمیر را ندارید.']);
        }

        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $users = User::whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
        $customersWithAddresses = Customer::with('addresses:id,customer_id,label,address,is_default')->get(['id', 'name']);
        $availableEquipments = Equipment::orderBy('name')->get();
        $currentJalaliDateTime = Jalalian::now()->format('Y/m/d H:i');
        $currentJalaliDate = Jalalian::now()->format('Y/m/d');

        $selectedCustomerAddresses = $repair->customer ? $repair->customer->addresses : collect();

        $repairsList = collect();
        if (Route::currentRouteName() == 'repairs.index' || !view()->exists('repairs.edit')) {
            $query = Repair::with(['customer', 'address', 'user']);
            if (Auth::check() && Auth::user()->isStaff()) { // Also filter list in background if shown on edit page
                $query->where('assigned_to', Auth::id());
            }
            $repairsList = $query->orderBy('performed_date', 'desc')->paginate(10);
        }

        return view('repairs', [
            'editMode' => true,
            'repair' => $repair,
            'customers' => $customers,
            'users' => $users,
            'customersWithAddresses' => $customersWithAddresses,
            'selectedCustomerAddresses' => $selectedCustomerAddresses,
            'availableEquipments' => $availableEquipments,
            'currentJalaliDateTime' => $currentJalaliDateTime,
            'currentJalaliDate' => $currentJalaliDate,
            'repairsList' => $repairsList,
        ]);
    }

    public function update(Request $request, $id)
    {
        $repair = Repair::findOrFail($id);

        // Authorization: Staff can only update their own repairs
        if (Auth::check() && Auth::user()->isStaff() && $repair->assigned_to !== Auth::id()) {
            return redirect()->route('repairs.index')->withErrors(['general' => 'شما اجازه بروزرسانی این تعمیر را ندارید.']);
        }
        // Admins can update any

        $validator = Validator::make($request->all(), [
            'customer_address_id' => 'required|exists:customer_addresses,id,customer_id,' . $repair->customer_id,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'performed_date' => 'required|string',
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
            'payments.*.paid_at' => 'required_with:payments.*.id|string',
            'payments.*.note' => 'nullable|string',
            'payments.*._remove' => 'nullable|boolean',

            'new_payments.*.title' => 'nullable|required_with:new_payments.*.amount|string|max:255',
            'new_payments.*.amount' => 'nullable|required_with:new_payments.*.title|numeric|min:0',
            'new_payments.*.paid_at' => 'nullable|required_with:new_payments.*.title|string',
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
            // If user is staff, they cannot change the assigned_to field to someone else
            if (Auth::user()->isStaff() && isset($validatedRepairData['assigned_to']) && $validatedRepairData['assigned_to'] != Auth::id()) {
                $validatedRepairData['assigned_to'] = Auth::id(); // Force assign to self or remove this field from update for staff
            }


            $validatedRepairData['sms_sent'] = $request->has('sms_sent');
            $validatedRepairData['performed_date'] = Jalalian::fromFormat('Y/m/d', $request->performed_date)->toCarbon();

            $repair->update($validatedRepairData);

            // Handle Existing Equipments (Same logic as before)
            if ($request->has('equipments')) {
                foreach ($request->input('equipments') as $eqData) {
                    if (empty($eqData['id'])) {
                        continue;
                    }
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

            // Handle New Equipments (Same logic as before)
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

            // Handle Existing Payments (Same logic as before)
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
            // Handle New Payments (Same logic as before)
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

    public function destroy($id)
    {
        $repair = Repair::with('equipments')->findOrFail($id);

        // Authorization: Only admins can delete, or staff their own (adjust as needed)
        if (Auth::check() && Auth::user()->isStaff() && $repair->assigned_to !== Auth::id()) {
            // If you want staff to not delete at all, remove the $repair->assigned_to !== Auth::id() part
            // and just check if !Auth::user()->isAdmin()
            return redirect()->route('repairs.index')->withErrors(['general' => 'شما اجازه حذف این تعمیر را ندارید.']);
        }
        // Admins can delete any

        DB::beginTransaction();
        try {
            foreach ($repair->equipments as $repairEquipment) {
                $equipment = Equipment::find($repairEquipment->equipment_id);
                if ($equipment) {
                    $equipment->increment('stock_quantity', $repairEquipment->quantity);
                }
            }
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
