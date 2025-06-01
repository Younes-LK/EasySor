<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\IsAdmin;

/*Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome');*/


Route::get('/', function () {
    return view('home');
})->name('home');



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});



Route::middleware([IsAdmin::class])->group(function () {
    // روت‌های مخصوص ادمین‌ها
    Route::get('/users', function () {
        return view('users');
    })->name('users');


    Route::resource('contracts', ContractController::class);


    Route::resource('equipments', EquipmentController::class);

});

Route::middleware('auth')->group(function () {
    // روت‌های عمومی (بدون نیاز به میدل‌ویر IsAdmin)
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');


    Route::resource('customers', CustomerController::class);

    Route::get('/maintenances', function () {
        return view('maintenances');
    })->name('maintenances');

    Route::get('/repairs', function () {
        return view('repairs');
    })->name('repairs');
});





require __DIR__.'/auth.php';
