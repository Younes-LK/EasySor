<?php

namespace App\Http\Controllers;

use App\Models\RepairEquipment;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index()
    {
        $equipments = RepairEquipment::with('repair')->latest()->get();
        return view('equipments.index', compact('equipments'));
    }

    public function create()
    {
        return view('equipments.create');
    }

    public function store(Request $request)
    {
        RepairEquipment::create($request->only([
            'repair_id', 'name', 'price', 'quantity'
        ]));

        return redirect()->route('equipments.index');
    }

    public function edit($id)
    {
        $equipment = RepairEquipment::withTrashed()->findOrFail($id);
        return view('equipments.edit', compact('equipment'));
    }

    public function update(Request $request, $id)
    {
        $equipment = RepairEquipment::withTrashed()->findOrFail($id);
        $equipment->update($request->all());

        return redirect()->route('equipments.index');
    }

    public function destroy($id)
    {
        RepairEquipment::findOrFail($id)->delete();
        return redirect()->route('equipments.index');
    }

    public function restore($id)
    {
        RepairEquipment::withTrashed()->findOrFail($id)->restore();
        return redirect()->route('equipments.index');
    }

    public function forceDelete($id)
    {
        RepairEquipment::withTrashed()->findOrFail($id)->forceDelete();
        return redirect()->route('equipments.index');
    }
}
