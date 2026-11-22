<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const ACTION_LOGIN = 'login';

    public const ACTION_LOGOUT = 'logout';

    public const ACTION_SALE_COMPLETED = 'sale_completed';

    public const ACTION_ACCOUNT_CREATED = 'account_created';

    public const ACTION_ACCOUNT_UPDATED = 'account_updated';

    public const ACTION_ACCOUNT_PASSWORD_RESET = 'account_password_reset';

    public const ACTION_ACCOUNT_STATUS_CHANGED = 'account_status_changed';

    public const ACTION_ACCOUNT_FORCED_LOGOUT = 'account_forced_logout';

    public const ACTION_PERMISSION_DENIED = 'permission_denied';

    public const ACTION_INACTIVE_MEMBER_BLOCKED = 'inactive_member_blocked';

    public const ACTION_CATEGORY_CREATED = 'category_created';

    public const ACTION_CATEGORY_DELETED = 'category_deleted';

    public const ACTION_REPORT_EXPORTED = 'report_exported';

    public const ACTIONS = [
        self::ACTION_LOGIN => 'Login',
        self::ACTION_LOGOUT => 'Logout',
        self::ACTION_SALE_COMPLETED => 'Sale completed',
        self::ACTION_ACCOUNT_CREATED => 'Account created',
        self::ACTION_ACCOUNT_UPDATED => 'Account updated',
        self::ACTION_ACCOUNT_PASSWORD_RESET => 'Password reset',
        self::ACTION_ACCOUNT_STATUS_CHANGED => 'Account status changed',
        self::ACTION_ACCOUNT_FORCED_LOGOUT => 'Forced logout',
        self::ACTION_PERMISSION_DENIED => 'Permission denied',
        self::ACTION_INACTIVE_MEMBER_BLOCKED => 'Inactive member blocked',
        self::ACTION_CATEGORY_CREATED => 'Category created',
        self::ACTION_CATEGORY_DELETED => 'Category deleted',
        self::ACTION_REPORT_EXPORTED => 'Report exported',
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

    /**
     * Build a human-readable description for the activity log table. This only
     * affects display; the stored details array is never modified.
     */
    public static function formatActivityDetails(string $action, ?array $details): string
    {
        $details ??= [];

        return match ($action) {
            self::ACTION_LOGIN => self::formatLogin($details),
            self::ACTION_LOGOUT => 'Logged out',
            self::ACTION_ACCOUNT_CREATED => self::formatAccountCreated($details),
            self::ACTION_ACCOUNT_UPDATED => self::formatAccountUpdated($details),
            self::ACTION_ACCOUNT_PASSWORD_RESET => self::formatPasswordReset($details),
            self::ACTION_ACCOUNT_STATUS_CHANGED => self::formatStatusChanged($details),
            self::ACTION_ACCOUNT_FORCED_LOGOUT => self::formatForcedLogout($details),
            self::ACTION_PERMISSION_DENIED => self::formatPermissionDenied($details),
            self::ACTION_INACTIVE_MEMBER_BLOCKED => self::formatInactiveMemberBlocked($details),
            self::ACTION_CATEGORY_CREATED => self::formatCategoryCreated($details),
            self::ACTION_CATEGORY_DELETED => self::formatCategoryDeleted($details),
            self::ACTION_REPORT_EXPORTED => self::formatReportExported($details),
            self::ACTION_SALE_COMPLETED => self::formatSaleCompleted($details),
            default => self::formatGeneric($details),
        };
    }

    private static function formatLogin(array $details): string
    {
        $username = self::stringValue($details['username'] ?? null);
        $role = self::stringValue($details['role'] ?? null);

        if ($username === '') {
            return 'Logged in';
        }

        return 'Logged in as '.$username.($role !== '' ? ' ('.ucfirst($role).')' : '');
    }

    private static function formatAccountCreated(array $details): string
    {
        $role = self::stringValue($details['role'] ?? null);
        $roleLabel = $role !== '' ? ucfirst($role) : 'Cashier';

        $text = 'Created '.$roleLabel.' account @'.self::username($details).'.';

        if (array_key_exists('is_active', $details) && ! self::truthy($details['is_active'])) {
            $text .= ' The account is inactive.';
        }

        return $text;
    }

    private static function formatAccountUpdated(array $details): string
    {
        $username = self::username($details);

        $statusChanged = array_key_exists('is_active', $details)
            && array_key_exists('previous_is_active', $details)
            && self::truthy($details['is_active']) !== self::truthy($details['previous_is_active']);

        if ($statusChanged) {
            $text = (self::truthy($details['is_active']) ? 'Reactivated' : 'Deactivated').' account of @'.$username.'.';
        } else {
            $text = 'Updated account @'.$username.'.';
        }

        if (self::truthy($details['password_changed'] ?? false)) {
            $text .= ' Password was changed.';
        }

        if (self::truthy($details['sessions_invalidated'] ?? false)) {
            $text .= ' Active sessions were signed out.';
        }

        return $text;
    }

    private static function formatPasswordReset(array $details): string
    {
        return 'Reset the password for @'.self::username($details).'.';
    }

    private static function formatStatusChanged(array $details): string
    {
        $active = self::truthy($details['is_active'] ?? false);

        $text = ($active ? 'Reactivated' : 'Deactivated').' account of @'.self::username($details).'.';

        if (self::truthy($details['sessions_invalidated'] ?? false)) {
            $text .= ' Active sessions were signed out.';
        }

        return $text;
    }

    private static function formatForcedLogout(array $details): string
    {
        $text = 'Signed out @'.self::username($details);

        return match ($details['reason'] ?? null) {
            'account_disabled' => $text.' because the account was disabled.',
            'account_missing' => $text.' because the account no longer exists.',
            default => $text.'.',
        };
    }

    private static function formatPermissionDenied(array $details): string
    {
        $text = 'Permission denied for @'.self::username($details);
        $attemptedAction = self::stringValue($details['attempted_action'] ?? null);

        if ($attemptedAction !== '') {
            $text .= ' while attempting to '.$attemptedAction;
        }

        return $text.'.';
    }

    private static function formatInactiveMemberBlocked(array $details): string
    {
        $memberId = $details['member_id'] ?? null;
        $memberName = self::stringValue($details['member_name'] ?? null);
        $text = 'Blocked inactive member'.($memberId !== null ? ' #'.$memberId : '');

        if ($memberName !== '') {
            $text .= ' ('.$memberName.')';
        }

        return $text.' for @'.self::username($details).'.';
    }

    private static function formatCategoryCreated(array $details): string
    {
        $name = self::stringValue($details['name'] ?? null);

        return 'Added category'.($name !== '' ? " '{$name}'" : '').' for @'.self::username($details).'.';
    }

    private static function formatCategoryDeleted(array $details): string
    {
        $name = self::stringValue($details['name'] ?? null);

        return 'Deleted category'.($name !== '' ? " '{$name}'" : '').' for @'.self::username($details).'.';
    }

    private static function formatReportExported(array $details): string
    {
        $report = self::stringValue($details['report'] ?? null);
        $format = strtoupper(self::stringValue($details['format'] ?? null));

        $label = $report !== '' ? ucfirst(str_replace('-', ' ', $report)) : 'Report';

        return 'Exported '.$label.($format !== '' ? ' ('.$format.')' : '').' for @'.self::username($details).'.';
    }

    private static function formatSaleCompleted(array $details): string
    {
        $saleId = $details['transaction_id'] ?? null;
        $text = 'Completed sale'.($saleId !== null ? ' #'.$saleId : '').'.';

        $bits = [];

        if (isset($details['item_count'])) {
            $count = (int) $details['item_count'];
            $bits[] = $count.' '.($count === 1 ? 'item' : 'items');
        }

        if (isset($details['total'])) {
            $total = "\u{20B1}".number_format((float) $details['total'], 2);
            $method = self::stringValue($details['payment_method'] ?? null);
            $bits[] = $method !== '' ? $total.' via '.$method : $total;
        }

        if ($bits !== []) {
            $text .= ' '.ucfirst(implode(', ', $bits)).'.';
        }

        if (isset($details['member_id'])) {
            $text .= ' Member #'.$details['member_id'].'.';
        }

        return $text;
    }

    private static function formatGeneric(array $details): string
    {
        $parts = [];

        foreach ($details as $key => $value) {
            $parts[] = self::humanizeKey((string) $key).': '.self::displayValue($value);
        }

        return $parts !== [] ? implode(', ', $parts).'.' : '';
    }

    private static function username(array $details): string
    {
        $username = self::stringValue($details['username'] ?? null);

        return $username !== '' ? $username : 'user';
    }

    private static function stringValue(mixed $value): string
    {
        if ($value === null || is_array($value)) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        return trim((string) $value);
    }

    private static function truthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return $value != 0;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    private static function humanizeKey(string $key): string
    {
        return ucfirst(trim(str_replace('_', ' ', $key)));
    }

    private static function displayValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if ($value === null) {
            return '—';
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn ($item) => self::displayValue($item), $value));
        }

        return (string) $value;
    }
}
