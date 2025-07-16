<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule; // <-- ADD THIS IMPORT

class EquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Equipment::query();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('brand', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $validSortFields = ['name', 'brand', 'price', 'purchase_price', 'stock_quantity', 'unit', 'created_at'];

        if (!in_array($sortField, $validSortFields)) {
            $sortField = 'created_at';
        }
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }
        $query->orderBy($sortField, $sortDirection);

        $equipments = $query->paginate(10);

        return view('equipments', [
            'equipments' => $equipments,
            'editMode' => false,
            'equipment' => new Equipment(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('equipments', [
            'equipments' => Equipment::latest()->paginate(10),
            'editMode' => false,
            'equipment' => new Equipment(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|numeric|min:0',
            'unit' => ['required', Rule::in(['-', 'عدد', 'متر', 'کلاف'])], // <-- ADDED validation
            'brand' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('equipments.index')
                        ->withErrors($validator)
                        ->withInput();
        }

        Equipment::create($validator->validated());

        return redirect()->route('equipments.index')->with('success', 'تجهیزات با موفقیت اضافه شد.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Equipment $equipment)
    {
        return view('equipments', [
            'equipments' => Equipment::latest()->paginate(10),
            'editMode' => true,
            'equipment' => $equipment,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Equipment $equipment)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|numeric|min:0',
            'unit' => ['required', Rule::in(['-', 'عدد', 'متر', 'کلاف'])], // <-- ADDED validation
            'brand' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('equipments.edit', $equipment->id)
                        ->withErrors($validator)
                        ->withInput();
        }

        $equipment->update($validator->validated());

        return redirect()->route('equipments.index')->with('success', 'تجهیزات با موفقیت بروزرسانی شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Equipment $equipment)
    {
        $equipment->delete();
        return redirect()->route('equipments.index')->with('success', 'تجهیزات با موفقیت حذف شد.');
    }
}
