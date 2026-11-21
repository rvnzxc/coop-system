<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' User',
            'username' => $role.'.logout',
            'email' => $role.'.logout@test.com',
            'password' => 'password123',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function assertNoCacheHeaders($response): void
    {
        $cacheControl = $response->headers->get('Cache-Control');

        $this->assertNotNull($cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);

        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', '0');
    }

    public function test_cashier_logout_ends_the_session_and_blocks_protected_pages(): void
    {
        $cashier = $this->createUser('cashier');

        $this->actingAs($cashier)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
        $this->get('/pos')->assertRedirect('/login');
        $this->get('/credits')->assertRedirect('/login');
    }

    public function test_admin_logout_ends_the_session_and_blocks_protected_pages(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
        $this->get('/analytics')->assertRedirect('/login');
        $this->get('/accounts')->assertRedirect('/login');
    }

    public function test_logout_returns_json_for_ajax_requests(): void
    {
        $cashier = $this->createUser('cashier');

        $this->actingAs($cashier)
            ->postJson('/logout')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'redirect' => '/login',
            ]);

        $this->assertGuest();
        $this->get('/pos')->assertRedirect('/login');
    }

    public function test_authenticated_users_visiting_the_login_page_are_redirected_to_their_main_page(): void
    {
        $cashier = $this->createUser('cashier');
        $admin = $this->createUser('admin');

        $this->actingAs($cashier)->get('/login')->assertRedirect('/pos');
        $this->actingAs($admin)->get('/login')->assertRedirect('/analytics');
    }

    public function test_disabled_users_visiting_the_login_page_are_logged_out(): void
    {
        $cashier = $this->createUser('cashier');
        $cashier->update(['is_active' => false]);

        $this->actingAs($cashier)->get('/login')->assertOk();

        $this->assertGuest();
    }

    public function test_pages_include_the_session_and_login_history_guards(): void
    {
        $cashier = $this->createUser('cashier');

        $this->get('/login')->assertSee('name="ccfmpc-login"', false);

        $this->actingAs($cashier)->get('/pos')
            ->assertSee('id="sessionGuard"', false)
            ->assertSee('data-session-content', false)
            ->assertSee("getElementById('logout-form').requestSubmit()", false)
            ->assertDontSee("document.documentElement.classList.add('session-pending');", false);
    }

    public function test_login_and_authenticated_pages_send_no_cache_headers(): void
    {
        $cashier = $this->createUser('cashier');

        $this->get('/login')->assertOk();
        $this->assertNoCacheHeaders($this->get('/login'));

        $this->assertNoCacheHeaders($this->actingAs($cashier)->get('/pos'));
    }

    public function test_logout_and_api_responses_send_no_cache_headers(): void
    {
        $cashier = $this->createUser('cashier');

        $this->assertNoCacheHeaders($this->actingAs($cashier)->post('/logout'));
        $this->assertNoCacheHeaders($this->get('/api/test'));
    }
}
