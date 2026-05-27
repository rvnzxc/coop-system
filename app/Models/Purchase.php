<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = ['member_id', 'member_number', 'amount', 'quantity', 'product_name', 'purchase_date'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
