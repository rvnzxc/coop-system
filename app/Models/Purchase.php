<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'member_id',
        'member_number',
        'customer_type',
        'payment_method',
        'amount',
        'quantity',
        'product_name',
        'purchase_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'purchase_date' => 'date',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    // ── Scopes ──────────────────────────────────────────────

    public function scopeMemberSales(Builder $query): Builder
    {
        return $query->where('customer_type', 'member');
    }

    public function scopeNonMemberSales(Builder $query): Builder
    {
        return $query->where('customer_type', 'non_member');
    }

    public function scopeForPeriod(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('purchase_date', [$startDate, $endDate]);
    }
}
