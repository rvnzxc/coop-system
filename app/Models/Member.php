<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = [
        'first_name',
        'last_name', 
        'email',
        'phone',
        'total_purchases',
        'purchase_count',
        'last_purchase_date',
        'is_active',
        'member_number'
    ];

    protected $casts = [
        'total_purchases' => 'decimal:2',
        'last_purchase_date' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function updatePurchaseStats($amount)
    {
        $this->total_purchases += $amount;
        $this->purchase_count += 1;
        $this->last_purchase_date = now();
        $this->save();
    }
}
