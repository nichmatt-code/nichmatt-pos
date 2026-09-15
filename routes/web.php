<?php

use App\Http\Controllers\TransactionReceiptController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::view('profile', 'profile')->name('profile');

    Route::middleware('role:owner,kasir')->group(function () {
        Route::view('pos', 'pos')->name('pos');
        Route::get('transactions/{transaction}/receipt', TransactionReceiptController::class)
            ->name('transactions.receipt');
    });

    Route::middleware('role:owner')->group(function () {
        Route::view('products', 'products.index')->name('products.index');
        Route::view('categories', 'categories.index')->name('categories.index');
        Route::view('reports/sales', 'reports.sales')->name('reports.sales');
    });
});

require __DIR__.'/auth.php';
