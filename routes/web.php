<?php

use App\Http\Controllers\ShopController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\SalesAnalyticsController;

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

// Members Management
Route::prefix('members')->group(function () {
    Route::get('/', [MemberController::class, 'index'])->name('members.index');
    Route::get('/create', [MemberController::class, 'create'])->name('members.create');
    Route::post('/store', [MemberController::class, 'store'])->name('members.store');
    Route::get('/edit/{id}', [MemberController::class, 'edit'])->name('members.edit');
    Route::put('/update/{id}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('/delete/{id}', [MemberController::class, 'destroy'])->name('members.destroy');
    Route::get('/analytics/{id}', [MemberController::class, 'analytics'])->name('members.analytics');
    Route::get('/analytics', [MemberController::class, 'analyticsIndex'])->name('members.analytics.index');
Route::get('/analytics/{id}', [MemberController::class, 'memberAnalytics'])->name('members.analytics');
    Route::get('/lookup', [MemberController::class, 'lookup'])->name('members.lookup');
    Route::get('/card/{id}', [MemberController::class, 'card'])->name('members.card');
});

// Sales Analytics
Route::prefix('sales-analytics')->group(function () {
    Route::get('/', [SalesAnalyticsController::class, 'index'])->name('sales-analytics.index');
    Route::get('/print', [SalesAnalyticsController::class, 'printReport'])->name('sales-analytics.print');
});