<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\RepairLog;
use App\Models\RepairPayment;
use App\Models\RepairEquipment;
use Illuminate\Http\Request;

class RepairController extends Controller
{
    public function index()
    {
        $repairs = Repair::with(['customer', 'logs', 'payments', 'equipments'])->latest()->get();
        return view('repairs.index', compact('repairs'));
    }

    public function create()
    {
        return view('repairs.create');
    }

    public function store(Request $request)
    {
        $repair = Repair::create($request->only([
            'customer_id', 'customer_address_id', 'title', 'description', 'cost',
            'assigned_to', 'performed_date', 'sms_sent'
        ]));

        return redirect()->route('repairs.index');
    }

    public function edit($id)
    {
        $repair = Repair::with(['logs', 'payments', 'equipments'])->withTrashed()->findOrFail($id);
        return view('repairs.edit', compact('repair'));
    }

    public function update(Request $request, $id)
    {
        $repair = Repair::withTrashed()->findOrFail($id);
        $repair->update($request->all());
        return redirect()->route('repairs.index');
    }

    public function destroy($id)
    {
        Repair::findOrFail($id)->delete();
        return redirect()->route('repairs.index');
    }

    public function restore($id)
    {
        Repair::withTrashed()->findOrFail($id)->restore();
        return redirect()->route('repairs.index');
    }

    public function forceDelete($id)
    {
        Repair::withTrashed()->findOrFail($id)->forceDelete();
        return redirect()->route('repairs.index');
    }
}
