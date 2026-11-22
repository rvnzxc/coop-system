<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab') === 'archived' ? 'archived' : 'active';
        $search = $request->input('search');

        $membersQuery = $tab === 'archived' ? Member::archived() : Member::active();

        $members = $membersQuery
            ->when($search, function ($query, $search) {
                return $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($tab === 'archived', function ($query) {
                return $query->orderByDesc('archived_at');
            })
            ->orderBy('last_name', 'asc')
            ->get();

        return view('members.index', [
            'members' => $members,
            'search' => $search,
            'tab' => $tab,
            'archivedCount' => Member::archived()->count(),
        ]);
    }

    public function create()
    {
        return view('members.create');
    }

    public function store(Request $request)
    {
        $this->normalizeMemberInput($request);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\p{M} \'.-]+$/u'],
            'last_name'  => ['required', 'string', 'max:255', 'regex:/^[\p{L}\p{M} \'.-]+$/u'],
            'email'      => 'nullable|email|unique:members,email',
            'phone'      => ['nullable', 'regex:/^\+639\d{9}$/'],
            'photo'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ], [
            'first_name.regex' => 'First name must contain letters only.',
            'last_name.regex' => 'Last name must contain letters only.',
            'phone.regex' => 'Enter a valid PH mobile number (e.g. +63 912 345 6789).',
            'photo.image' => 'Please upload a JPG, PNG, or WebP image.',
            'photo.mimes' => 'Please upload a JPG, PNG, or WebP image.',
            'photo.max' => 'Photo must not be larger than 2 MB.',
        ]);

        $member = Member::create([
            'first_name'   => $validated['first_name'],
            'last_name'    => $validated['last_name'],
            'email'        => $validated['email'] ?? null,
            'phone'        => $validated['phone'] ?? null,
            'photo'        => $this->storeMemberPhoto($request),
            'member_number'=> 'MEM' . str_pad(Member::max('id') + 1, 5, '0', STR_PAD_LEFT),
        ]);

        return redirect()
            ->route('members.index')
            ->with('success', 'Member added successfully!');
    }

    public function edit($id)
    {
        $member = Member::findOrFail($id);

        return view('members.edit', compact('member'));
    }

    public function update(Request $request, $id)
    {
        $this->normalizeMemberInput($request);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\p{M} \'.-]+$/u'],
            'last_name'  => ['required', 'string', 'max:255', 'regex:/^[\p{L}\p{M} \'.-]+$/u'],
            'email'      => 'nullable|email|unique:members,email,' . $id,
            'phone'      => ['nullable', 'regex:/^\+639\d{9}$/'],
            'photo'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'is_active'  => 'boolean',
        ], [
            'first_name.regex' => 'First name must contain letters only.',
            'last_name.regex' => 'Last name must contain letters only.',
            'phone.regex' => 'Enter a valid PH mobile number (e.g. +63 912 345 6789).',
            'photo.image' => 'Please upload a JPG, PNG, or WebP image.',
            'photo.mimes' => 'Please upload a JPG, PNG, or WebP image.',
            'photo.max' => 'Photo must not be larger than 2 MB.',
        ]);

        $member = Member::findOrFail($id);

        $updateData = [
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'] ?? null,
            'phone'      => $validated['phone'] ?? null,
            'is_active'  => $request->has('is_active'),
        ];

        if ($request->hasFile('photo')) {
            try {
                $updateData['photo'] = $this->storeMemberPhoto($request);
            } catch (\Throwable $e) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['photo' => 'Could not save the photo. Please try again.'])
                    ->with('error', 'Could not save the photo. Please try again.');
            }

            $this->deleteMemberPhoto($member->photo);
        } elseif ($request->boolean('remove_photo')) {
            $this->deleteMemberPhoto($member->photo);
            $updateData['photo'] = null;
        }

        $member->update($updateData);

        return redirect()
            ->route('members.index')
            ->with('success', 'Member updated successfully!');
    }

    public function archive(Request $request, $id)
    {
        $member = Member::findOrFail($id);
        $indexQuery = $this->memberIndexQuery($request);

        if ($member->credits()->sum('amount') > 0) {
            return redirect()
                ->route('members.index', $indexQuery)
                ->with('error', "This member still has an unpaid credit balance and can't be archived.");
        }

        if (! $member->is_archived) {
            $member->forceFill([
                'archived_at' => now(),
                'is_active' => false,
            ])->save();
        }

        return redirect()
            ->route('members.index', $indexQuery)
            ->with('success', 'Member archived successfully.');
    }

    public function restore(Request $request, $id)
    {
        $member = Member::archived()->findOrFail($id);

        $member->forceFill([
            'archived_at' => null,
            'is_active' => true,
        ])->save();

        $indexQuery = ['tab' => 'archived'] + $this->memberIndexQuery($request);

        return redirect()
            ->route('members.index', $indexQuery)
            ->with('success', 'Member restored successfully.');
    }

    private function memberIndexQuery(Request $request): array
    {
        $search = trim((string) $request->input('search'));

        return $search !== '' ? ['search' => $search] : [];
    }

    private function storeMemberPhoto(Request $request): ?string
    {
        if (! $request->hasFile('photo')) {
            return null;
        }

        return $request->file('photo')->store('members/photos', 'public');
    }

    private function deleteMemberPhoto(?string $photo): void
    {
        if (! $photo || Str::startsWith($photo, ['data:', 'http://', 'https://', '/', 'storage/'])) {
            return;
        }

        Storage::disk('public')->delete($photo);
    }

    private function normalizeMemberInput(Request $request): void
    {
        $normalizeName = function ($value) {
            $value = trim((string) $value);
            $value = preg_replace('/\s+/u', ' ', $value);
            return $value;
        };

        $phone = trim((string) $request->input('phone', ''));
        $normalizedPhone = null;
        if ($phone !== '') {
            $digits = preg_replace('/\D+/', '', $phone);
            if (str_starts_with($digits, '63')) {
                $digits = substr($digits, 2);
            } elseif (str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            }
            $normalizedPhone = $digits !== '' ? '+63' . $digits : null;
        }

        $request->merge([
            'first_name' => $normalizeName($request->input('first_name')),
            'last_name'  => $normalizeName($request->input('last_name')),
            'phone'      => $normalizedPhone,
        ]);
    }

    public function destroy($id)
    {
        $member = Member::findOrFail($id);

        $this->deleteMemberPhoto($member->photo);

        $member->delete();

        return redirect()
            ->route('members.index')
            ->with('success', 'Member deleted successfully!');
    }

    public function analytics($id)
    {
        $member = Member::findOrFail($id);

        // Get purchase statistics
        $totalPurchases = $member->total_purchases;
        $purchaseCount = $member->purchase_count;
        $lastPurchaseDate = $member->last_purchase_date;

        // Get all members data for analytics
        $allMembers = Member::active()->with(['purchases' => function ($query) {
            $query->orderBy('purchase_date', 'desc');
        }])->get();
        
        // Calculate member analytics for all members
        $memberAnalytics = [];
        
        foreach ($allMembers as $memberData) {
            $purchases = $memberData->purchases;
            $total = $purchases->sum('amount');
            $count = $purchases->count();
            $average = $count > 0 ? $total / $count : 0;
            
            $memberAnalytics[$memberData->id] = [
                'id' => $memberData->id,
                'first_name' => $memberData->first_name,
                'last_name' => $memberData->last_name,
                'member_number' => $memberData->member_number,
                'total_purchases' => $total,
                'purchase_count' => $count,
                'average_purchase' => $average,
                'last_purchase_date' => $memberData->last_purchase_date,
                'is_active' => $memberData->is_active,
                'purchases' => $purchases->toArray()
            ];
        }

        // Debug logging
        \Log::info('Member Analytics Debug', [
            'member_id' => $id,
            'last_purchase_date_raw' => $lastPurchaseDate,
            'last_purchase_date_string' => $lastPurchaseDate
                ? $lastPurchaseDate->toDateTimeString()
                : 'null',
            'current_time' => now()->toDateTimeString(),
            'timezone' => config('app.timezone'),
            'all_members_count' => count($allMembers),
            'member_analytics_data' => $memberAnalytics
        ]);

        $averagePurchase = $purchaseCount > 0
            ? $totalPurchases / $purchaseCount
            : 0;

        return view('members.analytics', compact(
            'member',
            'totalPurchases',
            'purchaseCount',
            'lastPurchaseDate',
            'averagePurchase',
            'memberAnalytics'
        ));
    }

    public function lookup(Request $request)
    {
        $q = trim((string) $request->query('q'));

        if ($q === '') {
            return response()->json(['found' => false]);
        }

        $cleanQ = ltrim($q, '#');
        $cleanQ = preg_replace('/^MEM/i', '', $cleanQ);
        $paddedQ = str_pad($cleanQ, 5, '0', STR_PAD_LEFT);
        $memberNumber = 'MEM' . strtoupper($paddedQ);

        if ($request->boolean('exact')) {
            // A scan only counts as a member when it is the card/ID itself,
            // never a partial or name-based match.
            $member = Member::active()
                ->where(function ($query) use ($q, $memberNumber, $paddedQ) {
                    $query->where('member_number', $q)
                        ->orWhere('member_number', $memberNumber)
                        ->orWhere('member_number', '#' . $paddedQ)
                        ->orWhere('member_number', $paddedQ);
                })
                ->first();
        } else {
            $member = Member::active()
                ->where(function ($query) use ($q, $memberNumber, $paddedQ) {
                    $query->where('member_number', $q)
                        ->orWhere('member_number', $memberNumber)
                        ->orWhere('member_number', '#' . $paddedQ)
                        ->orWhere('member_number', $paddedQ)
                        ->orWhere('member_number', 'LIKE', "%{$paddedQ}%")
                        ->orWhere(
                            DB::raw("CONCAT(first_name, ' ', last_name)"),
                            'LIKE',
                            "%{$q}%"
                        );
                })
                ->orderByRaw("CASE WHEN member_number = ? THEN 0 WHEN member_number = ? THEN 1 ELSE 2 END", [$q, $memberNumber])
                ->first();
        }

        if ($member) {
            if (! $member->is_active) {
                AuditLogger::inactiveMemberBlocked($member, 'lookup');

                return response()->json([
                    'found' => true,
                    'is_active' => false,
                    'member' => $member,
                    'message' => 'This member is inactive',
                ], 403);
            }

            return response()->json([
                'found' => true,
                'is_active' => true,
                'member' => $member,
            ]);
        }

        return response()->json([
            'found' => false
        ]);
    }

    public function analyticsIndex()
    {
        // Get all members data for analytics
        $allMembers = Member::active()->with(['purchases' => function ($query) {
            $query->orderBy('purchase_date', 'desc');
        }])->get();
        
        // Calculate member analytics for all members
        $memberAnalytics = [];
        foreach ($allMembers as $memberData) {
            $purchases = $memberData->purchases;
            $total = $purchases->sum('amount');
            $count = $purchases->count();
            $average = $count > 0 ? $total / $count : 0;
            
            $memberAnalytics[$memberData->id] = [
                'id' => $memberData->id,
                'first_name' => $memberData->first_name,
                'last_name' => $memberData->last_name,
                'member_number' => $memberData->member_number,
                'total_purchases' => $total,
                'purchase_count' => $count,
                'average_purchase' => $average,
                'last_purchase_date' => $memberData->last_purchase_date,
                'is_active' => $memberData->is_active,
                'purchases' => $purchases->toArray()
            ];
        }
        
        return view('members.analytics', compact('memberAnalytics'));
    }

    public function memberAnalytics($id)
    {
        $member = Member::findOrFail($id);

        // Fetch the member's purchase line items in a single query.
        // There is no order column, but every item from the same
        // checkout is inserted within a single request, so the rows share
        // the exact same created_at timestamp - that is the grouping key.
        $purchases = $member->purchases()
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        $transactions = $purchases
            ->groupBy(function ($purchase) {
                return $purchase->created_at->format('Y-m-d H:i:s');
            })
            ->map(function ($group) {
                $items = $group->sortBy('id')->values();

                return [
                    'datetime'   => $group->first()->created_at->format('Y-m-d H:i:s'),
                    'item_count' => $items->sum(function ($item) {
                        return (int) $item->quantity;
                    }) ?: $items->count(),
                    'total'      => (float) $group->sum('amount'),
                    'items'      => $items->map(function ($item) {
                        $quantity = (int) $item->quantity;

                        return [
                            'product_name' => $item->product_name,
                            'price'        => (float) $item->amount / max(1, $quantity),
                            'quantity'     => $quantity ?: 1,
                            'amount'       => (float) $item->amount,
                        ];
                    }),
                ];
            })
            ->sortByDesc('datetime')
            ->values();

        return view('members.member-analytics', compact('member', 'transactions'));
    }

    public function card($id)
    {
        $member = Member::findOrFail($id);
        return view('members.card', compact('member'));
    }

    public function downloadCard($id)
    {
        $member = Member::findOrFail($id);

        $filename = 'member-card-'
            .preg_replace('/[^A-Za-z0-9._-]/', '-', $member->member_number)
            .'.svg';

        return response()
            ->view('members.card-svg', compact('member'))
            ->header('Content-Type', 'image/svg+xml; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}