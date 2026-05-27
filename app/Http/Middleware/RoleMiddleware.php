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
        // Check if user is authenticated
        if (!Auth::check()) {
            // Redirect to login page with session
            session(['url.intended' => $request->fullUrl()]);
            return redirect('/login');
        }

        // Check if user has required role
        if (Auth::user()->role !== $role) {
            // Admin can access everything
            if (Auth::user()->role === 'admin') {
                return $next($request);
            }
            
            // Cashier trying to access admin area
            if ($role === 'admin' && Auth::user()->role === 'cashier') {
                abort(403, 'Access denied. Admin access required.');
            }
            
            // Cashier trying to access cashier area - allow
            if ($role === 'cashier' && Auth::user()->role === 'cashier') {
                return $next($request);
            }
        }

        return $next($request);
    }
}
