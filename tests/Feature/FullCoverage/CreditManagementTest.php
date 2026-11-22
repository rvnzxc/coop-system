<?php

namespace Tests\Feature\FullCoverage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CreditManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_index_groups_balances_by_member(): void
    {
        $first = $this->member(['first_name' => 'Ana', 'last_name' => 'Reyes']);
        $second = $this->member(['first_name' => 'Ben', 'last_name' => 'Santos']);
        $this->credit(['member_id' => $first->id, 'amount' => 100]);
        $this->credit(['member_id' => $first->id, 'amount' => 250]);
        $this->credit(['member_id' => $second->id, 'amount' => 75]);

        $this->actingAs($this->cashier())->get(route('credits.index'))
            ->assertOk()
            ->assertSee('Ana Reyes')
            ->assertSee('Ben Santos')
            ->assertSee('425.00');
    }

    public function test_credit_show_displays_snapshot_and_member(): void
    {
        $member = $this->member();
        $credit = $this->credit([
            'member_id' => $member->id,
            'amount' => 320,
            'items_snapshot' => [[
                'product_name' => 'Notebook',
                'quantity' => 2,
                'price' => 160,
                'subtotal' => 320,
            ]],
        ]);

        $this->actingAs($this->admin())->get(route('credits.show', $credit->id))
            ->assertOk()
            ->assertSee('Notebook')
            ->assertSee($member->member_number);
    }

    public function test_credit_analytics_accepts_reversed_date_range(): void
    {
        $this->actingAs($this->admin())->get(route('credits.index', [
            'tab' => 'analytics',
            'from' => '2026-03-31',
            'to' => '2026-03-01',
        ]))->assertOk()->assertSee('data-credit-panel="analytics"', false);
    }

    public function test_credit_repayment_actions_are_not_implemented(): void
    {
        $this->assertFalse(Schema::hasTable('credit_payments'));
        $this->assertFalse(Schema::hasColumns('credits', ['amount_paid', 'status']));

        $names = collect(Route::getRoutes())
            ->map(fn ($route) => (string) $route->getName())
            ->filter()
            ->values();

        $this->assertFalse($names->contains(fn ($name) => str_contains($name, 'credit.pay')));
        $this->assertFalse($names->contains(fn ($name) => str_contains($name, 'credit.repay')));
    }
}
