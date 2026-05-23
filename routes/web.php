<?php

use App\Http\Controllers\BillController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomFieldsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DraftController;
use App\Http\Controllers\EmailsController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\Inventory\IncomingRequestAppendController;
use App\Http\Controllers\Inventory\IncomingRequestInvoicePreviewController;
use App\Http\Controllers\PublicStore\PublicStoreInventoryController;
use App\Http\Controllers\PublicStore\Portal\PublicStoreSessionController;
use App\Http\Controllers\PublicStore\Portal\PublicStorePortalController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MailListController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductModelController;
use App\Http\Controllers\ProspectController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UtilitiesController;
use App\Http\Controllers\VendorController;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// =====================================================
// Customer Portal Routes (Public Store Portal)
// =====================================================
Route::prefix('public-store/portal')->name('public-store.portal.')->group(function () {
    Route::get('/', function () {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('public-store.portal.dashboard');
        }

        return redirect()->route('public-store.portal.login.show');
    })->name('home');

    // Guest routes (no auth required)
    Route::middleware('guest:customer')->group(function () {
        Route::get('/login', [PublicStoreSessionController::class, 'showLogin'])->name('login.show');
        Route::post('/login', [PublicStoreSessionController::class, 'login'])->name('login');
        Route::post('/login/activate', [PublicStoreSessionController::class, 'sendActivationLink'])->name('login.activate');
        Route::post('/login/forgot-password', [PublicStoreSessionController::class, 'sendResetLink'])->name('login.forgot-password');

        Route::get('/register', fn() => redirect()->route('public-store.portal.login.show'))->name('register.show');
        Route::post('/register', [PublicStoreSessionController::class, 'register'])->name('register');

        Route::get('/magic-link/{token}', [PublicStoreSessionController::class, 'showSetPassword'])->name('magic-link.show');
        Route::post('/set-password', [PublicStoreSessionController::class, 'setPassword'])->name('set-password');
    });

    // Authenticated routes (session-based auth via customer guard)
    Route::middleware('auth:customer')->group(function () {
        Route::get('/dashboard', [PublicStorePortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/requests', [PublicStorePortalController::class, 'requests'])->name('requests');
        Route::get('/requests/{id}', [PublicStorePortalController::class, 'showRequest'])->name('requests.show');
        Route::get('/returns', [PublicStorePortalController::class, 'returns'])->name('returns');
        Route::get('/credit', [PublicStorePortalController::class, 'credit'])->name('credit');
        Route::get('/orders', [PublicStorePortalController::class, 'orders'])->name('orders');
        Route::post('/logout', [PublicStoreSessionController::class, 'logout'])->name('logout');
        Route::put('/profile', [PublicStorePortalController::class, 'updateProfile'])->name('profile');
    });
});

Route::post('/public-store/get-unique-models', [PublicStoreInventoryController::class, 'getUniqueModelsByManufacturer'])->name('public.items.getUniqueModelsByManufacturer');
Route::get('/public-store/{shopSlug}', [PublicStoreInventoryController::class, 'index'])->name('public.inventory.shop.index');
Route::get('/public-store', function () {
    abort(404);
});
Route::get('items/tabs/{id}/items', [ItemController::class, 'getTabItems'])->name('items.tabs.items');
Route::post('publicInventory/request', [ItemController::class, 'request'])->name('items.request');

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->back();
})->name('logout');

Route::get('/', function (Request $request) {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->role === 'OWNER') {
            return redirect()->route('dashboard');
        }

        $permissions = $user->page_permissions;

        // If permissions is null or flat array (old format), convert or default
        if (is_null($permissions)) {
            $configKey = ($user->role === 'ADMIN' || $user->role === 'OWNER') ? 'permissions.defaults' : 'permissions.user_defaults';
            $permissions = config($configKey);
        }

        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?? [];
        }

        // Backward compatibility: Flat array check
        if (is_array($permissions) && ! empty($permissions) && array_keys($permissions) !== range(0, count($permissions) - 1)) {
            // It is an associative array (new format), check keys
        } elseif (is_array($permissions) && ! empty($permissions)) {
            // It is a flat array (old format), convert
            $newPerms = [];
            foreach ($permissions as $p) {
                if ($p === 'Inventory') {
                    $newPerms[$p] = ['Active Inventory', 'On Hold', 'Sold'];
                } else {
                    $newPerms[$p] = [];
                }
            }
            $permissions = $newPerms;
        }

        if (! is_array($permissions) || empty($permissions)) {
            // If empty object, default based on role
            $configKey = ($user->role === 'ADMIN' || $user->role === 'OWNER') ? 'permissions.defaults' : 'permissions.user_defaults';
            $permissions = config($configKey);
        }

        $routeMap = [
            'Dashboard' => '/dashboard',
            'Inventory' => function ($tabs) {
                if (empty($tabs)) {
                    return '/inventory/items';
                }
                if (in_array('Active Inventory', $tabs)) {
                    return '/inventory/items';
                }
                if (in_array('On Hold', $tabs)) {
                    return '/inventory/items/hold';
                }
                if (in_array('Sold', $tabs)) {
                    return '/inventory/report';
                }

                return '/inventory/items';
            },
            'Markets' => '/ecommerce/markets',
            'Accounting' => function ($tabs) {
                if (empty($tabs)) {
                    return '/accounting/payments';
                }
                if (in_array('Payments', $tabs)) {
                    return '/accounting/payments';
                }
                if (in_array('Expenses', $tabs)) {
                    return '/accounting/expenses';
                }
                if (in_array('Bills', $tabs)) {
                    return '/accounting/bills';
                }
                if (in_array('Taxes', $tabs)) {
                    return '/accounting/taxes';
                }

                return '/accounting/payments';
            },
            'Contacts' => function ($tabs) {
                if (empty($tabs)) {
                    return '/customer';
                }
                if (in_array('Customers', $tabs)) {
                    return '/customer';
                }
                if (in_array('Prospects', $tabs)) {
                    return '/prospects';
                }
                if (in_array('Vendors', $tabs)) {
                    return '/vendor';
                }
                if (in_array('Mailing list', $tabs)) {
                    return '/mailing_list';
                }
                if (in_array('Email editor', $tabs)) {
                    return '/email_templates';
                }

                return '/customer';
            },
            'Stores' => '/stores',
            'Company' => '/company',
            'Users' => '/users',
        ];

        foreach ($routeMap as $page => $resolver) {
            if (array_key_exists($page, $permissions)) {
                $url = is_callable($resolver) ? $resolver($permissions[$page] ?? []) : $resolver;

                return redirect($url);
            }
        }

        // Fallback if none match
        return redirect('/inventory/items');
    }

    return Inertia::render('Auth/Login', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->middleware([\App\Http\Middleware\DetectMarketFromHost::class]);

Route::get('invitation', [InvitationController::class, 'index'])->name('invitation');

Route::resource('storages', StorageController::class);
Route::post('/storages/destroy', [StorageController::class, 'destroy']);
Route::post('/storages/reorder', [StorageController::class, 'reorder']);

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    // Inventory Group (Base Page Permission)
    Route::group(['prefix' => 'inventory', 'name' => 'inventory.', 'middleware' => 'page.permission:Inventory'], function () {

        // Active Inventory Tab
        Route::middleware('page.permission:Inventory,Active Inventory')->group(function () {
            Route::delete('items/obliterate', [ItemController::class, 'obliterate'])->name('items.obliterate');
            Route::put('items/hold', [ItemController::class, 'hold'])->name('items.hold');
            Route::get('items/tab/{id}', [ItemController::class, 'tabItems'])->name('items.tab');
            Route::post('items/tab/store', [ItemController::class, 'tabStore'])->name('tab.store');
            Route::post('items/tabmove', [ItemController::class, 'tabMove'])->name('tab.move');
            Route::post('items/tabmoveall', [ItemController::class, 'tabMoveAll'])->name('tab.move.all');
            Route::post('items/tabreturnmove', [ItemController::class, 'tabreturnmove'])->name('tab.returnmove');
            Route::post('items/tabreorder', [ItemController::class, 'tabReorder'])->name('tab.reorder');
            Route::post('items/tabremove', [ItemController::class, 'tabRemove'])->name('tab.remove');
            Route::put('items/return', [ItemController::class, 'returnItem'])->name('items.return');
            Route::put('items/refund', [ItemController::class, 'refundItem'])->name('items.refund');
            Route::post('items/correct', [ItemController::class, 'correct'])->name('items.correct');
            Route::post('items/update', [ItemController::class, 'update'])->name('items.update');
            Route::get('items/{item}/label', [ItemController::class, 'label'])->name('items.label');
            Route::get('items/labels/{items}', [ItemController::class, 'getLabels'])->name('items.labels');
            Route::post('items/newlabels', [ItemController::class, 'getLabelsNewItems'])->name('items.newlabels');
            Route::post('items/generate-selling-prices', [ItemController::class, 'generateSellingPrice'])->name('items.generateSellingPrices');
            Route::post('items/storeWithBill', [ItemController::class, 'storeWithBill'])->name('items.storeWithBill');
            Route::post('items/get_unique_models_by_manufacturer', [ItemController::class, 'getUniqueModelsByManufacturer'])->name('Items.getUniqueModelsByManufacturer');
            Route::get('items/getItems', [ItemController::class, 'getItems'])->name('items.getItems');
            Route::post('items/get-specific-items', [ItemController::class, 'getSpecificItems'])->name('items.getSpecificItems');
            Route::get('items/incoming-requests', [ItemController::class, 'incomingRequests'])->name('items.incomingRequests');
            Route::post('items/incoming-requests/{id}/create-invoice', [ItemController::class, 'createInvoiceFromRequest'])->name('items.incomingRequests.createInvoice');
            Route::post('items/incoming-requests/{incomingRequest}/append-to-invoice', [IncomingRequestAppendController::class, 'append'])->name('items.incomingRequests.appendToInvoice');
            Route::get('items/incoming-requests/invoices/{sale}/preview', [IncomingRequestInvoicePreviewController::class, 'show'])->name('items.incomingRequests.invoices.preview');
            Route::delete('items/incoming-requests/items/{id}', [ItemController::class, 'deleteIncomingRequestItem'])->name('items.incomingRequests.deleteItem');
            Route::delete('items/incoming-requests/{id}', [ItemController::class, 'deleteIncomingRequest'])->name('items.incomingRequests.delete');
            Route::resource('items', ItemController::class)
                ->except(['show', 'update', 'edit']);

            Route::get('items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
            Route::get('items/search', [ItemController::class, 'search'])->name('items.search');
            Route::get('items/excel/create', [ItemController::class, 'excelCreate'])->name('items.excel.create');
            Route::post('items/excel/store', [ItemController::class, 'excelStore'])->name('items.excel.store');
            Route::get('items/excelDemo/download', [ItemController::class, 'excelDemoDownload'])->name('items.excel.demo.download');
            Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
            Route::get('sale/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
            Route::get('sale/{sale}/items', [SaleController::class, 'soldItems'])->name('sales.sold');

            Route::post('sales/update', [SaleController::class, 'update'])->name('sales.update');
        });

        // On Hold Tab
        Route::middleware('page.permission:Inventory,On Hold')->group(function () {
            Route::get('items/hold', [ItemController::class, 'viewHold'])->name('items.viewHold');
            Route::put('items/unhold', [ItemController::class, 'unhold'])->name('items.unhold');
        });

        // Sold Tab (Report)
        Route::middleware('page.permission:Inventory,Sold')->group(function () {
            Route::get('report', [SaleController::class, 'showReport'])->name('sales.report');
            Route::post('report', [SaleController::class, 'showReport'])->name('sales.generate_report');
        });
    });

    Route::post('/storages/assign-positions', [StorageController::class, 'assignPositions'])->name('storages.assignPositions');

    // Utilities Routes
    Route::get('/utilities/find-position', [UtilitiesController::class, 'findPositionPage'])->name('utilities.findPosition');
    Route::post('/utilities/search-position', [UtilitiesController::class, 'searchPosition'])->name('utilities.searchPosition');

    Route::middleware('page.permission:Contacts')->group(function () {
        Route::resource('prospects', ProspectController::class);

        Route::resource('customer', CustomerController::class)->except(['show']);
        Route::post('customer/datewise', [CustomerController::class, 'datewise'])->name('customer.datewise');
        Route::get('customer/list', [CustomerController::class, 'customersList'])->name('customer.list');
        Route::get('customer/email', [CustomerController::class, 'marketingEmail'])->name('marketing.email');
        Route::post('customer/email/send', [CustomerController::class, 'sendMarketingEmail'])->name('send.marketing.email');
        Route::get('/customers/by-name/{name}', [CustomerController::class, 'getByName'])->name('customer.getByName');
        Route::resource('vendor', VendorController::class)->except(['show', 'store']);
        Route::post('vendor/store', [VendorController::class, 'store'])->name('vendor.store');
        Route::get('vendor/list', [VendorController::class, 'vendorList'])->name('vendor.list');
        Route::post('vendor/datewise', [VendorController::class, 'datewise'])->name('vendor.datewise');

        Route::resource('mailing_list', MailListController::class);
        Route::post('mailing_list/send', [MailListController::class, 'send'])->name('send.mailing.list');
    });

    Route::middleware('page.permission:Accounting')->group(function () {
        Route::get('accounting/expenses', [ExpensesController::class, 'show'])->name('reports.expenses.show');
        Route::get('accounting/taxes/list', [TaxController::class, 'list'])->name('tax.list');
        Route::post('accounting/taxes/store', [TaxController::class, 'store'])->name('tax.store');
        Route::get('accounting/payments/simple', [PaymentController::class, 'getPaymentsSimpleList'])->name('payments.simpleList');
    });

    Route::get('user/locations', [LocationController::class, 'userLocations'])->name('locations.list');
    // Fetch and update user timezone
    Route::get('user/timezone', [UserController::class, 'getTimezone'])->name('user.timezone.fetch');
    Route::get('user/tabs', [\App\Http\Controllers\UserController::class, 'userTabs'])->name('tabs.user');
    Route::post('user/tab-name', [UserController::class, 'updateTabName'])->name('user.updateTabName');
    Route::put('user/timezone', [UserController::class, 'updateTimezone'])->name('user.timezone.update');
    Route::get('user/printable-tag-fields', [UserController::class, 'getPrintableTagFields'])->name('user.printableTagFields');
    Route::put('user/printable-tag-fields', [UserController::class, 'updatePrintableTagFields'])->name('user.updatePrintableTagFields');

    // Printable invoice fields
    Route::get('user/printable-invoice-fields', [UserController::class, 'getPrintableInvoiceFields'])->name('user.printableInvoiceFields');
    Route::put('user/printable-invoice-fields', [UserController::class, 'updatePrintableInvoiceFields'])->name('user.updatePrintableInvoiceFields');

    // Invoice Logo (User specific)
    Route::get('user/invoice-logo', [UserController::class, 'getInvoiceLogo'])->name('user.invoice-logo.get');
    Route::post('user/invoice-logo', [UserController::class, 'updateInvoiceLogo'])->name('user.invoice-logo.update');
    Route::delete('user/invoice-logo', [UserController::class, 'deleteInvoiceLogo'])->name('user.invoice-logo.delete');

    // Company Logo (Company wide)
    Route::get('company/invoice-logo', [CompanyController::class, 'getLogo'])->name('company.invoice-logo.get');
    Route::middleware(['role:OWNER,ADMIN'])->group(function () {
        Route::get('company/settings', [CompanyController::class, 'show'])->name('company.show');
        Route::put('company/settings', [CompanyController::class, 'update'])->name('company.update');

        Route::post('company/members', [CompanyController::class, 'storeMember'])->name('company.members.store');
        Route::put('company/members/{member}', [CompanyController::class, 'updateMemberRole'])->name('company.members.update');
        Route::put('company/members/{member}/permissions', [CompanyController::class, 'updateMemberPermissions'])->name('company.members.permissions.update');
        Route::delete('company/members/{member}', [CompanyController::class, 'removeMember'])->name('company.members.destroy');

        Route::post('company/invoice-logo', [CompanyController::class, 'updateLogo'])->name('company.invoice-logo.update');
        Route::delete('company/invoice-logo', [CompanyController::class, 'deleteLogo'])->name('company.invoice-logo.delete');
    });

    Route::middleware('page.permission:Stores')->group(function () {
        Route::resource('stores.locations', LocationController::class)->shallow();
        Route::get('locations/{location}/users', [LocationController::class, 'listUsers'])->name('locations.usersList');
        Route::post('locations/{location}/users', [LocationController::class, 'users'])->name('locations.users');
    });

    Route::middleware('page.permission:Users')->group(function () {
        Route::resource('users', UserController::class)->except(['show'])->middleware(['role:OWNER,ADMIN']);

        Route::get(
            'users/{user}/role',
            [UserController::class, 'changeRole']
        )->name('users.changeRole');

        Route::post(
            'users/{user}/role',
            [UserController::class, 'updateRole']
        )->name('users.updateRole');
    });

    Route::post(
        'users/{user}/headers',
        [UserController::class, 'updateHeaders']
    )->name('users.updateHeaders');

    Route::resource('email_templates', EmailsController::class);

    Route::post('store/contact', [ContactController::class, 'store'])->name('contact.store');

    Route::post('invitation', [InvitationController::class, 'accept'])->name('invitation');

    Route::resource('customfields', CustomFieldsController::class);
    Route::post('customfields/{id}/active', [CustomFieldsController::class, 'updateActive'])->name('customFields.updateActive');

    Route::get('drafts', [DraftController::class, 'index'])->name('drafts.index');
    Route::get('drafts/simple', [DraftController::class, 'simpleList'])->name('drafts.simpleList');
    Route::get('drafts/{draft}', [DraftController::class, 'show'])->name('drafts.show');
    Route::post('drafts', [DraftController::class, 'store'])->name('drafts.store');
    Route::delete('drafts/{draft}', [DraftController::class, 'destroy'])->name('drafts.destroy');
    Route::post('drafts/purge/{draft}', [DraftController::class, 'purgeDraft'])->name('drafts.purge');
    Route::post('payments/addNewItems', [PaymentController::class, 'addNewItems'])->name('payments.addNewItems');

    Route::middleware('page.permission:Dashboard')->group(function () {
        Route::get('dashboard/clear-cache', [DashboardController::class, 'clearCache'])->name('dashboard.clear-cache');
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::post('dashboard/update_cash', [DashboardController::class, 'updateCashOnHand'])->name('update.cash');
        Route::post('report/datewise', [DashboardController::class, 'reportDatewise'])->name('report.datewise');
        Route::post('report/datewiseByDate', [DashboardController::class, 'repostDatewiseByDate'])->name('report.datewise.date');
    });

    Route::middleware('page.permission:Inventory')->group(function () {
        Route::post('/items/assign-storage', [ItemController::class, 'assignStorage'])->name('items.assign');
    });

    Route::middleware('page.permission:Accounting')->group(function () {
        Route::delete('expenses/obliterate', [ExpensesController::class, 'obliterate'])->name('expenses.obliterate');
        Route::resource('expenses', ExpensesController::class)
            ->except(['show', 'update']);
        Route::post('expenses/update', [ExpensesController::class, 'update'])->name('expenses.update');
        Route::get('expenses/excel/create', [ExpensesController::class, 'excelCreate'])->name('expenses.excel.create');
        Route::post('expenses/excel/store', [ExpensesController::class, 'excelStore'])->name('expenses.excel.store');
        Route::get('expenses/excelDemo/download', [ExpensesController::class, 'excelDemoDownload'])->name('expenses.excel.demo.download');

        Route::get('accounting/bills', [BillController::class, 'show'])->name('reports.bills.show');
        Route::delete('bills/obliterate', [BillController::class, 'obliterate'])->name('bills.obliterate');
        Route::resource('bills', BillController::class)
            ->except(['show', 'update'])
            ->only(['index', 'create', 'store', 'destroy', 'edit']);
        Route::post('bills/update', [BillController::class, 'update'])->name('bills.update');
        Route::post('bills/recordPayment', [BillController::class, 'recordPayment'])->name('bills.record.payment');
        Route::post('bills/removePayment', [BillController::class, 'removePayment'])->name('bills.remove.payment');
        Route::post('bills/editPayment', [BillController::class, 'editPayment'])->name('bills.edit.payment');
        Route::get('bills/excel/create', [BillController::class, 'excelCreate'])->name('bills.excel.create');
        Route::post('bills/excel/store', [BillController::class, 'excelStore'])->name('bills.excel.store');
        Route::get('bills/excelDemo/download', [BillController::class, 'excelDemoDownload'])->name('bills.excel.demo.download');

        Route::get('accounting/payments', [PaymentController::class, 'show'])->name('reports.payments.show');
        Route::resource('payments', PaymentController::class)->except(['show', 'update']);
        Route::post('payments/update', [PaymentController::class, 'update'])->name('payments.update');
        Route::post('payments/remove', [PaymentController::class, 'removePayment'])->name('remove.payment');
        Route::post('payments/delete', [PaymentController::class, 'delete'])->name('payments.delete');
        Route::post('payments/edit', [PaymentController::class, 'editPayment'])->name('edit.payment');
        Route::get('accounting/payments/{id}/invoice', [PaymentController::class, 'invoice'])->name('reports.payments.invoice');
        Route::post('accounting/payments/{id}/sendInvoice', [PaymentController::class, 'sendInvoice'])->name('payments.send.invoice');
        Route::get('accounting/payments/{id}/view', [PaymentController::class, 'view'])->name('payments.view');
        Route::post('accounting/payments/{id}/invoice/paid', [PaymentController::class, 'paid'])->name('reports.payments.invoice.paid');
        Route::get('accounting/payments/search', [PaymentController::class, 'searchPayments'])->name('payments.search');
        Route::get('accounting/amount-paid-balancing-set', [PaymentController::class, 'amountPaidBalancingSet'])->name('reports.amount.paid.balancing.set');

        Route::get('accounting/taxes', [TaxController::class, 'show'])->name('reports.taxes.show');
        Route::post('accounting/taxes/update', [TaxController::class, 'update'])->name('taxes.update');
        Route::post('accounting/taxes/remove', [TaxController::class, 'remove'])->name('taxes.remove');
        Route::post('accounting/taxes/datewise', [TaxController::class, 'datewise'])->name('taxes.datewise');
        Route::resource('taxes', TaxController::class)->except(['update']);
    });

    Route::middleware('page.permission:Stores')->group(function () {
        Route::resource('stores', StoreController::class)->except(['show']);
        Route::get('receipt/detail/{id}', [StoreController::class, 'receiptDetail'])->name('receipt.detail');

        Route::get('stores/{store}/users', [StoreController::class, 'listUsers'])->name('stores.usersList');
        Route::post('stores/{store}/users', [StoreController::class, 'users'])->name('stores.users');
        Route::put('stores/{store}/receipt', [StoreController::class, 'storeReceiptSettings'])->name('stores.storeReceiptSettings');
        Route::put('stores/{store}/cut', [StoreController::class, 'updateStorePercent'])->name('stores.updateStorePercent');
    });

    // Ecommerce Admin Routes (Authenticated and company-specific)
    Route::prefix('ecommerce/markets')->name('ecommerce.markets.')->middleware('page.permission:Markets')->group(function () {
        Route::get('/', [App\Http\Controllers\Ecommerce\MarketAdminController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Ecommerce\MarketAdminController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Ecommerce\MarketAdminController::class, 'store'])->name('store');
        Route::get('/{market:id}/edit', [App\Http\Controllers\Ecommerce\MarketAdminController::class, 'edit'])->name('edit');
        Route::put('/{market:id}', [App\Http\Controllers\Ecommerce\MarketAdminController::class, 'update'])->name('update');
        Route::delete('/{market:id}', [App\Http\Controllers\Ecommerce\MarketAdminController::class, 'destroy'])->name('destroy');
        Route::get('/{market:id}/analytics', [App\Http\Controllers\Ecommerce\MarketAdminController::class, 'analytics'])->name('analytics');
        Route::patch('/{market:id}/toggle-status', [App\Http\Controllers\Ecommerce\MarketAdminController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Ecommerce Item Management Routes (Authenticated and company-specific)
    Route::prefix('ecommerce/items')->name('ecommerce.items.')->middleware('page.permission:Markets')->group(function () {
        Route::get('/{market:id}', [App\Http\Controllers\Ecommerce\MarketItemController::class, 'index'])->name('index');
        Route::get('/{market:id}/model/{model}/details', [App\Http\Controllers\Ecommerce\MarketItemController::class, 'modelDetails'])->name('model-details');
        Route::get('/{market:id}/item/{item:id}/by-model', [App\Http\Controllers\Ecommerce\MarketItemController::class, 'byModel'])->name('by-model');
        Route::post('/{market:id}/item/{item:id}/update-price', [App\Http\Controllers\Ecommerce\MarketItemController::class, 'updatePrice'])->name('update-price');
        Route::post('/{market:id}/item/{item:id}/update-description', [App\Http\Controllers\Ecommerce\MarketItemController::class, 'updateDescription'])->name('update-description');
        Route::post('/{market:id}/item/{item:id}/toggle-visibility', [App\Http\Controllers\Ecommerce\MarketItemController::class, 'toggleVisibility'])->name('toggle-visibility');
        Route::post('/{market:id}/set-bulk-visibility', [App\Http\Controllers\Ecommerce\MarketItemController::class, 'setBulkVisibility'])->name('set-bulk-visibility');
        Route::get('/{market:id}/item/{item:id}/photos', [App\Http\Controllers\Ecommerce\MarketItemController::class, 'edit'])->name('edit');
        Route::post('/{market:id}/item/{item:id}/photos', [App\Http\Controllers\Ecommerce\MarketItemController::class, 'upload'])->name('upload');
        Route::delete('/{market:id}/item/{item:id}/photos/{media}', [App\Http\Controllers\Ecommerce\MarketItemController::class, 'delete'])->name('delete');
        Route::post('/{market:id}/item/{item:id}/photos/reorder', [App\Http\Controllers\Ecommerce\MarketItemController::class, 'reorder'])->name('reorder');
    });

    // Ecommerce Orders Routes (under markets)
    Route::prefix('ecommerce/markets')->name('ecommerce.markets.')->middleware('page.permission:Markets')->group(function () {
        Route::get('/{market:id}/orders', [App\Http\Controllers\Ecommerce\OrderController::class, 'index'])->name('orders');
    });

    Route::middleware(['role:ADMIN,OWNER'])->group(function () {
        // Product Models (Global - Admin only)
        Route::resource('product-models', ProductModelController::class);
        Route::post('product-models/sync', [ProductModelController::class, 'sync'])->name('product-models.sync');
        Route::post('product-models/{productModel}/photos', [ProductModelController::class, 'uploadPhoto'])->name('product-models.photos.upload');
        Route::delete('product-models/{productModel}/photos/{media}', [ProductModelController::class, 'deletePhoto'])->name('product-models.photos.delete');
        Route::post('product-models/{productModel}/photos/reorder', [ProductModelController::class, 'reorderPhotos'])->name('product-models.photos.reorder');
        Route::get('api/product-models/search', [ProductModelController::class, 'search'])->name('product-models.search');

        // Shop endpoints used by front-end modals
        Route::get('shops/{shop}', [ShopController::class, 'show'])->name('shops.show');
        Route::put('shops/{shop}', [ShopController::class, 'update'])->name('shops.update');
        Route::get('shops/{shop}/tabs', [ShopController::class, 'getShopTabs'])->name('shops.tabs');
    });
});

// Host-based Ecommerce Routes (Custom domains / white-label)
// Refactored: Use controller methods and inject Market via middleware for clean route-model-binding
use App\Http\Controllers\Ecommerce\MarketController;

Route::domain('{custom_domain}')
    ->middleware([\App\Http\Middleware\DetectMarketFromHost::class])
    ->group(function () {
        Route::get('/', [MarketController::class, 'index'])->name('market.domain.index');
        Route::get('/products', [MarketController::class, 'products'])->name('market.domain.products');
        Route::get('/products-list', [MarketController::class, 'productsList'])->name('market.domain.productsList');
        Route::get('/category/{category}', [MarketController::class, 'category'])->name('market.domain.category');
        Route::get('/product/{item}', [MarketController::class, 'product'])->name('market.domain.product');
        Route::get('/search', [MarketController::class, 'search'])->name('market.domain.search');
        Route::get('/contact', [MarketController::class, 'contact'])->name('market.domain.contact');
        Route::get('/faq', [MarketController::class, 'faq'])->name('market.domain.faq');
        Route::get('/about', [MarketController::class, 'about'])->name('market.domain.about');
        Route::get('/cart', [MarketController::class, 'cart'])->name('market.domain.cart');
        // API endpoint for market info on custom domain
        Route::get('/api/info', [MarketController::class, 'info'])->name('market.domain.api.info');
    });

// Ecommerce Routes (Public Market - No authentication required)
Route::prefix('market/{market:slug}')->name('market.')->group(function () {
    Route::get('/', [App\Http\Controllers\Ecommerce\MarketController::class, 'index'])->name('index');
    Route::get('/products', [App\Http\Controllers\Ecommerce\MarketController::class, 'products'])->name('products');
    Route::get('/model/{model}/variants', [App\Http\Controllers\Ecommerce\MarketController::class, 'showModelVariants'])->name('model-variants');
    Route::get('/products-list', [App\Http\Controllers\Ecommerce\MarketController::class, 'productsList'])->name('products-list');
    Route::get('/category/{category}', [App\Http\Controllers\Ecommerce\MarketController::class, 'category'])->name('category');
    Route::get('/product/{item}', [App\Http\Controllers\Ecommerce\MarketController::class, 'product'])->name('product');
    Route::get('/search', [App\Http\Controllers\Ecommerce\MarketController::class, 'search'])->name('search');
    Route::get('/contact', [App\Http\Controllers\Ecommerce\MarketController::class, 'contact'])->name('contact');
    Route::get('/faq', [App\Http\Controllers\Ecommerce\MarketController::class, 'faq'])->name('faq');
    Route::get('/about', [App\Http\Controllers\Ecommerce\MarketController::class, 'about'])->name('about');
    Route::get('/cart', [App\Http\Controllers\Ecommerce\MarketController::class, 'cart'])->name('cart');
    // Order Confirmation (Protected by signature)
    Route::get('/order/{sale_id}', [App\Http\Controllers\Ecommerce\MarketController::class, 'orderConfirmation'])
        ->name('order.confirmation')
        ->middleware('signed');
})->middleware('web');

// Ecommerce API Routes (Public - for AJAX calls)
Route::prefix('api/market/{market:slug}')->name('market.api.')->group(function () {
    Route::get('/info', [App\Http\Controllers\Ecommerce\MarketController::class, 'info'])->name('info');
    Route::get('/model/{model}/variants', [App\Http\Controllers\Ecommerce\MarketController::class, 'modelVariants'])->name('model-variants');
});

// Checkout Routes (Public)
Route::prefix('market/{market:slug}/checkout')->name('market.checkout.')->group(function () {
    Route::post('/intent', [App\Http\Controllers\Ecommerce\CheckoutController::class, 'createPaymentIntent'])->name('intent');
    Route::post('/finalize', [App\Http\Controllers\Ecommerce\CheckoutController::class, 'storeOrder'])->name('finalize');
    Route::get('/stripe-key', [App\Http\Controllers\Ecommerce\CheckoutController::class, 'getStripeKey'])->name('stripe-key');
});
