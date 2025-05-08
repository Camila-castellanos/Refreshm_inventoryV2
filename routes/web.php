<?php

use App\Http\Controllers\BillController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomFieldsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailsController;
use App\Http\Controllers\ExpensesController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MailListController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProspectController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InventoryPublicController;
use App\Http\Controllers\InvitationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;


Route::get('/publicstore/{companyName}/{shopName}', [InventoryPublicController::class, "index"])->name('public.inventory.shop.index');
Route::get('/publicstore/{companyName}', function () {abort(404);});
Route::get('/publicstore', function () {abort(404);});



Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->back();
})->name('logout');

Route::get('/', function () {

    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Auth/Login', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get("invitation", [InvitationController::class, "index"])->name("invitation");

Route::resource('storages', StorageController::class);
Route::post('/storages/destroy', [StorageController::class, 'destroy']);



Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::get("dashboard", DashboardController::class)->name("dashboard");
    Route::post("dashboard/update_cash", [DashboardController::class, 'updateCashOnHand'])->name("update.cash");
    Route::post("report/datewise", [DashboardController::class, 'reportDatewise'])->name("report.datewise");
    Route::post("report/datewiseByDate", [DashboardController::class, 'repostDatewiseByDate'])->name("report.datewise.date");

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
        Route::get("items/labels/{items}", [ItemController::class, "getLabels"])->name("items.labels");
        Route::post("items/newlabels", [ItemController::class, "getLabelsNewItems"])->name("items.newlabels");

        Route::get("items/getItems", [ItemController::class, "getItems"])->name("items.getItems");
        Route::resource("items", ItemController::class)
            ->except(["show", "update", "edit"]);

        Route::get("items/{item}/edit", [ItemController::class, "edit"])->name("items.edit");

        Route::get("items/excel/create", [ItemController::class, "excelCreate"])->name("items.excel.create");
        Route::post("items/excel/store", [ItemController::class, "excelStore"])->name("items.excel.store");
        Route::get("items/excelDemo/download", [ItemController::class, "excelDemoDownload"])->name("items.excel.demo.download");

        Route::post("sales", [SaleController::class, "store"])->name("sales.store");
        Route::get("sale/{sale}/receipt", [SaleController::class, "receipt"])->name("sales.receipt");
        Route::get("sale/{sale}/items", [SaleController::class, "soldItems"])->name("sales.sold");

        Route::post("sales/update", [SaleController::class, "update"])->name("sales.update");

        Route::get("report", [SaleController::class, "showReport"])->name("sales.report");
        Route::post("report", [SaleController::class, "generateReport"])->name("sales.generate_report");
        Route::get("report/getSoldItems", [SaleController::class, "getSoldItems"])->name("sales.getSoldItems");
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

    Route::resource("mailing_list", MailListController::class);
    Route::post("mailing_list/send", [MailListController::class, 'send'])->name("send.mailing.list");
    Route::get("accounting/expenses", [ExpensesController::class, "show"])->name("reports.expenses.show");

    Route::delete("expenses/obliterate", [ExpensesController::class, "obliterate"])->name("expenses.obliterate");
    Route::resource("expenses", ExpensesController::class)
        ->except(["show", "update"]);
    Route::post("expenses/update", [ExpensesController::class, "update"])->name("expenses.update");
    Route::get("expenses/excel/create", [ExpensesController::class, "excelCreate"])->name("expenses.excel.create");
    Route::post("expenses/excel/store", [ExpensesController::class, "excelStore"])->name("expenses.excel.store");
    Route::get("expenses/excelDemo/download", [ExpensesController::class, "excelDemoDownload"])->name("expenses.excel.demo.download");

    Route::get("accounting/bills", [BillController::class, "show"])->name("reports.bills.show");
    Route::delete("bills/obliterate", [BillController::class, "obliterate"])->name("bills.obliterate");
    Route::resource("bills", BillController::class)
        ->except(["show", "update"]);
    Route::post("bills/update", [BillController::class, "update"])->name("bills.update");
    Route::post("bills/recordPayment", [BillController::class, "recordPayment"])->name("bills.record.payment");
    Route::post("bills/removePayment", [BillController::class, "removePayment"])->name("bills.remove.payment");
    Route::post("bills/editPayment", [BillController::class, "editPayment"])->name("bills.edit.payment");
    Route::get("bills/excel/create", [BillController::class, "excelCreate"])->name("bills.excel.create");
    Route::post("bills/excel/store", [BillController::class, "excelStore"])->name("bills.excel.store");
    Route::get("bills/excelDemo/download", [BillController::class, "excelDemoDownload"])->name("bills.excel.demo.download");

    Route::get("accounting/payments", [PaymentController::class, "show"])->name("reports.payments.show");
    Route::resource("payments", PaymentController::class)->except(["show", "update"]);
    Route::post("payments/update", [PaymentController::class, "update"])->name("payments.update");
    Route::post("payments/remove", [PaymentController::class, "removePayment"])->name("remove.payment");
    Route::post("payments/delete", [PaymentController::class, "delete"])->name("payments.delete");
    Route::post("payments/edit", [PaymentController::class, "editPayment"])->name("edit.payment");
    Route::get("accounting/payments/{id}/invoice", [PaymentController::class, "invoice"])->name("reports.payments.invoice");
    Route::post("accounting/payments/{id}/sendInvoice", [PaymentController::class, "sendInvoice"])->name("payments.send.invoice");
    Route::get("accounting/payments/{id}/view", [PaymentController::class, "view"])->name("payments.view");
    Route::post("accounting/payments/{id}/invoice/paid", [PaymentController::class, "paid"])->name("reports.payments.invoice.paid");
    Route::post("payments/addNewItems", [PaymentController::class, "addNewItems"])->name("payments.addNewItems");
    Route::get("accounting/amount-paid-balancing-set", [PaymentController::class, "amountPaidBalancingSet"])->name("reports.amount.paid.balancing.set");

    Route::get("accounting/taxes", [TaxController::class, "show"])->name("reports.taxes.show");
    Route::get("accounting/taxes/list", [TaxController::class, "list"])->name("tax.list");
    Route::post("accounting/taxes/store", [TaxController::class, "store"])->name("tax.store");
    Route::post("accounting/taxes/update", [TaxController::class, "update"])->name("taxes.update");
    Route::post("accounting/taxes/remove", [TaxController::class, "remove"])->name("taxes.remove");
    Route::post("accounting/taxes/datewise", [TaxController::class, "datewise"])->name("taxes.datewise");
    Route::resource("taxes", TaxController::class)->except(['update']);

    Route::resource("stores", StoreController::class)->except(["show"]);
    Route::get('receipt/detail/{id}', [StoreController::class, 'receiptDetail'])->name("receipt.detail");

    Route::get("stores/{store}/users", [StoreController::class, "listUsers"])->name("stores.usersList");
    Route::post("stores/{store}/users", [StoreController::class, "users"])->name("stores.users");
    Route::put("stores/{store}/receipt", [StoreController::class, "storeReceiptSettings"])->name("stores.storeReceiptSettings");
    Route::put("stores/{store}/cut", [StoreController::class, "updateStorePercent"])->name("stores.updateStorePercent");

    Route::get("user/locations", [LocationController::class, "userLocations"])->name("locations.list");
    Route::resource("stores.locations", LocationController::class)->shallow();
    Route::get("locations/{location}/users", [LocationController::class, "listUsers"])->name("locations.usersList");
    Route::post("locations/{location}/users", [LocationController::class, "users"])->name("locations.users");

    Route::resource("users", UserController::class)->except(["show"]);

    Route::get(
        "users/{user}/role",
        [UserController::class, "changeRole"]
    )->name('users.changeRole');

    Route::post(
        "users/{user}/role",
        [UserController::class, "updateRole"]
    )->name('users.updateRole');

    Route::post(
        "users/{user}/headers",
        [UserController::class, "updateHeaders"]
    )->name('users.updateHeaders');

    Route::resource("email_templates", EmailsController::class);

    Route::post("store/contact", [ContactController::class, "store"])->name("contact.store");

    Route::post("public/request", [ItemController::class, "request"])->name("items.request");


    Route::post("invitation", [InvitationController::class, "accept"])->name("invitation");

    Route::resource("customfields", CustomFieldsController::class);
    Route::post("customfields/{id}/active", [CustomFieldsController::class, "updateActive"])->name("customFields.updateActive");
});
