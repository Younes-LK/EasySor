<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Equipment::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->sort === 'alphabet') {
            $query->orderBy('name');
        } else {
            $query->latest();
        }

        $equipments = $query->paginate(10);

        return view('equipments', [
            'equipments' => $equipments,
            'editMode' => false,
            'equipment' => null,
        ]);
    }

    public function edit($id)
    {
        $equipment = Equipment::findOrFail($id);
        $equipments = Equipment::paginate(10);

        return view('equipments', [
            'editMode' => true,
            'equipment' => $equipment,
            'equipments' => $equipments,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric',
            'stock_quantity' => 'nullable|integer',
        ]);

        Equipment::create($request->only(['name', 'price', 'stock_quantity']));

        return redirect()->route('equipments.index');
    }

    public function update(Request $request, $id)
    {
        $equipment = Equipment::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric',
            'stock_quantity' => 'nullable|integer',
        ]);

        $equipment->update($request->only(['name', 'price', 'stock_quantity']));

        return redirect()->route('equipments.index');
    }

    public function destroy($id)
    {
        $equipment = Equipment::findOrFail($id);
        $equipment->delete();

        return redirect()->route('equipments.index')->with('success', 'Equipment deleted successfully.');
    }
}
