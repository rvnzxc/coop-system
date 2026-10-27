<?php

namespace App\Http\Controllers;

use App\Services\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Item; // Using Item model instead of Product

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $items = Item::when($search, function ($query, $search) {
            return $query->where('item_name', 'like', "%{$search}%")
                         ->orWhere('id', $search);
        })->orderBy('id', 'asc')->get();

        return view('inventory.index', compact('items', 'search'));
    }

    public function create()
    {
        $previewBarcode = Item::generateBarcode();
        session(['inventory.preview_barcode' => $previewBarcode]);

        return view('inventory.create', compact('previewBarcode'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:50',
            'manufacturer_barcode' => $this->manufacturerBarcodeRules(),
        ]);

        $barcode = session()->pull('inventory.preview_barcode');

        if (blank($barcode) || Item::where('barcode', $barcode)->exists()) {
            $barcode = Item::generateBarcode();
        }

        Item::create([
            'item_name' => $validated['item_name'],
            'quantity' => $validated['quantity'],
            'price' => $validated['price'],
            'category' => $validated['category'],
            'barcode' => $barcode,
            'manufacturer_barcode' => $validated['manufacturer_barcode'] ?? null,
        ]);

        return redirect()->route('inventory.index')->with('success', 'Item added successfully!');
    }

    public function edit($id)
    {
        $item = Item::findOrFail($id);
        return view('inventory.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = Item::findOrFail($id);

        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:50',
            'manufacturer_barcode' => $this->manufacturerBarcodeRules($item->id),
        ]);

        $item->update([
            'item_name' => $validated['item_name'],
            'quantity' => $validated['quantity'],
            'price' => $validated['price'],
            'category' => $validated['category'],
            'manufacturer_barcode' => $validated['manufacturer_barcode'] ?? null,
        ]);

        return redirect()->route('inventory.index')->with('success', 'Item updated successfully!');
    }

    private function manufacturerBarcodeRules(?int $ignoreId = null): array
    {
        $unique = Rule::unique('items', 'manufacturer_barcode');

        if ($ignoreId !== null) {
            $unique->ignore($ignoreId);
        }

        return [
            'nullable',
            'string',
            'max:64',
            $unique,
            function ($attribute, $value, $fail) {
                if (blank($value)) {
                    return;
                }

                if (Item::where('barcode', $value)->exists()) {
                    $fail('This code is already used as an internal barcode on another item.');
                }
            },
        ];
    }

    public function destroy($id)
    {
        $item = Item::findOrFail($id);
        $item->delete();
        return redirect()->route('inventory.index')->with('success', 'Item deleted successfully!');
    }

    public function downloadBarcode(string $code)
    {
        $svg = BarcodeService::generateBarcode($code);

        abort_if($svg === '', 404);

        $filename = 'barcode-'.preg_replace('/[^A-Za-z0-9._-]/', '-', $code).'.svg';

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function lowStockNotifications()
    {
        $LOW_STOCK_THRESHOLD = 10;
        
        $lowStockItems = Item::where('quantity', '<=', $LOW_STOCK_THRESHOLD)
                            ->select('id', 'item_name', 'quantity', 'category')
                            ->orderBy('quantity', 'asc')
                            ->get();
        
        return response()->json([
            'low_stock_items' => $lowStockItems,
            'count' => $lowStockItems->count(),
            'threshold' => $LOW_STOCK_THRESHOLD
        ]);
    }
}
