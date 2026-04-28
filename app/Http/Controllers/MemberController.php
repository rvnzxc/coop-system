<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $members = Member::when($search, function ($query, $search) {
            return $query->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
        })->orderBy('last_name', 'asc')->get();

        return view('members.index', compact('members', 'search'));
    }

    public function create()
    {
        return view('members.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:members,email',
            'phone' => 'nullable|string|max:20',
        ]);

        $member = Member::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'member_number' => 'MEM' . str_pad(Member::max('id') + 1, 5, '0', STR_PAD_LEFT),
        ]);

        return redirect()->route('members.index')->with('success', 'Member added successfully!');
    }

    public function edit($id)
    {
        $member = Member::findOrFail($id);
        return view('members.edit', compact('member'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:members,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $member = Member::findOrFail($id);
        $member->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('members.index')->with('success', 'Member updated successfully!');
    }

    public function destroy($id)
    {
        $member = Member::findOrFail($id);
        $member->delete();
        return redirect()->route('members.index')->with('success', 'Member deleted successfully!');
    }

    public function analytics($id)
    {
        $member = Member::findOrFail($id);
        
        // Get purchase statistics
        $totalPurchases = $member->total_purchases;
        $purchaseCount = $member->purchase_count;
        $lastPurchaseDate = $member->last_purchase_date;
        
        // Debug logging
        \Log::info('Member Analytics Debug', [
            'member_id' => $id,
            'last_purchase_date_raw' => $lastPurchaseDate,
            'last_purchase_date_string' => $lastPurchaseDate ? $lastPurchaseDate->toDateTimeString() : 'null',
            'current_time' => now()->toDateTimeString(),
            'timezone' => config('app.timezone')
        ]);
        
        $averagePurchase = $purchaseCount > 0 ? $totalPurchases / $purchaseCount : 0;

        return view('members.analytics', compact('member', 'totalPurchases', 'purchaseCount', 'lastPurchaseDate', 'averagePurchase'));
    }

    public function lookup(Request $request)
    {
        $q = $request->query('q');
        $member = Member::where('member_number', $q)
                        ->orWhere('id', $q)
                        ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$q}%")
                        ->first();
        
        if ($member) {
            return response()->json(['found' => true, 'member' => $member]);
        }
        return response()->json(['found' => false]);
    }

    public function card($id)
    {
        $member = Member::findOrFail($id);
        return view('members.card', compact('member'));
    }
}
