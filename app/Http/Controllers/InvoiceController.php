<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\User;
use App\Models\Equipment;
use App\Models\InvoiceEquipment;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['customer', 'address', 'assignedUser'])->latest();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('customer', function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%');
            })->orWhere('notes', 'like', '%' . $searchTerm . '%');
        }

        $invoicesList = $query->paginate(10);
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $users = User::whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
        $customersWithAddresses = Customer::with('addresses:id,customer_id,label,address,is_default')->get(['id', 'name']);
        $availableEquipments = Equipment::orderBy('name')->get();

        return view('invoices', [
            'editMode' => false,
            'invoice' => new Invoice(),
            'invoicesList' => $invoicesList,
            'customers' => $customers,
            'users' => $users,
            'customersWithAddresses' => $customersWithAddresses,
            'availableEquipments' => $availableEquipments,
        ]);
    }

    public function create()
    {
        return redirect()->route('invoices.index');
    }

    public function store(Request $request)
    {
        $validator = $this->validateInvoice($request);
        if ($validator->fails()) {
            return redirect()->route('invoices.index')->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $this->saveInvoiceData($request, new Invoice());
            DB::commit();
            return redirect()->route('invoices.index')->with('success', 'فاکتور با موفقیت ایجاد شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error storing invoice: " . $e->getMessage());
            return redirect()->route('invoices.index')->withErrors(['general' => 'خطا در ایجاد فاکتور: ' . $e->getMessage()])->withInput();
        }
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load(['customer.addresses', 'assignedUser', 'equipments.equipment', 'items']);
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $users = User::whereIn('role', ['admin', 'staff'])->orderBy('name')->get(['id', 'name']);
        $customersWithAddresses = Customer::with('addresses:id,customer_id,label,address,is_default')->get(['id', 'name']);
        $availableEquipments = Equipment::orderBy('name')->get();

        return view('invoices', [
            'editMode' => true,
            'invoice' => $invoice,
            'customers' => $customers,
            'users' => $users,
            'customersWithAddresses' => $customersWithAddresses,
            'availableEquipments' => $availableEquipments,
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validator = $this->validateInvoice($request);
        if ($validator->fails()) {
            return redirect()->route('invoices.edit', $invoice->id)->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $this->saveInvoiceData($request, $invoice);
            DB::commit();
            return redirect()->route('invoices.index')->with('success', 'فاکتور با موفقیت بروزرسانی شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating invoice: " . $e->getMessage());
            return redirect()->route('invoices.edit', $invoice->id)->withErrors(['general' => 'خطا در بروزرسانی فاکتور: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(Invoice $invoice)
    {
        DB::beginTransaction();
        try {
            foreach ($invoice->equipments as $invoiceEquipment) {
                if ($invoiceEquipment->equipment) {
                    $invoiceEquipment->equipment->increment('stock_quantity', $invoiceEquipment->quantity);
                }
            }
            $invoice->delete();
            DB::commit();
            return redirect()->route('invoices.index')->with('success', 'فاکتور با موفقیت حذف شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting invoice: " . $e->getMessage());
            return redirect()->route('invoices.index')->withErrors(['general' => 'خطا در حذف فاکتور.']);
        }
    }

    private function validateInvoice(Request $request)
    {
        return Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'customer_address_id' => 'required|exists:customer_addresses,id',
            'assigned_to' => 'nullable|exists:users,id',
            'total_price' => 'required|numeric|min:0',
            'status' => 'required|in:draft,sent,paid,cancelled',
            'invoice_date' => 'required|string',
        ]);
    }

    private function saveInvoiceData(Request $request, Invoice $invoice)
    {
        $invoice->fill($request->only(['customer_id', 'customer_address_id', 'assigned_to', 'notes', 'status']));
        $invoice->invoice_date = Jalalian::fromFormat('Y/m/d', $request->invoice_date)->toCarbon();
        $invoice->total_price = $request->total_price;
        $invoice->save();

        $submittedEquipmentIds = [];

        // --- Process All Submitted Equipment ---
        if ($request->has('equipments')) {
            foreach ($request->equipments as $data) {
                $equipment = Equipment::find($data['equipment_id']);
                if (!$equipment) {
                    continue;
                }

                // Case 1: Existing item marked for removal
                if (isset($data['id']) && !empty($data['id']) && isset($data['_remove'])) {
                    $invoiceEquipment = InvoiceEquipment::find($data['id']);
                    if ($invoiceEquipment) {
                        $equipment->increment('stock_quantity', $invoiceEquipment->quantity); // Return stock
                        $invoiceEquipment->delete();
                    }
                    continue; // Skip to next item
                }

                // Case 2: Update an existing item
                if (isset($data['id']) && !empty($data['id'])) {
                    $invoiceEquipment = InvoiceEquipment::find($data['id']);
                    if ($invoiceEquipment) {
                        $quantityDifference = $invoiceEquipment->quantity - $data['quantity'];
                        $equipment->increment('stock_quantity', $quantityDifference); // Adjust stock
                        $invoiceEquipment->update($data);
                        $submittedEquipmentIds[] = $invoiceEquipment->id;
                    }
                }
                // Case 3: Create a new item
                else {
                    if (empty($data['equipment_id'])) {
                        continue;
                    }
                    if ($equipment->stock_quantity >= $data['quantity']) {
                        $newEquipment = $invoice->equipments()->create($data);
                        $equipment->decrement('stock_quantity', $data['quantity']); // Decrement stock
                        $submittedEquipmentIds[] = $newEquipment->id;
                    } else {
                        throw new \Exception("موجودی انبار برای تجهیز " . ($equipment->name ?? '') . " کافی نیست.");
                    }
                }
            }
        }

        // --- Delete any old equipment that wasn't in the submission ---
        if ($invoice->id) {
            $equipmentsToDelete = $invoice->equipments()->whereNotIn('id', $submittedEquipmentIds)->get();
            foreach ($equipmentsToDelete as $itemToDelete) {
                if ($itemToDelete->equipment) {
                    $itemToDelete->equipment->increment('stock_quantity', $itemToDelete->quantity); // Return stock before deleting
                }
                $itemToDelete->delete();
            }
        }

        // --- Process Custom Items (No stock logic needed) ---
        $submittedItemIds = [];
        if ($request->has('items')) {
            foreach ($request->items as $data) {
                if (isset($data['id']) && !empty($data['id']) && isset($data['_remove'])) {
                    InvoiceItem::find($data['id'])->delete();
                    continue;
                }
                if (isset($data['id']) && !empty($data['id'])) {
                    $item = InvoiceItem::find($data['id']);
                    if ($item) {
                        $item->update($data);
                        $submittedItemIds[] = $item->id;
                    }
                } else {
                    if (empty($data['name']) && empty($data['price'])) {
                        continue;
                    }
                    $newItem = $invoice->items()->create($data);
                    $submittedItemIds[] = $newItem->id;
                }
            }
        }
        if ($invoice->id) {
            $invoice->items()->whereNotIn('id', $submittedItemIds)->delete();
        }
    }
}
