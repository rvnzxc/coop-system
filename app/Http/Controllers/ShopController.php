<?php

namespace App\Http\Controllers;

use App\Models\Item; // This tells Laravel to use your database model
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index()
    {
        // 1. Fetch all items from the 'items' table using the Item model
        $items = Item::orderBy('item_name', 'asc')->get();

        // 2. Return the view located at resources/views/shop/index.blade.php
        // 3. 'compact' passes the $items variable to the HTML so you can loop through it
        return view('shop.index', compact('items'));
    }

    public function checkout(Request $request)
    {
        $cartItems = $request->input('items', []);
        
        try {
            foreach ($cartItems as $cartItem) {
                $itemName = $cartItem['name'];
                $quantity = $cartItem['quantity'];
                
                // Find the item in database
                $item = Item::where('item_name', $itemName)->first();
                
                if ($item) {
                    // Check if enough stock is available
                    if ($item->quantity >= $quantity) {
                        // Deduct from inventory
                        $item->quantity -= $quantity;
                        $item->save();
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => "Not enough stock for {$itemName}. Available: {$item->quantity}, Required: {$quantity}"
                        ], 400);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => "Item {$itemName} not found in inventory"
                    ], 400);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Checkout completed successfully!'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing checkout: ' . $e->getMessage()
            ], 500);
        }
    }
}
