<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractLog;
use App\Models\ContractPayment;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index()
    {
        $contracts = Contract::with(['customer', 'logs', 'payments'])->latest()->get();
        return view('contracts.index', compact('contracts'));
    }

    public function create()
    {
        return view('contracts.create');
    }

    public function store(Request $request)
    {
        $contract = Contract::create($request->only([
            'customer_id', 'customer_address_id', 'start_date', 'end_date',
            'monthly_fee', 'type', 'assigned_to', 'sms_sent'
        ]));

        return redirect()->route('contracts.index');
    }

    public function edit($id)
    {
        $contract = Contract::with(['logs', 'payments'])->withTrashed()->findOrFail($id);
        return view('contracts.edit', compact('contract'));
    }

    public function update(Request $request, $id)
    {
        $contract = Contract::withTrashed()->findOrFail($id);
        $contract->update($request->all());

        return redirect()->route('contracts.index');
    }

    public function destroy($id)
    {
        Contract::findOrFail($id)->delete();
        return redirect()->route('contracts.index');
    }

    public function restore($id)
    {
        Contract::withTrashed()->findOrFail($id)->restore();
        return redirect()->route('contracts.index');
    }

    public function forceDelete($id)
    {
        Contract::withTrashed()->findOrFail($id)->forceDelete();
        return redirect()->route('contracts.index');
    }
}
