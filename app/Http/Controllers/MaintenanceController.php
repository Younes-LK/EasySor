<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\MaintenanceLog;
use App\Models\MaintenancePayment;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index()
    {
        $maintenances = Maintenance::with(['contract', 'logs', 'payments'])->latest()->get();
        return view('maintenances.index', compact('maintenances'));
    }

    public function create()
    {
        return view('maintenances.create');
    }

    public function store(Request $request)
    {
        $maintenance = Maintenance::create($request->only([
            'contract_id', 'performed_date', 'assigned_to', 'sms_sent'
        ]));

        return redirect()->route('maintenances.index');
    }

    public function edit($id)
    {
        $maintenance = Maintenance::with(['logs', 'payments'])->withTrashed()->findOrFail($id);
        return view('maintenances.edit', compact('maintenance'));
    }

    public function update(Request $request, $id)
    {
        $maintenance = Maintenance::withTrashed()->findOrFail($id);
        $maintenance->update($request->all());

        return redirect()->route('maintenances.index');
    }

    public function destroy($id)
    {
        Maintenance::findOrFail($id)->delete();
        return redirect()->route('maintenances.index');
    }

    public function restore($id)
    {
        Maintenance::withTrashed()->findOrFail($id)->restore();
        return redirect()->route('maintenances.index');
    }

    public function forceDelete($id)
    {
        Maintenance::withTrashed()->findOrFail($id)->forceDelete();
        return redirect()->route('maintenances.index');
    }
}
