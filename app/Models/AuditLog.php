<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_SALE_COMPLETED = 'sale_completed';
    public const ACTION_SALE_VOIDED = 'sale_voided';
    public const ACTION_ACCOUNT_CREATED = 'account_created';
    public const ACTION_ACCOUNT_UPDATED = 'account_updated';
    public const ACTION_ACCOUNT_PASSWORD_RESET = 'account_password_reset';
    public const ACTION_ACCOUNT_STATUS_CHANGED = 'account_status_changed';

    public const ACTIONS = [
        self::ACTION_LOGIN => 'Login',
        self::ACTION_LOGOUT => 'Logout',
        self::ACTION_SALE_COMPLETED => 'Sale completed',
        self::ACTION_SALE_VOIDED => 'Sale voided',
        self::ACTION_ACCOUNT_CREATED => 'Account created',
        self::ACTION_ACCOUNT_UPDATED => 'Account updated',
        self::ACTION_ACCOUNT_PASSWORD_RESET => 'Password reset',
        self::ACTION_ACCOUNT_STATUS_CHANGED => 'Account status changed',
    ];

    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? ucfirst(str_replace('_', ' ', (string) $this->action));
    }
}
