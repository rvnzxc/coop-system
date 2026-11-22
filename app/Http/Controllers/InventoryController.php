<?php

namespace App\Http\Controllers;

use App\Services\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Category;
use App\Models\Item; // Using Item model instead of Product

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $sort = in_array($request->input('sort'), ['high', 'low'], true) ? $request->input('sort') : null;

        $items = Item::with('categoryInfo')
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('item_name', 'like', "%{$search}%")
                          ->orWhere('id', $search);
                });
            })
            ->when($sort === 'high', fn ($query) => $query->orderByDesc('quantity'))
            ->when($sort === 'low', fn ($query) => $query->orderBy('quantity'))
            ->orderBy('id', 'asc')
            ->get();

        $categories = Category::withCount('items')->orderBy('name')->get();

        return view('inventory.index', compact('items', 'search', 'sort', 'categories'));
    }

    public function create()
    {
        $previewBarcode = Item::generateBarcode();
        session(['inventory.preview_barcode' => $previewBarcode]);

        $categories = Category::orderBy('name')->get();

        return view('inventory.create', compact('previewBarcode', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'category' => $this->categoryRules(),
            'manufacturer_barcode' => $this->manufacturerBarcodeRules(),
        ]);

        $barcode = session()->pull('inventory.preview_barcode');

        if (blank($barcode) || Item::where('barcode', $barcode)->exists()) {
            $barcode = Item::generateBarcode();
        }

        $category = $this->resolveCategory($validated['category']);

        Item::create([
            'item_name' => $validated['item_name'],
            'quantity' => $validated['quantity'],
            'price' => $validated['price'],
            'category' => $category->name,
            'category_id' => $category->id,
            'barcode' => $barcode,
            'manufacturer_barcode' => $validated['manufacturer_barcode'] ?? null,
        ]);

        return redirect()->route('inventory.index')->with('success', 'Item added successfully!');
    }

    public function edit($id)
    {
        $item = Item::findOrFail($id);
        $categories = Category::orderBy('name')->get();

        return view('inventory.edit', compact('item', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $item = Item::findOrFail($id);

        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'category' => $this->categoryRules(),
            'manufacturer_barcode' => $this->manufacturerBarcodeRules($item->id),
        ]);

        $category = $this->resolveCategory($validated['category']);

        $item->update([
            'item_name' => $validated['item_name'],
            'quantity' => $validated['quantity'],
            'price' => $validated['price'],
            'category' => $category->name,
            'category_id' => $category->id,
            'manufacturer_barcode' => $validated['manufacturer_barcode'] ?? null,
        ]);

        return redirect()->route('inventory.index')->with('success', 'Item updated successfully!');
    }

    private function categoryRules(): array
    {
        return [
            'required',
            'string',
            'max:50',
            function ($attribute, $value, $fail) {
                $name = trim((string) $value);

                if (! Category::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->exists()) {
                    $fail('Please choose a valid category.');
                }
            },
        ];
    }

    private function resolveCategory(string $name): Category
    {
        return Category::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($name))])->firstOrFail();
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
