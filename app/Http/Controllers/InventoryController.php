<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        return view('inventory.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:50'
        ]);

        Item::create([
            'item_name' => $request->item_name,
            'quantity' => $request->quantity,
            'price' => $request->price,
            'category' => $request->category
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
        $request->validate([
            'item_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:50'
        ]);

        $item = Item::findOrFail($id);
        $item->update([
            'item_name' => $request->item_name,
            'quantity' => $request->quantity,
            'price' => $request->price,
            'category' => $request->category
        ]);

        return redirect()->route('inventory.index')->with('success', 'Item updated successfully!');
    }

    public function destroy($id)
    {
        $item = Item::findOrFail($id);
        $item->delete();
        return redirect()->route('inventory.index')->with('success', 'Item deleted successfully!');
    }
}
