<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReportAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && in_array($user->role, ['admin', 'cashier'], true)) {
            return $next($request);
        }

        AuditLogger::log(
            AuditLog::ACTION_PERMISSION_DENIED,
            'report',
            null,
            [
                'username' => $user?->username,
                'attempted_action' => 'download a report',
            ]
        );

        $message = "You don't have permission to download reports.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        abort(403, $message);
    }
}
