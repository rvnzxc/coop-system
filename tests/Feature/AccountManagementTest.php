<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ], $overrides));
    }

    private function createCashier(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test Cashier',
            'username' => 'cashier',
            'email' => null,
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
        ], $overrides));
    }

    public function test_admin_can_view_accounts_page_with_cashiers(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier(['name' => 'Jane Cashier', 'username' => 'janec']);

        $response = $this->actingAs($admin)->get('/accounts');

        $response->assertOk();
        $response->assertSee('My Account');
        $response->assertSee('Cashier Accounts');
        $response->assertSee('Jane Cashier');
        $response->assertSee('janec');
    }

    public function test_cashier_cannot_access_accounts_page_or_endpoints(): void
    {
        $cashier = $this->createCashier();

        $this->actingAs($cashier)->get('/accounts')->assertForbidden();
        $this->actingAs($cashier)->post('/accounts/cashiers', [
            'name' => 'Nope',
            'username' => 'nope',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertForbidden();
        $this->actingAs($cashier)->put('/accounts/me', [
            'name' => 'Nope',
            'username' => 'nope',
        ])->assertForbidden();
    }

    public function test_admin_can_create_cashier_with_hashed_password_and_login(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/accounts/cashiers', [
            'name' => 'New Cashier',
            'username' => 'newcashier',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('accounts.index'));
        $response->assertSessionHas('success');

        $cashier = User::where('username', 'newcashier')->firstOrFail();

        $this->assertSame('cashier', $cashier->role);
        $this->assertTrue($cashier->is_active);
        $this->assertNotSame('secret123', $cashier->password);
        $this->assertTrue(Hash::check('secret123', $cashier->password));

        $this->post('/logout');
        $this->post('/login', [
            'username' => 'newcashier',
            'password' => 'secret123',
        ])->assertRedirect('/pos');

        $this->assertAuthenticatedAs($cashier);
    }

    public function test_duplicate_username_is_rejected_case_insensitively(): void
    {
        $admin = $this->createAdmin();
        $this->createCashier(['username' => 'CashierOne']);

        $response = $this->actingAs($admin)->post('/accounts/cashiers', [
            'name' => 'Duplicate',
            'username' => 'cashierone',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertSame(1, User::where('role', 'cashier')->count());
    }

    public function test_admin_can_update_cashier_username_and_password(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier(['username' => 'oldname']);

        $response = $this->actingAs($admin)->put('/accounts/cashiers/'.$cashier->id, [
            'name' => 'Updated Cashier',
            'username' => 'newname',
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ]);

        $response->assertRedirect(route('accounts.index'));

        $cashier->refresh();
        $this->assertSame('Updated Cashier', $cashier->name);
        $this->assertSame('newname', $cashier->username);
        $this->assertTrue(Hash::check('newsecret123', $cashier->password));

        $this->post('/login', [
            'username' => 'newname',
            'password' => 'newsecret123',
        ])->assertRedirect('/pos');
        $this->assertAuthenticatedAs($cashier);
    }

    public function test_editing_cashier_without_password_keeps_current_password(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier(['username' => 'keepme']);
        $originalHash = $cashier->password;

        $this->actingAs($admin)->put('/accounts/cashiers/'.$cashier->id, [
            'name' => 'Renamed Cashier',
            'username' => 'keepme2',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('accounts.index'));

        $cashier->refresh();
        $this->assertSame($originalHash, $cashier->password);
        $this->assertTrue(Hash::check('password', $cashier->password));
    }

    public function test_admin_can_reset_cashier_password(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier(['username' => 'resetcash']);

        $this->actingAs($admin)->post('/accounts/cashiers/'.$cashier->id.'/reset-password', [
            'password' => 'brandnew123',
            'password_confirmation' => 'brandnew123',
        ])->assertRedirect(route('accounts.index'));

        $cashier->refresh();
        $this->assertTrue(Hash::check('brandnew123', $cashier->password));

        $this->post('/login', [
            'username' => 'resetcash',
            'password' => 'brandnew123',
        ])->assertRedirect('/pos');
        $this->assertAuthenticatedAs($cashier);
    }

    public function test_admin_can_toggle_cashier_status_and_disabled_cashier_cannot_login(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier(['username' => 'toggleme']);

        $this->actingAs($admin)->patch('/accounts/cashiers/'.$cashier->id.'/status', [
            'is_active' => '0',
        ])->assertRedirect(route('accounts.index'));

        $cashier->refresh();
        $this->assertFalse($cashier->is_active);

        $this->post('/logout');
        $this->post('/login', [
            'username' => 'toggleme',
            'password' => 'password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_admin_can_update_own_info_with_correct_current_password(): void
    {
        $admin = $this->createAdmin(['username' => 'oldadmin']);

        $response = $this->actingAs($admin)->put('/accounts/me', [
            'name' => 'Renamed Admin',
            'username' => 'newadmin',
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('accounts.index'));
        $response->assertSessionHas('success');

        $admin->refresh();
        $this->assertSame('Renamed Admin', $admin->name);
        $this->assertSame('newadmin', $admin->username);
        $this->assertTrue(Hash::check('newpassword123', $admin->password));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_self_update_rejects_wrong_current_password(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put('/accounts/me', [
            'name' => 'Admin User',
            'username' => 'admin',
            'current_password' => 'wrong-password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');

        $admin->refresh();
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    public function test_self_update_requires_current_password_when_setting_a_new_one(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put('/accounts/me', [
            'name' => 'Admin User',
            'username' => 'admin',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_accounts_page_never_exposes_stored_passwords(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier();

        $response = $this->actingAs($admin)->get('/accounts');

        $response->assertOk();
        $response->assertDontSee($cashier->password);
        $response->assertDontSee('password_confirmation" value', false);
    }

    public function test_admin_cannot_manage_another_admin_through_cashier_endpoints(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin(['name' => 'Other Admin', 'username' => 'otheradmin', 'email' => 'other@test.com']);

        $this->actingAs($admin)->put('/accounts/cashiers/'.$otherAdmin->id, [
            'name' => 'Hijacked',
            'username' => 'hijacked',
        ])->assertNotFound();

        $this->assertSame('Other Admin', $otherAdmin->fresh()->name);
        $this->assertSame('otheradmin', $otherAdmin->fresh()->username);
    }

    public function test_legacy_plain_text_password_is_rehashed_on_login(): void
    {
        $user = $this->createCashier(['username' => 'legacyuser']);

        // Simulate an account created before password hashing was enforced.
        DB::table('users')->where('id', $user->id)->update(['password' => 'plaintextpass']);
        $user->refresh();

        $this->post('/login', [
            'username' => 'legacyuser',
            'password' => 'plaintextpass',
        ])->assertRedirect('/pos');

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame('plaintextpass', $user->fresh()->password);
        $this->assertTrue(Hash::check('plaintextpass', $user->fresh()->password));
    }
}
