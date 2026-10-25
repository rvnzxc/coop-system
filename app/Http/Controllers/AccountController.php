<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureAdmin();

        $search = trim((string) $request->query('search', ''));

        $cashiers = User::query()
            ->where('role', 'cashier')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';

                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('username', 'like', $like);
                });
            })
            ->orderBy('name')
            ->get();

        return view('accounts.index', [
            'cashiers' => $cashiers,
            'search' => $search,
            'currentUser' => $request->user(),
        ]);
    }

    public function storeCashier(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => $this->usernameRules(),
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => null,
            'password' => $validated['password'],
            'role' => 'cashier',
            'is_active' => true,
        ]);

        AuditLogger::log(AuditLog::ACTION_ACCOUNT_CREATED, 'user', $user->id, [
            'name' => $user->name,
            'username' => $user->username,
            'role' => $user->role,
        ]);

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Cashier account created successfully.');
    }

    public function updateCashier(Request $request, User $user)
    {
        $this->ensureAdmin();
        $this->ensureCashier($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => $this->usernameRules($user),
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'];

        // A blank password keeps the existing one. Hash casts on the model
        // handle the actual hashing; the plain value is never stored.
        $passwordChanged = ! blank($validated['password'] ?? null);

        if ($passwordChanged) {
            $user->password = $validated['password'];
        }

        $user->save();

        AuditLogger::log(AuditLog::ACTION_ACCOUNT_UPDATED, 'user', $user->id, [
            'name' => $user->name,
            'username' => $user->username,
            'password_changed' => $passwordChanged,
        ]);

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Cashier account updated successfully.');
    }

    public function resetCashierPassword(Request $request, User $user)
    {
        $this->ensureAdmin();
        $this->ensureCashier($user);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = $validated['password'];
        $user->save();

        AuditLogger::log(AuditLog::ACTION_ACCOUNT_PASSWORD_RESET, 'user', $user->id, [
            'name' => $user->name,
            'username' => $user->username,
        ]);

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Password reset for '.$user->name.'.');
    }

    public function toggleCashierStatus(Request $request, User $user)
    {
        $this->ensureAdmin();
        $this->ensureCashier($user);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user->is_active = (bool) $validated['is_active'];
        $user->save();

        AuditLogger::log(AuditLog::ACTION_ACCOUNT_STATUS_CHANGED, 'user', $user->id, [
            'name' => $user->name,
            'username' => $user->username,
            'is_active' => $user->is_active,
        ]);

        return redirect()
            ->route('accounts.index')
            ->with('success', $user->name.'\'s account has been '.($user->is_active ? 'enabled' : 'disabled').'.');
    }

    public function updateSelf(Request $request)
    {
        $this->ensureAdmin();

        $user = $request->user();
        $previousUsername = $user->username;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => $this->usernameRules($user),
            'current_password' => [
                'nullable',
                'required_with:password',
                function (string $attribute, $value, Closure $fail) use ($request) {
                    if (blank($value)) {
                        return;
                    }

                    if (! $this->passwordMatches($request->user(), (string) $value)) {
                        $fail('The current password is incorrect.');
                    }
                },
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $usernameChanged = mb_strtolower((string) $previousUsername) !== mb_strtolower($validated['username']);
        $passwordChanged = ! blank($validated['password'] ?? null);

        $user->name = $validated['name'];
        $user->username = $validated['username'];

        if ($passwordChanged) {
            $user->password = $validated['password'];
        }

        $user->save();

        AuditLogger::log(AuditLog::ACTION_ACCOUNT_UPDATED, 'user', $user->id, [
            'name' => $user->name,
            'username' => $user->username,
            'username_changed' => $usernameChanged,
            'password_changed' => $passwordChanged,
            'self' => true,
        ]);

        // Keep the current session authenticated after changing credentials.
        Auth::setUser($user->fresh());

        if ($usernameChanged || $passwordChanged) {
            $request->session()->regenerate();
        }

        if ($passwordChanged) {
            $message = 'Your account was updated. You are still signed in with your new password.';
        } elseif ($usernameChanged) {
            $message = 'Your account was updated. You are still signed in with your new username.';
        } else {
            $message = 'Your account was updated.';
        }

        return redirect()
            ->route('accounts.index')
            ->with('success', $message);
    }

    /**
     * Usernames are unique case-insensitively, so the check is done with
     * LOWER() rather than relying on the database collation alone.
     *
     * @return array<int, mixed>
     */
    private function usernameRules(?User $ignore = null): array
    {
        return [
            'required',
            'string',
            'min:3',
            'max:64',
            'regex:/^[A-Za-z0-9._-]+$/',
            function (string $attribute, $value, Closure $fail) use ($ignore) {
                $query = User::query()->whereRaw('LOWER(username) = ?', [mb_strtolower((string) $value)]);

                if ($ignore) {
                    $query->whereKeyNot($ignore->getKey());
                }

                if ($query->exists()) {
                    $fail('This username is already taken.');
                }
            },
        ];
    }

    private function passwordMatches(User $user, string $password): bool
    {
        $stored = (string) $user->getAuthPassword();

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

        return ! $isHashed && hash_equals($stored, $password);
    }

    private function ensureAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->role === 'admin', 403, 'Access denied. Admin access required.');
    }

    private function ensureCashier(User $user): void
    {
        // Admin accounts are not managed through the cashier endpoints, which
        // also prevents an admin from being demoted through them.
        abort_unless($user->role === 'cashier', 404);
    }
}
