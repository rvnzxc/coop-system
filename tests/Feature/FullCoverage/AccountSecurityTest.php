<?php

namespace Tests\Feature\FullCoverage;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_cashier_with_hashed_password_and_forced_role(): void
    {
        $this->actingAs($this->admin())->post(route('accounts.store'), [
            'name' => 'New Cashier',
            'username' => 'new.cashier',
            'email' => 'new.cashier@example.com',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
            'role' => 'admin',
        ])->assertRedirect(route('accounts.index'));

        $cashier = User::where('username', 'new.cashier')->sole();
        $this->assertSame('cashier', $cashier->role);
        $this->assertNotSame('secret12345', $cashier->password);
        $this->assertTrue(Hash::check('secret12345', $cashier->password));
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_ACCOUNT_CREATED,
            'target_id' => $cashier->id,
        ]);
    }

    public function test_account_validation_rejects_duplicate_identifiers_and_short_password(): void
    {
        $existing = $this->cashier(['username' => 'existing.user', 'email' => 'existing@example.com']);
        $this->actingAs($this->admin());

        $this->post(route('accounts.store'), [
            'name' => 'Duplicate',
            'username' => $existing->username,
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ])->assertSessionHasErrors('username');

        $this->post(route('accounts.store'), [
            'name' => 'Short',
            'username' => 'short.user',
            'email' => $existing->email,
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ])->assertSessionHasErrors('email');

        $this->post(route('accounts.store'), [
            'name' => 'Weak',
            'username' => 'weak.user',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }

    public function test_validation_errors_do_not_leak_stack_traces(): void
    {
        $response = $this->actingAs($this->admin())->post(route('accounts.store'), [
            'name' => '',
            'username' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'username', 'password']);
        $content = (string) $response->getContent();
        $this->assertStringNotContainsString('Stack trace', $content);
        $this->assertStringNotContainsString('Illuminate\\', $content);
    }

    public function test_admin_can_deactivate_cashier_and_action_is_logged(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($this->admin())->postJson(route('accounts.status', $cashier->id), [
            'is_active' => false,
        ])->assertOk()->assertJsonPath('is_active', false);

        $this->assertFalse($cashier->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_ACCOUNT_STATUS_CHANGED,
            'target_id' => $cashier->id,
        ]);
    }

    public function test_csrf_protection_requires_browser_verification(): void
    {
        $this->markTestIncomplete('Laravel feature tests bypass VerifyCsrfToken; verify rejected POST requests in a browser.');
    }
}
