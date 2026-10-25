<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
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
     * Handle login request.
     *
     * The login field accepts a username (case-insensitive) or an email
     * address so existing accounts keep working while new cashier accounts
     * are created with a username only.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        $login = trim((string) $request->input('username'));
        $password = (string) $request->input('password');

        $user = User::query()
            ->where(function ($query) use ($login) {
                $query->whereRaw('LOWER(username) = ?', [mb_strtolower($login)])
                    ->orWhere('email', $login);
            })
            ->first();

        $isLegacyPlain = false;

        if ($user && $this->passwordMatches($user, $password, $isLegacyPlain)) {
            if (! $user->is_active) {
                return back()->withErrors([
                    'username' => 'This account has been disabled. Please contact an administrator.',
                ])->onlyInput('username');
            }

            $needsRehash = false;

            if (! $isLegacyPlain) {
                try {
                    $needsRehash = Hash::needsRehash((string) $user->getAuthPassword());
                } catch (\Throwable $e) {
                    $needsRehash = false;
                }
            }

            // Upgrade plain-text or outdated hashes on the next successful login.
            if ($isLegacyPlain || $needsRehash) {
                $user->password = $password;
                $user->save();
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            AuditLogger::log(AuditLog::ACTION_LOGIN, 'user', $user->id, [
                'username' => $user->username,
                'role' => $user->role,
            ]);

            // Redirect based on role
            if ($user->role === 'admin') {
                return redirect('/analytics');
            }

            return redirect('/pos');
        }

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->onlyInput('username');
    }

    /**
     * Check a password against the stored value, accepting legacy plain-text
     * values so they can be rehashed after a successful login.
     */
    private function passwordMatches(User $user, string $password, bool &$isLegacyPlain = false): bool
    {
        $stored = (string) $user->getAuthPassword();
        $isLegacyPlain = false;

        if ($stored === '') {
            return false;
        }

        try {
            if (Hash::check($password, $stored)) {
                return true;
            }
        } catch (\Throwable $e) {
            // Fall through to the legacy plain-text comparison.
        }

        $isHashed = (bool) preg_match('/^\$(2[aby]|argon2(id|i)?)\$/', $stored);

        if (! $isHashed && hash_equals($stored, $password)) {
            $isLegacyPlain = true;

            return true;
        }

        return false;
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            AuditLogger::log(AuditLog::ACTION_LOGOUT, 'user', $user->id, [
                'username' => $user->username,
            ], $user->id);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
