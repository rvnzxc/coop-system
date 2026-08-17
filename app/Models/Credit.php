<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $fillable = [
        'member_id',
        'amount',
        'amount_paid',
        'status',
        'sale_reference',
        'notes',
        'items_snapshot',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'items_snapshot' => 'array',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function payments()
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function getBalanceAttribute()
    {
        return round($this->amount - $this->amount_paid, 2);
    }

    public function markPaidIfFull()
    {
        if ($this->amount_paid >= $this->amount) {
            $this->status = 'paid';
        } elseif ($this->amount_paid > 0) {
            $this->status = 'partial';
        }
        $this->save();
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', 'unpaid');
    }

    public function scopePartial(Builder $query): Builder
    {
        return $query->where('status', 'partial');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', ['unpaid', 'partial']);
    }
}
