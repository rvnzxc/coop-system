<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ['name'];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'category_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return Str::headline((string) $this->name);
    }
}
