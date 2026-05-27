<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalyticsController;
use App\Models\Purchase;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Analytics API - no authentication required for dashboard
Route::get('/analytics/sales', [AnalyticsController::class, 'getSalesData']);

// Test endpoint
Route::get('/test', function() {
    return response()->json(['message' => 'API is working', 'timestamp' => now()]);
});

// Create sample data endpoint
Route::get('/create-sample-data', function() {
    // Create some sample purchases for the last few days
    for ($i = 0; $i < 5; $i++) {
        Purchase::create([
            'member_id' => null,
            'member_number' => null,
            'amount' => rand(1000, 5000),
            'quantity' => rand(1, 5),
            'product_name' => 'Sample Product ' . ($i + 1),
            'purchase_date' => now()->subDays($i)->format('Y-m-d'),
            'created_at' => now()->subDays($i),
            'updated_at' => now()->subDays($i)
        ]);
    }
    
    return response()->json(['message' => 'Sample data created', 'count' => 5]);
});
