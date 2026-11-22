<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'cashier_id',
        'member_id',
        'member_number',
        'customer_type',
        'payment_method',
        'total',
        'amount_received',
        'change_amount',
        'status',
        'voided_by',
        'void_reason',
        'voided_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeVoided(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VOIDED);
    }

    public function scopeForCashier(Builder $query, int $cashierId): Builder
    {
        return $query->where('cashier_id', $cashierId);
    }

    public function getCashierNameAttribute(): string
    {
        return $this->cashier?->name ?: 'Unknown';
    }

    public function getMemberNameAttribute(): string
    {
        if ($this->member) {
            return $this->member->full_name;
        }

        return $this->member_id ? 'Unknown member' : 'Non-member';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === self::STATUS_VOIDED ? 'Voided' : 'Completed';
    }

    /**
     * Compact payload used by the transaction tables and their detail modal.
     */
    public function toTransactionPayload(): array
    {
        $this->loadMissing(['cashier', 'member', 'items', 'voidedBy']);

        return [
            'id' => $this->id,
            'cashier' => $this->cashier_name,
            'member' => $this->member_name,
            'member_number' => $this->member_number ?: $this->member?->member_number,
            'payment_method' => $this->payment_method,
            'total' => (float) $this->total,
            'amount_received' => $this->amount_received !== null ? (float) $this->amount_received : null,
            'change_amount' => $this->change_amount !== null ? (float) $this->change_amount : null,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'created_at' => $this->created_at?->format('M d, Y g:i A'),
            'void_reason' => $this->void_reason,
            'voided_by' => $this->voidedBy?->name,
            'items' => $this->items->map(function (SaleItem $item) {
                return [
                    'product_name' => $item->product_name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                ];
            })->values()->all(),
        ];
    }
}
