<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with('addresses')->latest()->get();
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $customer = Customer::create($request->only([
            'type', 'name', 'national_code', 'economic_code', 'phone'
        ]));

        if ($request->has('addresses')) {
            foreach ($request->addresses as $address) {
                $customer->addresses()->create($address);
            }
        }

        return redirect()->route('customers.index');
    }

    public function edit($id)
    {
        $customer = Customer::with('addresses')->withTrashed()->findOrFail($id);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::withTrashed()->findOrFail($id);
        $customer->update($request->all());

        return redirect()->route('customers.index');
    }

    public function destroy($id)
    {
        Customer::findOrFail($id)->delete();
        return redirect()->route('customers.index');
    }

    public function restore($id)
    {
        Customer::withTrashed()->findOrFail($id)->restore();
        return redirect()->route('customers.index');
    }

    public function forceDelete($id)
    {
        Customer::withTrashed()->findOrFail($id)->forceDelete();
        return redirect()->route('customers.index');
    }
}
