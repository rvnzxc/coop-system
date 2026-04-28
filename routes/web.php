<?php

use App\Http\Controllers\ShopController;
use App\Http\Controllers\InventoryController;

// Shop / Home
Route::get('/', [ShopController::class, 'index'])->name('shop.index');
Route::post('/checkout', [ShopController::class, 'checkout'])->name('shop.checkout');

// Inventory Management
Route::prefix('inventory')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/add', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('/store', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/edit/{id}', [InventoryController::class, 'edit'])->name('inventory.edit');
    Route::put('/update/{id}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/delete/{id}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
    Route::get('/low-stock', [InventoryController::class, 'lowStockNotifications'])->name('inventory.low-stock');
});

Route::view('/members', 'members')->name('members');