<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Member extends Model
{
    protected $fillable = [
        'first_name',
        'last_name', 
        'email',
        'phone',
        'photo',
        'total_purchases',
        'purchase_count',
        'last_purchase_date',
        'is_active',
        'archived_at',
        'member_number'
    ];

    protected $casts = [
        'total_purchases' => 'decimal:2',
        'last_purchase_date' => 'datetime',
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getIsArchivedAttribute(): bool
    {
        return $this->archived_at !== null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        $photo = trim((string) $this->photo);

        if ($photo === '') {
            return null;
        }

        if (Str::startsWith($photo, 'data:')) {
            return $photo;
        }

        if (Str::startsWith($photo, ['http://', 'https://'])) {
            return $photo;
        }

        $path = (string) (parse_url($photo, PHP_URL_PATH) ?? $photo);
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $url = Storage::disk('public')->url($path);

        if ($this->updated_at) {
            return $url.(Str::contains($url, '?') ? '&' : '?').'v='.$this->updated_at->getTimestamp();
        }

        return $url;
    }

    public function updatePurchaseStats($amount)
    {
        $this->total_purchases += $amount;
        $this->purchase_count += 1;
        $this->last_purchase_date = now();
        $this->save();
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function credits()
    {
        return $this->hasMany(Credit::class);
    }
}
