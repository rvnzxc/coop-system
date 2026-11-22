<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        if (! Auth::check()) {
            session(['url.intended' => $request->fullUrl()]);

            return redirect('/login');
        }

        $userRole = Auth::user()->role;

        // Admin can access everything
        if ($userRole === 'admin') {
            return $next($request);
        }

        // Non-admin trying to access admin area
        if ($role === 'admin') {
            if ($request->isMethodSafe() && ! $request->expectsJson()) {
                return redirect()
                    ->route('shop.index')
                    ->with('error', "You don't have permission to view that page.");
            }

            abort(403, 'Access denied. Admin access required.');
        }

        return $next($request);
    }
}
