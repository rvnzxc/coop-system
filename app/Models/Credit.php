<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $fillable = [
        'member_id',
        'amount',
        'sale_reference',
        'notes',
        'items_snapshot',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'items_snapshot' => 'array',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
