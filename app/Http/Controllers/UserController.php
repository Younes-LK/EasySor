<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth; // For checking current user

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query(); // Use User::withTrashed() if you want to include soft-deleted users

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('phone', 'like', '%' . $searchTerm . '%')
                  ->orWhere('national_code', 'like', '%' . $searchTerm . '%');
            });
        }

        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $validSortFields = ['name', 'phone', 'national_code', 'role', 'is_active', 'created_at'];

        if (!in_array($sortField, $validSortFields)) {
            $sortField = 'created_at';
        }
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }
        $query->orderBy($sortField, $sortDirection);

        $users = $query->paginate(10);

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')],
            'national_code' => ['required', 'string', 'max:10', Rule::unique('users', 'national_code')],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            'role' => ['required', Rule::in(['admin', 'staff'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('users.create')
                        ->withErrors($validator)
                        ->withInput();
        }

        $userData = $validator->validated();
        $userData['password'] = Hash::make($userData['password']);
        $userData['is_active'] = $request->has('is_active'); // Handles checkbox

        User::create($userData);

        return redirect()->route('users.index')->with('success', 'کاربر با موفقیت ایجاد شد.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user) // Route model binding
    {
        // Use User::withTrashed()->findOrFail($id) if you want to edit soft-deleted users
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user) // Route model binding
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'national_code' => ['required', 'string', 'max:10', Rule::unique('users', 'national_code')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()], // Password is optional
            'role' => ['required', Rule::in(['admin', 'staff'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('users.edit', $user->id)
                        ->withErrors($validator)
                        ->withInput();
        }

        $userData = $validator->safe()->except('password'); // Get all validated data except password initially
        $userData['is_active'] = $request->has('is_active');

        // Only update password if a new one is provided
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        // Prevent admin from deactivating or changing their own role if they are the only admin
        if ($user->id === Auth::id() && $user->isAdmin()) {
            $adminCount = User::where('role', 'admin')->where('is_active', true)->count();
            if ($adminCount <= 1) {
                if (!$userData['is_active']) {
                    return redirect()->route('users.edit', $user->id)->withErrors(['is_active' => 'شما نمی‌توانید تنها مدیر فعال سیستم را غیرفعال کنید.'])->withInput();
                }
                if ($userData['role'] !== 'admin') {
                    return redirect()->route('users.edit', $user->id)->withErrors(['role' => 'شما نمی‌توانید نقش تنها مدیر فعال سیستم را تغییر دهید.'])->withInput();
                }
            }
        }


        $user->update($userData);

        return redirect()->route('users.index')->with('success', 'اطلاعات کاربر با موفقیت بروزرسانی شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user) // Route model binding
    {
        // Prevent self-deletion
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')->withErrors(['general' => 'شما نمی‌توانید حساب کاربری خود را حذف کنید.']);
        }

        // Prevent deletion of the only admin
        if ($user->isAdmin()) {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return redirect()->route('users.index')->withErrors(['general' => 'شما نمی‌توانید تنها مدیر سیستم را حذف کنید.']);
            }
        }

        // If using SoftDeletes trait in User model, this will soft delete.
        // To permanently delete, you would use $user->forceDelete();
        $user->delete();

        return redirect()->route('users.index')->with('success', 'کاربر با موفقیت حذف شد.');
    }

    // If you implement SoftDeletes in your User model, you might want these methods:
    /*
    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        return redirect()->route('users.index')->with('success', 'کاربر با موفقیت بازیابی شد.');
    }

    public function forceDelete($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        // Add checks here to prevent self-force-deletion or deletion of only admin
        if ($user->id === Auth::id()) {
             return redirect()->route('users.index')->withErrors(['general' => 'عملیات غیر مجاز.']);
        }
        $user->forceDelete();
        return redirect()->route('users.index')->with('success', 'کاربر برای همیشه حذف شد.');
    }
    */
}
