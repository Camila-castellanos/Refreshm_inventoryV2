<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProspectController;
use App\Http\Controllers\SaleController;
use Illuminate\Http\Request;
use Inertia\Inertia;


Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');

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



Route::resource('storages', StorageController::class);
Route::post('/storages/destroy', [StorageController::class, 'destroy']);



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

        Route::get('/items/bulk', function () {
            return Inertia::render('Inventory/BulkCreate/InventoryItemsBulkCreate', ['layout' => 'AppLayout']);
        });

        Route::resource("items", ItemController::class)
            ->except(["show", "update", "edit"]);

        Route::get('/storages/index', function () {
            return Inertia::render('Storages/StoragesIndex', ['layout' => 'AppLayout']);
        });

        Route::get('/vendors', function () {
            return Inertia::render('Vendors/Vendors', ['layout' => 'AppLayout']);
        });

        Route::get("items/tab/{id}", [ItemController::class, "tabItems"])->name("items.tab");
        Route::post("items/tab/store", [ItemController::class, "tabStore"])->name("tab.store");
        Route::post("items/tabmove", [ItemController::class, "tabMove"])->name("tab.move");
        Route::post("items/tabmoveall", [ItemController::class, "tabMoveAll"])->name("tab.move.all");
        Route::post("items/tabreturnmove", [ItemController::class, "tabreturnmove"])->name("tab.returnmove");
        Route::post("items/tabreorder", [ItemController::class, "tabReorder"])->name("tab.reorder");
        Route::post("items/tabremove", [ItemController::class, "tabRemove"])->name("tab.remove");
    });

    Route::prefix('contacts')->group(function () {
        Route::get('/customers', function () {
            return Inertia::render('Customers/Index', ['layout' => 'AppLayout']);
        });
        Route::resource("customers", CustomerController::class)->except(["show"]);
        Route::post("/datewise", [CustomerController::class, "datewise"])->name("customer.datewise");
        Route::get("/list", [CustomerController::class, "customersList"])->name("customer.list");
        Route::get("/email", [CustomerController::class, "marketingEmail"])->name("marketing.email");
        Route::post("/email/send", [CustomerController::class, "sendMarketingEmail"])->name("send.marketing.email");
    });

    Route::resource('prospects', ProspectController::class);

    Route::resource("vendor", VendorController::class)->except(["show", "store"]);
    Route::post("vendor/store", [VendorController::class, "store"])->name("vendor.store");
    Route::get("vendor/list", [VendorController::class, "vendorList"])->name("vendor.list");
    Route::post("vendor/datewise", [VendorController::class, "datewise"])->name("vendor.datewise");

    Route::post("sales", [SaleController::class, "store"])->name("sales.store");
    Route::get("sale/{sale}/receipt", [SaleController::class, "receipt"])->name("sales.receipt");
    Route::get("sale/{sale}/items", [SaleController::class, "soldItems"])->name("sales.sold");

});
