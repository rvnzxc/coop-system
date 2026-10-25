<?php

use App\Http\Controllers\ShopController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\MyTransactionController;
use App\Http\Controllers\TransactionController;

// Default route - redirect to login
Route::get('/', function () {
    return redirect('/login');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Shop / Home - Cashier only
Route::middleware(['auth', 'role.cashier'])->group(function () {
    Route::get('/pos', [ShopController::class, 'index'])->name('shop.index');
    Route::post('/checkout', [ShopController::class, 'checkout'])->name('shop.checkout');
    Route::get('/items/lookup', [ShopController::class, 'lookupItem'])->name('items.lookup');
    Route::get('/members/lookup', [MemberController::class, 'lookup'])->name('members.lookup');
    Route::get('/members/{memberId}/credit-balance', [CreditController::class, 'balance'])->name('members.credit-balance');
});

// Credits Management - Admin and Cashier
Route::middleware(['auth'])->group(function () {
    Route::get('/credits', [CreditController::class, 'index'])->name('credits.index');
    Route::get('/credits/{credit}', [CreditController::class, 'show'])->name('credits.show');
    Route::post('/credits/{credit}/pay', [CreditController::class, 'pay'])->name('credits.pay');
});

// Cashier's own transactions - always scoped to the session user
Route::middleware(['auth'])->group(function () {
    Route::get('/my-transactions', [MyTransactionController::class, 'index'])->name('my-transactions.index');
});

// Inventory Management - Admin only
Route::middleware(['auth', 'role.admin'])->prefix('inventory')->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/add', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('/store', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/edit/{id}', [InventoryController::class, 'edit'])->name('inventory.edit');
    Route::put('/update/{id}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/delete/{id}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
});

// Low-stock notification endpoint - accessible to all authenticated users
Route::middleware(['auth'])->prefix('inventory')->group(function () {
    Route::get('/low-stock', [InventoryController::class, 'lowStockNotifications'])->name('inventory.low-stock');
});

// Members Management - Admin only
Route::middleware(['auth', 'role.admin'])->prefix('members')->group(function () {
    Route::get('/', [MemberController::class, 'index'])->name('members.index');
    Route::get('/create', [MemberController::class, 'create'])->name('members.create');
    Route::post('/store', [MemberController::class, 'store'])->name('members.store');
    Route::get('/edit/{id}', [MemberController::class, 'edit'])->name('members.edit');
    Route::put('/update/{id}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('/delete/{id}', [MemberController::class, 'destroy'])->name('members.destroy');
    Route::get('/analytics/{id}', [MemberController::class, 'analytics'])->name('members.analytics');
    Route::get('/analytics', [MemberController::class, 'analyticsIndex'])->name('members.analytics.index');
    Route::get('/analytics/{id}', [MemberController::class, 'memberAnalytics'])->name('members.analytics');
    Route::get('/card/{id}', [MemberController::class, 'card'])->name('members.card');
});

// Analytics - Admin only
Route::middleware(['auth', 'role.admin'])->group(function () {
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
});

// Accounts management - Admin only
Route::middleware(['auth', 'role.admin'])->prefix('accounts')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/cashiers', [AccountController::class, 'storeCashier'])->name('accounts.cashiers.store');
    Route::put('/cashiers/{user}', [AccountController::class, 'updateCashier'])->name('accounts.cashiers.update');
    Route::post('/cashiers/{user}/reset-password', [AccountController::class, 'resetCashierPassword'])->name('accounts.cashiers.reset-password');
    Route::patch('/cashiers/{user}/status', [AccountController::class, 'toggleCashierStatus'])->name('accounts.cashiers.status');
    Route::put('/me', [AccountController::class, 'updateSelf'])->name('accounts.me.update');
});

// Transactions and activity log - Admin only
Route::middleware(['auth', 'role.admin'])->prefix('transactions')->group(function () {
    Route::get('/', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/export', [TransactionController::class, 'export'])->name('transactions.export');
    Route::get('/activity', [TransactionController::class, 'activity'])->name('transactions.activity');
});