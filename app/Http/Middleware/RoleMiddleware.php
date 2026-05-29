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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!Auth::check()) {
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
            abort(403, 'Access denied. Admin access required.');
        }

        return $next($request);
    }
}
