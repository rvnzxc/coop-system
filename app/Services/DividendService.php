<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Purchase;
use Illuminate\Support\Collection;

class DividendService
{
    /**
     * Get aggregated sales totals for a given date range, split by customer type.
     *
     * @param  string  $startDate  YYYY-MM-DD
     * @param  string  $endDate    YYYY-MM-DD
     * @return array{member_total: float, non_member_total: float, combined_total: float}
     */
    public function getSalesTotals(string $startDate, string $endDate): array
    {
        $memberTotal = (float) Purchase::memberSales()
            ->forPeriod($startDate, $endDate)
            ->sum('amount');

        $nonMemberTotal = (float) Purchase::nonMemberSales()
            ->forPeriod($startDate, $endDate)
            ->sum('amount');

        return [
            'member_total'     => $memberTotal,
            'non_member_total' => $nonMemberTotal,
            'combined_total'   => $memberTotal + $nonMemberTotal,
        ];
    }

    /**
     * Get per-member purchase totals for a given date range.
     *
     * @param  string  $startDate  YYYY-MM-DD
     * @param  string  $endDate    YYYY-MM-DD
     * @return Collection<int, object{member_id: int, member_number: string, full_name: string, total: float}>
     */
    public function getMemberPurchaseTotals(string $startDate, string $endDate): Collection
    {
        return Purchase::memberSales()
            ->forPeriod($startDate, $endDate)
            ->select('member_id', 'member_number', \DB::raw('SUM(amount) as total'))
            ->groupBy('member_id', 'member_number')
            ->get()
            ->map(function ($record) {
                $member = Member::find($record->member_id);
                return (object) [
                    'member_id'     => $record->member_id,
                    'member_number' => $record->member_number,
                    'full_name'     => $member ? $member->full_name : 'Unknown',
                    'total'         => (float) $record->total,
                ];
            });
    }

    /**
     * Distribute dividends to members based on their share of total member purchases.
     *
     * TODO: Dividend distribution formula not finalized.
     *       The current implementation is a placeholder that splits the non-member
     *       net income pool equally among all active members. Replace this with the
     *       actual cooperative dividend/patronage refund formula when finalized.
     *
     * @param  string  $startDate  YYYY-MM-DD
     * @param  string  $endDate    YYYY-MM-DD
     * @return array{member_total: float, non_member_total: float, dividend_per_member: float, member_count: int}
     */
    public function distribute(string $startDate, string $endDate): array
    {
        $totals = $this->getSalesTotals($startDate, $endDate);

        $activeMembers = Member::where('is_active', true)->count();

        // Placeholder: even split of non-member pool across all active members
        $dividendPerMember = $activeMembers > 0
            ? $totals['non_member_total'] / $activeMembers
            : 0.0;

        return [
            'member_total'       => $totals['member_total'],
            'non_member_total'   => $totals['non_member_total'],
            'member_count'       => $activeMembers,
            'dividend_per_member' => round($dividendPerMember, 2),
        ];
    }
}
