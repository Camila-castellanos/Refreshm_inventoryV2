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

    Route::post('/items/assign-storage', [ItemController::class, 'assignStorage'])->name('items.assign');

    Route::group(["prefix" => "inventory", "name" => "inventory."], function () {
        Route::delete("items/obliterate", [ItemController::class, "obliterate"])->name("items.obliterate");
        Route::put("items/hold", [ItemController::class, "hold"])->name("items.hold");
        Route::get("items/hold", [ItemController::class, "viewHold"])->name("items.viewHold");
        Route::get("items/tab/{id}", [ItemController::class, "tabItems"])->name("items.tab");
        Route::post("items/tab/store", [ItemController::class, "tabStore"])->name("tab.store");
        Route::post("items/tabmove", [ItemController::class, "tabMove"])->name("tab.move");
        Route::post("items/tabmoveall", [ItemController::class, "tabMoveAll"])->name("tab.move.all");
        Route::post("items/tabreturnmove", [ItemController::class, "tabreturnmove"])->name("tab.returnmove");
        Route::post("items/tabreorder", [ItemController::class, "tabReorder"])->name("tab.reorder");
        Route::post("items/tabremove", [ItemController::class, "tabRemove"])->name("tab.remove");
        Route::put("items/unhold", [ItemController::class, "unhold"])->name("items.unhold");
        Route::put("items/return", [ItemController::class, "returnItem"])->name("items.return");
        Route::put("items/refund", [ItemController::class, "refundItem"])->name("items.refund");
        Route::post("items/correct", [ItemController::class, "correct"])->name("items.correct");
        Route::post("items/update", [ItemController::class, "update"])->name("items.update");
        Route::get("items/{item}/label", [ItemController::class, "label"])->name("items.label");
        Route::resource("items", ItemController::class)
            ->except(["show", "update", "edit"]);

        Route::get("items/{item}/edit", [ItemController::class, "edit"])->name("items.edit");

        Route::get("items/excel/create", [ItemController::class, "excelCreate"])->name("items.excel.create");
        Route::post("items/excel/store", [ItemController::class, "excelStore"])->name("items.excel.store");
        Route::get("items/excelDemo/download", [ItemController::class, "excelDemoDownload"])->name("items.excel.demo.download");

        Route::post("sales", [SaleController::class, "store"])->name("sales.store");
        Route::get("sale/{sale}/receipt", [SaleController::class, "receipt"])->name("sales.receipt");
        Route::get("sale/{sale}/items", [SaleController::class, "soldItems"])->name("sales.sold");

        Route::get("report", [SaleController::class, "showReport"])->name("sales.report");
        Route::post("report", [SaleController::class, "generateReport"])->name("sales.generate_report");
    });

    Route::resource('prospects', ProspectController::class);

    Route::resource("customer", CustomerController::class)->except(["show"]);
    Route::post("customer/datewise", [CustomerController::class, "datewise"])->name("customer.datewise");
    Route::get("customer/list", [CustomerController::class, "customersList"])->name("customer.list");
    Route::get("customer/email", [CustomerController::class, "marketingEmail"])->name("marketing.email");
    Route::post("customer/email/send", [CustomerController::class, "sendMarketingEmail"])->name("send.marketing.email");

    Route::resource("vendor", VendorController::class)->except(["show", "store"]);
    Route::post("vendor/store", [VendorController::class, "store"])->name("vendor.store");
    Route::get("vendor/list", [VendorController::class, "vendorList"])->name("vendor.list");
    Route::post("vendor/datewise", [VendorController::class, "datewise"])->name("vendor.datewise");
});
