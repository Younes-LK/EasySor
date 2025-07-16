<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with('addresses');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->sort === 'alphabet') {
            $query->orderBy('name');
        } else {
            $query->latest();
        }

        $customers = $query->paginate(10);

        return view('customers', [
            'customers' => $customers,
            'editMode' => false,
            'customer' => null,
        ]);
    }

    public function edit($id)
    {
        $customer = Customer::with('addresses')->findOrFail($id);
        $customers = Customer::with('addresses')->paginate(10);

        return view('customers', [
            'editMode' => true,
            'customer' => $customer,
            'customers' => $customers,
        ]);
    }

    public function store(Request $request)
    {
        $customer = Customer::create($request->only([
            'type', 'name', 'national_code', 'register_number', 'father_name', 'phone'
        ]));

        if ($request->has('addresses')) {
            foreach ($request->addresses as $address) {
                $customer->addresses()->create([
                    'address' => $address['address'],
                    'label' => $address['label'],
                    'is_default' => isset($address['is_default']),
                ]);
            }
        }

        return redirect()->route('customers.index');
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        // بروزرسانی اطلاعات مشتری
        $customer->update($request->only([
            'type', 'name', 'national_code', 'register_number', 'father_name', 'phone'
        ]));

        // مدیریت آدرس‌ها
        $addressIdsFromRequest = collect($request->addresses ?? [])->pluck('id')->filter()->all();

        // حذف آدرس‌هایی که در درخواست نیستند
        $customer->addresses()->whereNotIn('id', $addressIdsFromRequest)->delete();

        // حلقه روی آدرس‌ها برای بروزرسانی یا ایجاد
        if ($request->has('addresses')) {
            foreach ($request->addresses as $addressData) {
                if (isset($addressData['id'])) {
                    // بروزرسانی آدرس موجود
                    $address = $customer->addresses()->find($addressData['id']);
                    if ($address) {
                        $address->update([
                            'address' => $addressData['address'],
                            'label' => $addressData['label'] ?? null,
                            'is_default' => isset($addressData['is_default']),
                        ]);
                    }
                } else {
                    // ایجاد آدرس جدید
                    $customer->addresses()->create([
                        'address' => $addressData['address'],
                        'label' => $addressData['label'] ?? null,
                        'is_default' => isset($addressData['is_default']),
                    ]);
                }
            }
        }

        return redirect()->route('customers.index');
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->addresses()->delete();
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'مشتری با موفقیت حذف شد.');
    }

    public function search(Request $request)
    {
        $query = $request->get('query');
        $customers = Customer::where('name', 'like', "%{$query}%")
                             ->orWhere('phone', 'like', "%{$query}%")
                             ->with('addresses')
                             ->get();

        return response()->json($customers);
    }
}
