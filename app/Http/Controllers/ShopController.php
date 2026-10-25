<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Credit;
use App\Models\Item;
use App\Models\Member;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'member_id' => ['nullable'],
            'is_non_member' => ['nullable'],
            'payment_method' => ['nullable', 'in:cash,credit'],
        ]);

        $cartItems = $validated['items'];
        $memberId = $validated['member_id'] ?? null;
        $isNonMember = $request->boolean('is_non_member');
        $paymentMethod = $validated['payment_method'] ?? 'cash';

        if (!$memberId && !$isNonMember) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a member or choose non-member option.'
            ], 400);
        }

        $member = null;

        if (!$isNonMember && $memberId) {
            $member = Member::find($memberId);

            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member not found.'
                ], 400);
            }
        }

        // Credit is only for members
        if ($paymentMethod === 'credit') {
            if ($isNonMember || !$memberId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credit payment is only available for members.'
                ], 400);
            }
            if (!$member || !$member->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credit is only available for active members.'
                ], 400);
            }
        }

        $lines = [];
        $totalAmount = 0;

        // Validate stock and snapshot the price at the time of sale.
        foreach ($cartItems as $cartItem) {
            $quantity = (int) $cartItem['quantity'];
            $item = $this->resolveCartItem($cartItem);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => "Item {$this->cartItemLabel($cartItem)} not found in inventory"
                ], 400);
            }

            if ($item->quantity < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Not enough stock for {$item->item_name}. Available: {$item->quantity}, Required: {$quantity}"
                ], 400);
            }

            $unitPrice = (float) $item->price;
            $subtotal = round($unitPrice * $quantity, 2);

            $lines[] = [
                'item' => $item,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ];

            $totalAmount += $subtotal;
        }

        $totalAmount = round($totalAmount, 2);
        $saleRef = now()->format('Y-m-d H:i:s');

        DB::beginTransaction();

        try {
            // The cashier always comes from the authenticated session; a
            // cashier id posted by the browser is never trusted.
            $sale = Sale::create([
                'cashier_id' => Auth::id(),
                'member_id' => $isNonMember ? null : $member?->id,
                'member_number' => $isNonMember ? null : $member?->member_number,
                'customer_type' => $isNonMember ? 'non_member' : 'member',
                'payment_method' => $isNonMember ? 'cash' : $paymentMethod,
                'total' => $totalAmount,
                'status' => Sale::STATUS_COMPLETED,
            ]);

            // Deduct inventory and create sale lines plus the legacy purchase
            // rows that analytics and member reporting still rely on.
            foreach ($lines as $line) {
                $item = $line['item'];
                $quantity = $line['quantity'];

                $item->quantity -= $quantity;
                $item->save();

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'item_id' => $item->id,
                    'product_name' => $item->item_name,
                    'quantity' => $quantity,
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                ]);

                Purchase::create([
                    'member_id'      => $isNonMember ? null : $member?->id,
                    'member_number'  => $isNonMember ? null : $member?->member_number,
                    'customer_type'  => $isNonMember ? 'non_member' : 'member',
                    'payment_method' => $isNonMember ? 'cash' : $paymentMethod,
                    'amount'         => $line['subtotal'],
                    'quantity'       => $quantity,
                    'product_name'   => $item->item_name,
                    'purchase_date'  => now()->format('Y-m-d'),
                ]);
            }

            // Update member stats
            if (!$isNonMember && $member) {
                $member->total_purchases += $totalAmount;
                $member->purchase_count += count($lines);
                $member->last_purchase_date = now();
                $member->save();
            }

            // Create credit record if credit sale
            if ($paymentMethod === 'credit' && $member) {
                $itemsSnapshot = [];
                foreach ($lines as $line) {
                    $itemsSnapshot[] = [
                        'product_name' => $line['item']->item_name,
                        'quantity'     => $line['quantity'],
                        'price'        => $line['unit_price'],
                        'subtotal'     => $line['subtotal'],
                    ];
                }

                Credit::create([
                    'member_id'      => $member->id,
                    'amount'         => $totalAmount,
                    'amount_paid'    => 0,
                    'status'         => 'unpaid',
                    'sale_reference' => $saleRef,
                    'notes'          => count($lines) . ' item(s) purchased on credit',
                    'items_snapshot' => $itemsSnapshot,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error processing checkout: ' . $e->getMessage()
            ], 500);
        }

        AuditLogger::log(AuditLog::ACTION_SALE_COMPLETED, 'sale', $sale->id, [
            'transaction_id' => $sale->id,
            'total' => (float) $sale->total,
            'payment_method' => $sale->payment_method,
            'item_count' => count($lines),
            'cashier_id' => $sale->cashier_id,
            'member_id' => $sale->member_id,
        ]);

        $message = 'Checkout completed successfully!';
        if ($paymentMethod === 'credit') {
            $message = 'Credit sale recorded. Member owes ₱' . number_format($totalAmount, 2) . '.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'transaction_id' => $sale->id,
        ]);
    }
}
