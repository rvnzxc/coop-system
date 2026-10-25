<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_cashier_cannot_access_account_management(): void
    {
        $cashier = $this->createCashier();

        $this->actingAs($cashier)->get(route('accounts.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('accounts.create'))->assertForbidden();
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

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_admin_can_deactivate_a_cashier_account(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier();

        $response = $this->actingAs($admin)->post(route('accounts.status', $cashier));

        $response->assertRedirect(route('accounts.index'));
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
}
