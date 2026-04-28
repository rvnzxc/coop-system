<?php

namespace App\Http\Controllers;

use App\Models\Item; // This tells Laravel to use your database model
use App\Models\Member;
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
        $memberId = $request->input('member_id');
        $isNonMember = $request->input('is_non_member', 0);
        
        // Validate that either a member is selected or non-member is chosen
        if (!$memberId && !$isNonMember) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a member or choose non-member option.'
            ], 400);
        }
        
        try {
            $totalAmount = 0;
            
            foreach ($cartItems as $cartItem) {
                $itemName = $cartItem['name'];
                $quantity = $cartItem['quantity'];
                
                // Find the item in database
                $item = Item::where('item_name', $itemName)->first();
                
                if ($item) {
                    // Check if enough stock is available
                    if ($item->quantity >= $quantity) {
                        // Calculate total amount
                        $totalAmount += ($item->price * $quantity);
                        
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
            
            // Process member or non-member sale
            if ($isNonMember) {
                // Handle non-member sale - you could create a separate table or use a special member record
                // For now, we'll just log it or you could create a non-member accumulator
                // This could be extended to create sales records, etc.
            } elseif ($memberId) {
                // Update member purchase statistics
                $member = Member::find($memberId);
                if ($member) {
                    $member->total_purchases += $totalAmount;
                    $member->purchase_count += 1;
                    $member->last_purchase_date = now();
                    $member->save();
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
