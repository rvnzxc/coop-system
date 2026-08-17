<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreditController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $query = Credit::with('member')->orderBy('created_at', 'desc');

        if ($status && in_array($status, ['unpaid', 'partial', 'paid'])) {
            $query->where('status', $status);
        }

        $credits = $query->get();

        $totalOutstanding = Credit::outstanding()->sum(DB::raw('amount - amount_paid'));
        $unpaidCount = Credit::unpaid()->count();
        $partialCount = Credit::partial()->count();
        $membersWithDebt = Credit::outstanding()->distinct('member_id')->count('member_id');

        return view('credits.index', compact(
            'credits',
            'totalOutstanding',
            'unpaidCount',
            'partialCount',
            'membersWithDebt',
            'status'
        ));
    }

    public function show(Credit $credit)
    {
        $credit->load('member', 'payments.receiver');

        return view('credits.show', compact('credit'));
    }

    public function pay(Request $request, Credit $credit)
    {
        $request->validate([
            'amount_paid' => 'required|numeric|min:0.01|max:' . $credit->balance,
        ]);

        DB::beginTransaction();

        try {
            $payment = CreditPayment::create([
                'credit_id'   => $credit->id,
                'amount_paid' => $request->amount_paid,
                'paid_at'     => now(),
                'received_by' => Auth::id(),
            ]);

            $credit->amount_paid = round($credit->amount_paid + $request->amount_paid, 2);
            $credit->markPaidIfFull();

            DB::commit();

            $redirect = $request->query('redirect');
            if ($redirect === 'pos') {
                return redirect()->route('shop.index')
                    ->with('success', 'Credit payment of ₱' . number_format($request->amount_paid, 2) . ' recorded.');
            }

            return redirect()->route('credits.show', $credit->id)
                ->with('success', 'Payment of ₱' . number_format($request->amount_paid, 2) . ' recorded.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['amount_paid' => 'Failed to record payment: ' . $e->getMessage()]);
        }
    }

    public function balance($memberId)
    {
        $member = Member::findOrFail($memberId);

        return response()->json([
            'outstanding_balance' => $member->outstanding_credit_balance,
            'has_unpaid_credit'   => $member->has_unpaid_credit,
        ]);
    }
}
