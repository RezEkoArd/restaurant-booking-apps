<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\TableController;
use Illuminate\Http\Request;

// Public routes
Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Liat Daftar Meja
Route::prefix('tables')->group(function () {
    Route::get('/', [TableController::class, 'index'])->name('tables.index');
    Route::get('/available', [TableController::class, 'getAvailable']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('orders')->group(function () {

        route::middleware('role:Pelayan')->group(function () {
            Route::post('/', [OrderController::class, 'store'])->name('orders.store');
            Route::post('/{id}/items', [OrderController::class, 'addItem'])->name('orders.addItem');
        });

        Route::get('/', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/{id}', [OrderController::class, 'show'])->name('orders.show');
        // Close Order
        Route::patch('/{id}/close', [OrderController::class, 'closeOrder'])->name('orders.close');
        Route::get('/{id}/payment', [OrderController::class, 'calculatedPayment'])->name('orders.payment');
    });

    Route::prefix('receipts')->group(function () {
        //Download receipt
        // Download receipt PDF untuk single order
        Route::get('/order/{orderId}/download', [ReceiptController::class, 'downloadReceipt'])
            ->name('receipt.download');

        // Preview receipt PDF di browser
        Route::get('/order/{orderId}/preview', [ReceiptController::class, 'previewReceipt'])
            ->name('receipt.preview');

        // Get receipt data dalam format JSON (untuk API)
        Route::get('/order/{orderId}/data', [ReceiptController::class, 'getReceiptJson'])
            ->name('receipt.json');
    });

    // Menu prefix Menu
    Route::prefix('menus')->group(function () {
        Route::get('/', [MenuController::class, 'index']);
        Route::get('/{id}', [MenuController::class, 'show']);

        Route::middleware('role:Pelayan')->group(function () {
            Route::post('/', [MenuController::class, 'store']);
            Route::put('/{id}', [MenuController::class, 'update']);
            Route::delete('/{id}', [MenuController::class, 'destroy']);
        });
    });
});
