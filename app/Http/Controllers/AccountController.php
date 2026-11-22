<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = User::orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        $totalAccounts = $accounts->count();
        $cashierCount = $accounts->where('role', 'cashier')->count();
        $activeCount = $accounts->where('is_active', true)->count();

        return view('accounts.index', compact(
            'accounts',
            'totalAccounts',
            'cashierCount',
            'activeCount'
        ));
    }

    public function create()
    {
        return view('accounts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->accountRules());

        $user = User::create([
            'name' => $validated['name'],
            'username' => trim($validated['username']),
            'email' => filled($validated['email'] ?? null) ? trim($validated['email']) : null,
            'password' => $validated['password'],
            'role' => 'cashier',
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        AuditLogger::log(AuditLog::ACTION_ACCOUNT_CREATED, 'user', $user->id, [
            'username' => $user->username,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
        ]);

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Cashier account "'.$user->username.'" created successfully.');
    }

    public function edit(User $user)
    {
        if ($user->is(Auth::user())) {
            return redirect()->route('accounts.me');
        }

        abort_if($user->role !== 'cashier', 403, 'Administrator accounts can only be managed by their owner.');

        return view('accounts.edit', ['account' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $isSelf = $user->is(Auth::user());

        abort_if(! $isSelf && $user->role !== 'cashier', 403, 'Administrator accounts can only be managed by their owner.');

        if ($isSelf && $request->has('is_active') && ! $request->boolean('is_active')) {
            $message = 'You cannot disable your own account.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $rules = $this->accountRules($user, false);
        $rules['current_password'] = ['nullable', 'required_with:password', 'current_password'];
        $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];

        if (! $isSelf) {
            unset($rules['current_password']);
        }

        $validated = $request->validate($rules);
        $wasActive = $user->is_active;

        $user->name = $validated['name'];
        $user->username = trim($validated['username']);

        if (array_key_exists('email', $validated)) {
            $user->email = filled($validated['email'] ?? null) ? trim($validated['email']) : null;
        }

        if (! $isSelf && $request->has('is_active')) {
            $user->is_active = $request->boolean('is_active');

            if (! $user->is_active) {
                $user->remember_token = null;
            }
        }

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        $sessionsInvalidated = ! $user->is_active;

        if ($sessionsInvalidated) {
            $this->invalidateUserSessions($user);
        }

        AuditLogger::log(AuditLog::ACTION_ACCOUNT_UPDATED, 'user', $user->id, [
            'username' => $user->username,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
            'previous_is_active' => $wasActive,
            'sessions_invalidated' => $sessionsInvalidated,
            'password_changed' => filled($validated['password'] ?? null),
        ]);

        if ($isSelf) {
            return redirect()
                ->route('accounts.me')
                ->with('success', 'Your account has been updated.');
        }

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account "'.$user->username.'" updated successfully.');
    }

    public function password(User $user)
    {
        if ($user->is(Auth::user())) {
            return redirect()->route('accounts.me');
        }

        abort_if($user->role !== 'cashier', 403, 'Administrator accounts can only be managed by their owner.');

        return view('accounts.password', ['account' => $user]);
    }

    public function resetPassword(Request $request, User $user)
    {
        if ($user->is(Auth::user())) {
            return redirect()->route('accounts.me');
        }

        abort_if($user->role !== 'cashier', 403, 'Administrator accounts can only be managed by their owner.');

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = $validated['password'];
        $user->save();

        AuditLogger::log(AuditLog::ACTION_ACCOUNT_PASSWORD_RESET, 'user', $user->id, [
            'username' => $user->username,
        ]);

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Password for "'.$user->username.'" has been reset.');
    }

    public function toggleStatus(Request $request, User $user)
    {
        if ($user->is(Auth::user())) {
            $message = 'You cannot disable your own account.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        if ($user->role !== 'cashier') {
            $message = 'Administrator accounts cannot be deactivated from this page.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $wasActive = $user->is_active;
        $user->is_active = $request->has('is_active')
            ? $request->boolean('is_active')
            : ! $wasActive;

        if (! $user->is_active) {
            $user->remember_token = null;
        }

        $user->save();

        $sessionsInvalidated = ! $user->is_active;

        if ($sessionsInvalidated) {
            $this->invalidateUserSessions($user);
        }

        AuditLogger::log(AuditLog::ACTION_ACCOUNT_STATUS_CHANGED, 'user', $user->id, [
            'username' => $user->username,
            'is_active' => (bool) $user->is_active,
            'previous_is_active' => $wasActive,
            'sessions_invalidated' => $sessionsInvalidated,
        ]);

        $message = $user->is_active
            ? 'Account re-enabled. The cashier can log in again.'
            : 'Account disabled. Active sessions were ended.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => (bool) $user->is_active,
                'message' => $message,
            ]);
        }

        return redirect()
            ->route('accounts.index')
            ->with('success', $message);
    }

    public function me()
    {
        return view('accounts.me', ['account' => Auth::user()]);
    }

    public function updateMe(Request $request)
    {
        $user = Auth::user();

        $rules = $this->accountRules($user, false);
        $rules['current_password'] = ['nullable', 'required_with:password', 'current_password'];
        $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];

        $validated = $request->validate($rules);

        $user->name = $validated['name'];
        $user->username = trim($validated['username']);

        if (array_key_exists('email', $validated)) {
            $user->email = filled($validated['email'] ?? null) ? trim($validated['email']) : null;
        }

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        AuditLogger::log(AuditLog::ACTION_ACCOUNT_UPDATED, 'user', $user->id, [
            'username' => $user->username,
            'role' => $user->role,
            'password_changed' => filled($validated['password'] ?? null),
        ]);

        $redirect = $request->input('_redirect') === 'index'
            ? redirect()->route('accounts.index')
            : redirect()->route('accounts.me');

        return $redirect->with('success', 'Your account has been updated.');
    }

    private function invalidateUserSessions(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->delete();
    }

    private function accountRules(?User $user = null, bool $passwordRequired = true): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:255',
                $this->uniqueIdentifierRule($user, 'username'),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                $this->uniqueIdentifierRule($user, 'email'),
            ],
        ];

        $rules['password'] = $passwordRequired
            ? ['required', 'string', 'min:8', 'confirmed']
            : ['nullable', 'string', 'min:8', 'confirmed'];

        return $rules;
    }

    private function uniqueIdentifierRule(?User $user, string $column): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($user, $column): void {
            if (blank($value)) {
                return;
            }

            $identifier = mb_strtolower(trim((string) $value));

            $query = User::where(function ($query) use ($identifier) {
                $query->whereRaw('LOWER(username) = ?', [$identifier])
                    ->orWhereRaw('LOWER(email) = ?', [$identifier]);
            });

            if ($user) {
                $query->where('id', '!=', $user->id);
            }

            if ($query->exists()) {
                $label = $column === 'email' ? 'email address' : 'username';
                $fail('That '.$label.' is already in use.');
            }
        };
    }
}
