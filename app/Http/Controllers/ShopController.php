<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\Item;
use App\Models\Member;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    public function index()
    {
        $items = Item::orderBy('item_name', 'asc')->get();

        return view('shop.index', compact('items'));
    }

    /**
     * Resolve a scanned barcode to an item, for the POS scan input.
     */
    public function lookupItem(Request $request)
    {
        $code = $request->query('code');

        if (blank($code)) {
            return response()->json([
                'found'   => false,
                'message' => 'No barcode provided.',
            ]);
        }

        $item = Item::findByAnyBarcode($code);

        if (!$item) {
            return response()->json([
                'found'   => false,
                'message' => "No item matches barcode {$code}.",
            ]);
        }

        return response()->json([
            'found'      => true,
            'matched_on' => $item->matchedBarcodeColumn($code),
            'item'       => [
                'id'           => $item->id,
                'display_name' => ucwords($item->item_name),
                'price'        => (float) $item->price,
                'quantity'     => $item->quantity,
            ],
        ]);
    }

    /**
     * Match a cart line to its inventory row.
     *
     * Cart lines are keyed by item id, which is unambiguous when two products
     * share a name. The name lookup is kept only so older clients that still
     * post item_name keep working.
     */
    private function resolveCartItem(array $cartItem): ?Item
    {
        if (!empty($cartItem['id'])) {
            return Item::find($cartItem['id']);
        }

        if (!empty($cartItem['name'])) {
            return Item::where('item_name', $cartItem['name'])->first();
        }

        return null;
    }

    /**
     * Human readable label for a cart line, for error messages.
     */
    private function cartItemLabel(array $cartItem): string
    {
        return (string) ($cartItem['name'] ?? ($cartItem['id'] ?? 'item'));
    }

    public function checkout(Request $request)
    {
        $cartItems = $request->input('items', []);
        $memberId = $request->input('member_id');
        $isNonMember = $request->input('is_non_member', 0);
        $paymentMethod = $request->input('payment_method', 'cash');

        if (!$memberId && !$isNonMember) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a member or choose non-member option.'
            ], 400);
        }

        // Credit is only for members
        if ($paymentMethod === 'credit') {
            if ($isNonMember || !$memberId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credit payment is only available for members.'
                ], 400);
            }
            $member = Member::find($memberId);
            if (!$member || !$member->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credit is only available for active members.'
                ], 400);
            }
        }

        DB::beginTransaction();

        try {
            $totalAmount = 0;

            // Validate stock and calculate total
            foreach ($cartItems as $cartItem) {
                $quantity = $cartItem['quantity'];
                $item = $this->resolveCartItem($cartItem);

                if (!$item) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Item {$this->cartItemLabel($cartItem)} not found in inventory"
                    ], 400);
                }

                if ($item->quantity < $quantity) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Not enough stock for {$item->item_name}. Available: {$item->quantity}, Required: {$quantity}"
                    ], 400);
                }

                $totalAmount += ($item->price * $quantity);
            }

            // Deduct inventory and create purchase records
            $saleRef = now()->format('Y-m-d H:i:s');

            foreach ($cartItems as $cartItem) {
                $quantity = $cartItem['quantity'];
                $item = $this->resolveCartItem($cartItem);
                $item->quantity -= $quantity;
                $item->save();

                if ($isNonMember) {
                    Purchase::create([
                        'member_id'      => null,
                        'member_number'  => null,
                        'customer_type'  => 'non_member',
                        'payment_method' => 'cash',
                        'amount'         => $item->price * $quantity,
                        'quantity'       => $quantity,
                        'product_name'   => $item->item_name,
                        'purchase_date'  => now()->format('Y-m-d'),
                    ]);
                } elseif ($memberId) {
                    $member = $member ?? Member::find($memberId);
                    Purchase::create([
                        'member_id'      => $memberId,
                        'member_number'  => $member->member_number,
                        'customer_type'  => 'member',
                        'payment_method' => $paymentMethod,
                        'amount'         => $item->price * $quantity,
                        'quantity'       => $quantity,
                        'product_name'   => $item->item_name,
                        'purchase_date'  => now()->format('Y-m-d'),
                    ]);
                }
            }

            // Update member stats
            if (!$isNonMember && $memberId) {
                $member = $member ?? Member::find($memberId);
                if ($member) {
                    $member->total_purchases += $totalAmount;
                    $member->purchase_count += count($cartItems);
                    $member->last_purchase_date = now();
                    $member->save();
                }
            }

            // Create credit record if credit sale
            if ($paymentMethod === 'credit' && $memberId) {
                $itemsSnapshot = [];
                foreach ($cartItems as $cartItem) {
                    $item = $this->resolveCartItem($cartItem);
                    $itemsSnapshot[] = [
                        'product_name' => $item ? $item->item_name : $this->cartItemLabel($cartItem),
                        'quantity'     => $cartItem['quantity'],
                        'price'        => $item ? $item->price : 0,
                        'subtotal'     => $item ? $item->price * $cartItem['quantity'] : 0,
                    ];
                }

                Credit::create([
                    'member_id'      => $memberId,
                    'amount'         => $totalAmount,
                    'amount_paid'    => 0,
                    'status'         => 'unpaid',
                    'sale_reference' => $saleRef,
                    'notes'          => count($cartItems) . ' item(s) purchased on credit',
                    'items_snapshot' => $itemsSnapshot,
                ]);
            }

            DB::commit();

            $message = 'Checkout completed successfully!';
            if ($paymentMethod === 'credit') {
                $message = 'Credit sale recorded. Member owes ₱' . number_format($totalAmount, 2) . '.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error processing checkout: ' . $e->getMessage()
            ], 500);
        }
    }
}
