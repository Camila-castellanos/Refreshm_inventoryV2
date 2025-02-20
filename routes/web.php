<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VendorController;
use Illuminate\Http\Request;
use Inertia\Inertia;

Route::get('/login', function() {
    return Inertia::render('Auth/Login');
});

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->name('logout');

Route::get('/', function () {
    return Inertia::render('Auth/Login', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});


Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {


    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');



    Route::prefix('inventory')->group(function () {
        Route::get('/items', function () {
            return Inertia::render('Inventory/Index', ['layout' => 'AppLayout']);
        });

        Route::get('/items/create', function () {
            return Inertia::render('Inventory/BulkCreate/InventoryItemsBulkCreate', ['layout' => 'AppLayout']);
        });

    Route::get('/vendors', function () { 
        return Inertia::render('Vendors/Vendors', ['layout' => 'AppLayout']); 
    });});


    Route::resource("vendor", VendorController::class)->except(["show", "store"]);
    Route::post("vendor/store", [VendorController::class, "store"])->name("vendor.store");
    Route::get("vendor/list", [VendorController::class, "vendorList"])->name("vendor.list");
    Route::post("vendor/datewise", [VendorController::class, "datewise"])->name("vendor.datewise");

});
