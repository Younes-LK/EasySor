<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\IsAdmin;

/*Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome');*/


Route::get('/', [HomeController::class, 'index'])->name('home');




Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});



Route::middleware([IsAdmin::class])->group(function () {
    // روت‌های مخصوص ادمین‌ها
    Route::resource('users', UserController::class);

    Route::resource('contracts', ContractController::class);


    Route::resource('equipments', EquipmentController::class);

});

Route::middleware('auth')->group(function () {
    // روت‌های عمومی (بدون نیاز به میدل‌ویر IsAdmin)
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');


    Route::resource('customers', CustomerController::class);

    Route::resource('maintenances', MaintenanceController::class);

    Route::resource('repairs', RepairController::class);

    Route::post('/maintenance/{maintenance}/done', [HomeController::class, 'markMaintenanceDone'])
       ->name('home.maintenance.done');

    Route::post('/contract/{contract}/done', [HomeController::class, 'markContractDone'])
        ->name('home.contract.done');
});





require __DIR__.'/auth.php';
