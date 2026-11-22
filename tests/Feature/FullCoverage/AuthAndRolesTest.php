<?php

namespace Tests\Feature\FullCoverage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_protected_pages(): void
    {
        foreach (['analytics.index', 'inventory.index', 'credits.index', 'my-transactions.index'] as $route) {
            $this->get(route($route))->assertRedirect('/login');
        }
    }

    public function test_cashier_gets_403_for_admin_only_json_routes(): void
    {
        $this->actingAs($this->cashier());

        foreach (['accounts.index', 'transactions.index', 'members.index'] as $route) {
            $this->getJson(route($route))->assertForbidden();
        }
    }

    public function test_admin_can_open_admin_pages(): void
    {
        $this->actingAs($this->admin());

        foreach (['accounts.index', 'transactions.index', 'members.index', 'analytics.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_inactive_cashier_cannot_login(): void
    {
        $cashier = $this->cashier();
        $cashier->is_active = false;
        $cashier->save();

        $response = $this->post('/login', [
            'username' => $cashier->username,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_authenticated_inactive_user_is_logged_out_on_next_request(): void
    {
        $cashier = $this->cashier();
        $this->actingAs($cashier);

        $cashier->is_active = false;
        $cashier->save();

        $this->get(route('shop.index'))
            ->assertRedirect(route('login', ['account_disabled' => 1]));
        $this->assertGuest();
    }
}
