<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * Reject disabled or missing accounts on every authenticated request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check() || $request->is('login', 'logout')) {
            return $next($request);
        }

        $sessionUser = Auth::user();
        $user = User::query()->find($sessionUser->getAuthIdentifier());

        if ($user && $user->is_active) {
            return $next($request);
        }

        $reason = $user ? 'account_disabled' : 'account_missing';
        $message = $user
            ? 'Your account has been disabled by an administrator.'
            : 'Your account is no longer available. Contact an administrator.';

        AuditLogger::log(
            AuditLog::ACTION_ACCOUNT_FORCED_LOGOUT,
            'user',
            $user?->id ?? $sessionUser->getAuthIdentifier(),
            [
                'username' => $user?->username ?? $sessionUser->username,
                'reason' => $reason,
            ],
            $user?->id
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'reason' => $reason,
                'code' => $reason,
            ], 401);
        }

        return redirect()
            ->route('login', ['account_disabled' => 1])
            ->withErrors(['username' => $message]);
    }
}
