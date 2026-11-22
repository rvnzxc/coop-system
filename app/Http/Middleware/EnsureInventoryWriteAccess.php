<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInventoryWriteAccess
{
    /**
     * Only administrators may change inventory records. Blocked cashier
     * attempts are recorded in the activity log before returning a 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isAdmin()) {
            return $next($request);
        }

        $itemId = $request->route('id');
        $attemptedAction = match ($request->route()?->getName()) {
            'inventory.store' => 'add an inventory item',
            'inventory.update' => 'update inventory item'.($itemId !== null ? ' #'.$itemId : ''),
            'inventory.destroy' => 'delete inventory item'.($itemId !== null ? ' #'.$itemId : ''),
            'categories.store' => 'add a category',
            'categories.destroy' => 'delete a category',
            default => 'modify inventory',
        };

        AuditLogger::log(
            AuditLog::ACTION_PERMISSION_DENIED,
            'inventory',
            $itemId !== null ? (int) $itemId : null,
            [
                'username' => $request->user()?->username,
                'attempted_action' => $attemptedAction,
            ]
        );

        $message = "You don't have permission to modify inventory.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        abort(403, $message);
    }
}
