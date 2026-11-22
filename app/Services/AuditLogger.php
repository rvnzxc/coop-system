<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Member;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Record an audit entry. Password-like values are stripped defensively so
     * they can never end up in the log even if a caller passes them by mistake.
     */
    public static function log(
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        array $details = [],
        ?int $userId = null
    ): void {
        AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => static::sanitize($details),
        ]);
    }

    public static function inactiveMemberBlocked(Member $member, string $source): void
    {
        $user = Auth::user();

        self::log(AuditLog::ACTION_INACTIVE_MEMBER_BLOCKED, 'member', $member->id, [
            'username' => $user?->username,
            'member_id' => $member->id,
            'member_number' => $member->member_number,
            'member_name' => $member->full_name,
            'source' => $source,
        ]);
    }

    private static function sanitize(array $details): array
    {
        foreach ($details as $key => $value) {
            if (is_array($value)) {
                $details[$key] = static::sanitize($value);
                continue;
            }

            if (is_string($key) && preg_match('/(password|secret|token)/i', $key)) {
                unset($details[$key]);
            }
        }

        return $details;
    }
}
