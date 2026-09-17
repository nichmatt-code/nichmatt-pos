<?php

use App\Http\Controllers\LossRecordReceiptController;
use App\Http\Controllers\MidtransNotificationController;
use App\Http\Controllers\PosBillController;
use App\Http\Controllers\ProductLabelController;
use App\Http\Controllers\SelfOrderQrController;
use App\Http\Controllers\SelfOrderQrDownloadController;
use App\Http\Controllers\TransactionReceiptController;
use App\Models\StockOpname;
use App\Models\Store;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');

Route::post('midtrans/notification', MidtransNotificationController::class)
    ->name('midtrans.notification');

// Public self-order menu: customers scan a QR/link tied to the store's
// order_token, no login required.
Route::get('order/{store:order_token}', function (Store $store) {
    return view('self-order.menu', ['store' => $store]);
})->name('self-order.menu');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('billing/subscribe', 'billing.subscribe')->name('billing.subscribe');

    Route::view('profile', 'profile')->name('profile');

    Route::middleware('store.access')->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');

        Route::view('pos', 'pos')->name('pos');
        Route::get('pos/bill', PosBillController::class)->name('pos.bill');
        Route::get('transactions/{transaction}/receipt', TransactionReceiptController::class)
            ->name('transactions.receipt');
        Route::get('loss-records/{lossRecord}/receipt', LossRecordReceiptController::class)
            ->name('loss-records.receipt');
        Route::get('self-order-qr', SelfOrderQrController::class)->name('self-order.qr');
        Route::get('self-order-qr/download', SelfOrderQrDownloadController::class)->name('self-order.qr.download');

        Route::view('products', 'products.index')->name('products.index')
            ->middleware('permission:products');
        Route::get('products/{product}/label', ProductLabelController::class)
            ->name('products.label')
            ->middleware('permission:products');
        Route::view('categories', 'categories.index')->name('categories.index')
            ->middleware('permission:categories');
        Route::view('tags', 'tags.index')->name('tags.index')
            ->middleware('permission:products');
        Route::view('packages', 'packages.index')->name('packages.index')
            ->middleware('permission:products');
        Route::view('coupons', 'coupons.index')->name('coupons.index')
            ->middleware('permission:products');
        Route::view('inventory', 'inventory.index')->name('inventory.index')
            ->middleware('permission:inventory');
        Route::view('units', 'units.index')->name('units.index')
            ->middleware('permission:inventory');
        Route::view('customers', 'customers.index')->name('customers.index')
            ->middleware('permission:customers');
        Route::view('stock-opname', 'stock-opname.index')->name('stock-opname.index')
            ->middleware('permission:stock-opname');
        Route::get('stock-opname/{stockOpname}', function (StockOpname $stockOpname) {
            return view('stock-opname.show', ['stockOpname' => $stockOpname]);
        })->name('stock-opname.show')->middleware('permission:stock-opname');
        Route::view('reports/sales', 'reports.sales')->name('reports.sales')
            ->middleware('permission:reports');
        Route::view('reports/stock-opname', 'reports.stock-opname')->name('reports.stock-opname')
            ->middleware('permission:reports');
        Route::view('reports/inventory-monitor', 'reports.inventory-monitor')->name('reports.inventory-monitor')
            ->middleware('permission:reports');
        Route::view('preparation', 'preparation.index')->name('preparation.index')
            ->middleware('permission:preparation');

        Route::view('branch', 'branch.settings')->name('branch.settings')
            ->middleware('permission:store-settings');
        Route::view('team', 'team.index')->name('team.index')
            ->middleware('permission:employees');
    });

    // Cross-tenant platform admin: not gated by store.access, since a
    // developer must be able to use this even if their own store's trial
    // or subscription has lapsed.
    Route::middleware('developer')->prefix('developer')->name('developer.')->group(function () {
        Route::view('stores', 'developer.stores')->name('stores.index');
        Route::get('stores/{store}', function (Store $store) {
            return view('developer.store-show', ['store' => $store]);
        })->name('stores.show');
        Route::view('team', 'developer.team')->name('team');
        Route::view('pricing', 'developer.pricing')->name('pricing');
    });
});

require __DIR__.'/auth.php';
