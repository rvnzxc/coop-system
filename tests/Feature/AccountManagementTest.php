<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(string $password = 'adminpass123'): User
    {
        return User::create([
            'name' => 'Test Admin',
            'username' => 'admin',
            'email' => 'admin@test.com',
            'password' => $password,
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createCashier(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test Cashier',
            'username' => 'cashier',
            'email' => 'cashier@test.com',
            'password' => 'cashierpass123',
            'role' => 'cashier',
            'is_active' => true,
        ], $overrides));
    }

    public function test_admin_can_view_accounts_page(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier();

        $response = $this->actingAs($admin)->get(route('accounts.index'));

        $response->assertOk();
        $response->assertSee('Manage cashier accounts and your own administrator profile');
        $response->assertSee($cashier->username);
    }

    public function test_cashier_is_redirected_away_from_account_management(): void
    {
        $cashier = $this->createCashier();

        $this->actingAs($cashier)
            ->get(route('accounts.index'))
            ->assertRedirect(route('shop.index'));

        $this->actingAs($cashier)
            ->get(route('accounts.create'))
            ->assertRedirect(route('shop.index'));
    }

    public function test_admin_can_create_a_cashier_account_with_a_hashed_password(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('accounts.store'), [
            'username' => 'New.Cashier',
            'name' => 'New Cashier',
            'email' => 'new.cashier@test.com',
            'password' => 'secretpass123',
            'password_confirmation' => 'secretpass123',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('accounts.index'));

        $cashier = User::where('username', 'New.Cashier')->firstOrFail();

        $this->assertSame('cashier', $cashier->role);
        $this->assertTrue($cashier->is_active);
        $this->assertNotSame('secretpass123', $cashier->password);
        $this->assertTrue(Hash::check('secretpass123', $cashier->password));
    }

    public function test_usernames_are_unique_case_insensitively(): void
    {
        $admin = $this->createAdmin();
        $this->createCashier(['username' => 'Cashier.One']);

        $response = $this->actingAs($admin)->post(route('accounts.store'), [
            'username' => 'cashier.one',
            'name' => 'Duplicate Cashier',
            'password' => 'secretpass123',
            'password_confirmation' => 'secretpass123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertDatabaseMissing('users', ['name' => 'Duplicate Cashier']);
    }

    public function test_usernames_cannot_collide_with_existing_emails(): void
    {
        $admin = $this->createAdmin();
        $this->createCashier(['username' => null, 'email' => 'legacy@test.com']);

        $response = $this->actingAs($admin)->post(route('accounts.store'), [
            'username' => 'legacy@test.com',
            'name' => 'Collision Cashier',
            'password' => 'secretpass123',
            'password_confirmation' => 'secretpass123',
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_admin_can_reset_a_cashier_password_and_the_cashier_can_login(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier();

        $response = $this->actingAs($admin)->put(route('accounts.password.update', $cashier), [
            'password' => 'brandnewpass123',
            'password_confirmation' => 'brandnewpass123',
        ]);

        $response->assertRedirect(route('accounts.index'));

        $cashier->refresh();
        $this->assertTrue(Hash::check('brandnewpass123', $cashier->password));

        $this->post('/logout');

        $this->post('/login', [
            'username' => $cashier->username,
            'password' => 'brandnewpass123',
        ])->assertRedirect('/pos');

        $this->assertAuthenticatedAs($cashier);
    }

    public function test_admin_can_update_their_own_username_and_password(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put(route('accounts.me.update'), [
            'name' => 'Updated Admin',
            'username' => 'admin.updated',
            'email' => 'updated.admin@test.com',
            'current_password' => 'adminpass123',
            'password' => 'newadminpass123',
            'password_confirmation' => 'newadminpass123',
        ]);

        $response->assertRedirect(route('accounts.me'));

        $admin->refresh();
        $this->assertSame('admin.updated', $admin->username);
        $this->assertSame('Updated Admin', $admin->name);
        $this->assertTrue(Hash::check('newadminpass123', $admin->password));

        $this->post('/logout');

        $this->post('/login', [
            'username' => 'admin.updated',
            'password' => 'newadminpass123',
        ])->assertRedirect('/analytics');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_password_change_requires_the_correct_current_password(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->from(route('accounts.me'))->put(route('accounts.me.update'), [
            'name' => $admin->name,
            'username' => $admin->username,
            'current_password' => 'wrong-password',
            'password' => 'newadminpass123',
            'password_confirmation' => 'newadminpass123',
        ]);

        $response->assertRedirect(route('accounts.me'));
        $response->assertSessionHasErrors('current_password');

        $admin->refresh();
        $this->assertTrue(Hash::check('adminpass123', $admin->password));
    }

    public function test_inactive_cashiers_cannot_login(): void
    {
        $cashier = $this->createCashier(['is_active' => false]);

        $response = $this->post('/login', [
            'username' => $cashier->username,
            'password' => 'cashierpass123',
        ]);

        $response->assertSessionHasErrors([
            'username' => 'Your account has been disabled. Contact an administrator.',
        ]);
        $this->assertGuest();
    }

    public function test_admin_can_deactivate_a_cashier_account(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier();

        $response = $this->actingAs($admin)->post(route('accounts.status', $cashier));

        $response->assertRedirect(route('accounts.index'));
        $response->assertSessionHas('success', 'Account disabled. Active sessions were ended.');
        $this->assertFalse($cashier->fresh()->is_active);
    }

    public function test_legacy_email_login_still_works_without_a_username(): void
    {
        $cashier = $this->createCashier(['username' => null, 'email' => 'legacy.cashier@test.com']);

        $this->post('/login', [
            'email' => $cashier->email,
            'password' => 'cashierpass123',
        ])->assertRedirect('/pos');

        $this->assertAuthenticatedAs($cashier);
    }

    public function test_login_page_shows_the_disabled_account_message(): void
    {
        $this->get('/login?account_disabled=1')
            ->assertOk()
            ->assertSee('Your account has been disabled by an administrator. Contact an administrator.');
    }

    public function test_active_cashier_can_poll_account_status(): void
    {
        $cashier = $this->createCashier();

        $this->actingAs($cashier)
            ->getJson(route('account.status'))
            ->assertOk()
            ->assertJson(['active' => true]);
    }

    public function test_disabling_a_cashier_revokes_the_old_session_and_logs_forced_logout(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier();

        $this->actingAs($admin)
            ->postJson(route('accounts.status', $cashier), ['is_active' => '0'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'is_active' => false,
                'message' => 'Account disabled. Active sessions were ended.',
            ]);

        $this->actingAs($cashier)
            ->getJson(route('account.status'))
            ->assertStatus(401)
            ->assertJson([
                'reason' => 'account_disabled',
                'code' => 'account_disabled',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_ACCOUNT_FORCED_LOGOUT,
            'target_type' => 'user',
            'target_id' => $cashier->id,
        ]);
    }

    public function test_disabled_cashier_is_redirected_to_login(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier();

        $this->actingAs($admin)
            ->postJson(route('accounts.status', $cashier), ['is_active' => '0'])
            ->assertOk();

        $this->actingAs($cashier)
            ->get('/pos')
            ->assertRedirect(route('login', ['account_disabled' => 1]));
    }

    public function test_disabled_cashier_cannot_checkout_and_no_sale_is_created(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier();

        $this->actingAs($admin)
            ->postJson(route('accounts.status', $cashier), ['is_active' => '0'])
            ->assertOk();

        $this->actingAs($cashier)
            ->postJson('/checkout', [])
            ->assertStatus(401)
            ->assertJson(['reason' => 'account_disabled']);

        $this->assertSame(0, DB::table('sales')->count());
    }

    public function test_admin_cannot_disable_their_own_account(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->postJson(route('accounts.status', $admin), ['is_active' => '0'])
            ->assertStatus(422)
            ->assertJson(['message' => 'You cannot disable your own account.']);

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_cannot_disable_their_own_account_through_update(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->putJson(route('accounts.update', $admin), [
                'name' => $admin->name,
                'username' => $admin->username,
                'is_active' => '0',
            ])
            ->assertStatus(422)
            ->assertJson(['message' => 'You cannot disable your own account.']);

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_re_enabling_a_cashier_allows_login_again(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier();

        $this->actingAs($admin)
            ->postJson(route('accounts.status', $cashier), ['is_active' => '0'])
            ->assertOk();

        $this->actingAs($admin)
            ->postJson(route('accounts.status', $cashier), ['is_active' => '1'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'is_active' => true,
                'message' => 'Account re-enabled. The cashier can log in again.',
            ]);

        $this->post('/logout');

        $this->post('/login', [
            'username' => $cashier->username,
            'password' => 'cashierpass123',
        ])->assertRedirect('/pos');

        $this->assertAuthenticatedAs($cashier);
    }

    public function test_disabling_a_cashier_deletes_database_sessions_and_clears_remember_token(): void
    {
        config(['session.driver' => 'database']);

        $admin = $this->createAdmin();
        $cashier = $this->createCashier();
        $cashier->forceFill(['remember_token' => 'remember-me'])->save();

        DB::table('sessions')->insert([
            'id' => 'cashier-session',
            'user_id' => $cashier->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => base64_encode(serialize([])),
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin)
            ->postJson(route('accounts.status', $cashier), ['is_active' => '0'])
            ->assertOk();

        $this->assertDatabaseMissing('sessions', ['user_id' => $cashier->id]);
        $this->assertNull($cashier->fresh()->remember_token);
    }

    public function test_updating_a_cashier_to_inactive_revokes_the_old_session(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier();

        $this->actingAs($admin)->put(route('accounts.update', $cashier), [
            'name' => $cashier->name,
            'username' => $cashier->username,
            'email' => $cashier->email,
            'is_active' => '0',
        ])->assertRedirect(route('accounts.index'));

        $this->assertFalse($cashier->fresh()->is_active);

        $this->actingAs($cashier)
            ->getJson('/pos')
            ->assertStatus(401)
            ->assertJson(['reason' => 'account_disabled']);
    }
}
