<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillPreviewController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\LossRecordController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SelfOrderController;
use App\Http\Controllers\Api\StockOpnameController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\TransactionHistoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::middleware('store.access')->group(function () {
            Route::get('/products', [ProductController::class, 'index']);
            Route::get('/categories', [CategoryController::class, 'index']);
            Route::get('/customers', [CustomerController::class, 'index']);
            Route::get('/transactions', [TransactionHistoryController::class, 'index']);
            Route::post('/transactions', [TransactionController::class, 'store']);
            Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
            Route::post('/self-orders/claim', [SelfOrderController::class, 'claim']);
            Route::post('/coupons/check', [CouponController::class, 'check']);
            Route::post('/bill-preview', [BillPreviewController::class, 'store']);
            Route::post('/loss-records', [LossRecordController::class, 'store']);

            // Sama seperti halaman Stock Opname di web: butuh permission
            // 'stock-opname' (bukan cuma login+store access biasa), supaya
            // aplikasi mobile tidak jadi jalan pintas melewati pengaturan
            // hak akses yang sudah diatur owner toko.
            Route::middleware('permission:stock-opname')->group(function () {
                Route::get('/stock-opnames', [StockOpnameController::class, 'index']);
                Route::post('/stock-opnames', [StockOpnameController::class, 'store']);
                Route::get('/stock-opnames/{stockOpname}', [StockOpnameController::class, 'show']);
                Route::put('/stock-opnames/{stockOpname}/counts', [StockOpnameController::class, 'updateCounts']);
                Route::post('/stock-opnames/{stockOpname}/finish', [StockOpnameController::class, 'finish']);
                Route::delete('/stock-opnames/{stockOpname}', [StockOpnameController::class, 'destroy']);
            });
        });
    });
});
