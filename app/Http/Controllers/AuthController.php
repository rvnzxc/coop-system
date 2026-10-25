<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'nullable|string',
            'email' => 'nullable|string',
            'login' => 'nullable|string',
            'password' => 'required|string',
        ]);

        $identifier = trim((string) (
            $request->input('username')
            ?? $request->input('email')
            ?? $request->input('login')
        ));

        if ($identifier === '') {
            return back()->withErrors([
                'username' => 'The username field is required.',
                'email' => 'The username field is required.',
            ])->onlyInput('username', 'email');
        }

        $normalized = mb_strtolower($identifier);

        $user = User::where(function ($query) use ($normalized) {
            $query->whereRaw('LOWER(username) = ?', [$normalized])
                ->orWhereRaw('LOWER(email) = ?', [$normalized]);
        })->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors([
                'username' => 'The provided credentials do not match our records.',
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('username', 'email');
        }

        if (! $user->is_active) {
            return back()->withErrors([
                'username' => 'This account has been deactivated. Please contact an administrator.',
                'email' => 'This account has been deactivated. Please contact an administrator.',
            ])->onlyInput('username', 'email');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($user->role === 'admin') {
            return redirect('/analytics');
        }

        return redirect('/pos');
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
