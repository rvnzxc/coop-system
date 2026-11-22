<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Member;
use App\Models\Purchase;
use App\Models\User;
use App\Services\DividendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NonMemberSaleTest extends TestCase
{
    use RefreshDatabase;

    private int $memberCounter = 0;

    private function createMember(array $overrides = []): Member
    {
        $this->memberCounter++;
        return Member::create(array_merge([
            'first_name'     => 'Member',
            'last_name'      => $this->memberCounter,
            'member_number'  => '#' . str_pad($this->memberCounter, 5, '0', STR_PAD_LEFT),
            'email'          => "member{$this->memberCounter}@example.com",
            'total_purchases'=> 0,
            'purchase_count' => 0,
            'is_active'      => true,
        ], $overrides));
    }

    private function createItem(array $overrides = []): Item
    {
        return Item::create(array_merge([
            'item_name'  => 'Rice',
            'quantity'   => 100,
            'price'      => 50.00,
            'category'   => 'essentials',
        ], $overrides));
    }

    private function createCashier(): User
    {
        return User::create([
            'name'     => 'Test Cashier',
            'email'    => 'cashier@test.com',
            'password' => bcrypt('password'),
            'role'     => 'cashier',
        ]);
    }

    // ── Non-member sale ──────────────────────────────────────

    public function test_non_member_sale_saves_with_no_member_id(): void
    {
        $purchase = Purchase::create([
            'member_id'      => null,
            'member_number'  => null,
            'customer_type'  => 'non_member',
            'amount'         => 150.00,
            'quantity'       => 3,
            'product_name'   => 'Rice',
            'purchase_date'  => now()->toDateString(),
        ]);

        $this->assertNull($purchase->member_id);
        $this->assertNull($purchase->member_number);
        $this->assertEquals('non_member', $purchase->customer_type);
        $this->assertDatabaseHas('purchases', [
            'customer_type' => 'non_member',
            'member_id'     => null,
        ]);
    }

    // ── Member sale ──────────────────────────────────────────

    public function test_member_sale_stores_customer_type(): void
    {
        $member = $this->createMember();

        $purchase = Purchase::create([
            'member_id'      => $member->id,
            'member_number'  => $member->member_number,
            'customer_type'  => 'member',
            'amount'         => 200.00,
            'quantity'       => 4,
            'product_name'   => 'Rice',
            'purchase_date'  => now()->toDateString(),
        ]);

        $this->assertEquals('member', $purchase->customer_type);
        $this->assertEquals($member->id, $purchase->member_id);
        $this->assertDatabaseHas('purchases', [
            'customer_type' => 'member',
            'member_id'     => $member->id,
        ]);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function test_member_sales_scope_filters_correctly(): void
    {
        $member = $this->createMember();

        Purchase::create([
            'member_id' => $member->id, 'member_number' => $member->member_number,
            'customer_type' => 'member', 'amount' => 100, 'quantity' => 2,
            'product_name' => 'Item A', 'purchase_date' => '2026-08-01',
        ]);
        Purchase::create([
            'member_id' => null, 'member_number' => null,
            'customer_type' => 'non_member', 'amount' => 50, 'quantity' => 1,
            'product_name' => 'Item B', 'purchase_date' => '2026-08-01',
        ]);

        $this->assertEquals(1, Purchase::memberSales()->count());
        $this->assertEquals(1, Purchase::nonMemberSales()->count());
    }

    // ── Aggregation via DividendService ──────────────────────

    public function test_sales_totals_split_by_customer_type(): void
    {
        $member = $this->createMember();

        Purchase::create([
            'member_id' => $member->id, 'member_number' => $member->member_number,
            'customer_type' => 'member', 'amount' => 200, 'quantity' => 1,
            'product_name' => 'A', 'purchase_date' => '2026-08-10',
        ]);
        Purchase::create([
            'member_id' => $member->id, 'member_number' => $member->member_number,
            'customer_type' => 'member', 'amount' => 300, 'quantity' => 1,
            'product_name' => 'B', 'purchase_date' => '2026-08-15',
        ]);
        Purchase::create([
            'member_id' => null, 'member_number' => null,
            'customer_type' => 'non_member', 'amount' => 100, 'quantity' => 1,
            'product_name' => 'C', 'purchase_date' => '2026-08-12',
        ]);
        Purchase::create([
            'member_id' => null, 'member_number' => null,
            'customer_type' => 'non_member', 'amount' => 50, 'quantity' => 1,
            'product_name' => 'D', 'purchase_date' => '2026-08-20',
        ]);

        $service = new DividendService();
        $totals = $service->getSalesTotals('2026-08-01', '2026-08-31');

        $this->assertEquals(500.0, $totals['member_total']);
        $this->assertEquals(150.0, $totals['non_member_total']);
        $this->assertEquals(650.0, $totals['combined_total']);
    }

    public function test_dividend_placeholder_distributes_evenly(): void
    {
        $this->createMember(['first_name' => 'Member', 'last_name' => 'One']);
        $this->createMember(['first_name' => 'Member', 'last_name' => 'Two']);

        Purchase::create([
            'member_id' => null, 'member_number' => null,
            'customer_type' => 'non_member', 'amount' => 1000, 'quantity' => 1,
            'product_name' => 'X', 'purchase_date' => '2026-08-10',
        ]);

        $service = new DividendService();
        $result = $service->distribute('2026-08-01', '2026-08-31');

        $this->assertEquals(0.0, $result['member_total']);
        $this->assertEquals(1000.0, $result['non_member_total']);
        $this->assertEquals(2, $result['member_count']);
        $this->assertEquals(500.0, $result['dividend_per_member']);
    }

    public function test_date_range_filtering(): void
    {
        Purchase::create([
            'member_id' => null, 'member_number' => null,
            'customer_type' => 'non_member', 'amount' => 100, 'quantity' => 1,
            'product_name' => 'A', 'purchase_date' => '2026-08-15',
        ]);
        Purchase::create([
            'member_id' => null, 'member_number' => null,
            'customer_type' => 'non_member', 'amount' => 200, 'quantity' => 1,
            'product_name' => 'B', 'purchase_date' => '2026-09-05',
        ]);

        $service = new DividendService();
        $augustTotals = $service->getSalesTotals('2026-08-01', '2026-08-31');
        $septTotals = $service->getSalesTotals('2026-09-01', '2026-09-30');

        $this->assertEquals(100.0, $augustTotals['non_member_total']);
        $this->assertEquals(200.0, $septTotals['non_member_total']);
    }

    // ── Checkout flow integration ────────────────────────────

    public function test_checkout_creates_non_member_purchase(): void
    {
        $this->createItem();
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)
            ->postJson('/checkout', [
                'items'         => [['name' => 'Rice', 'quantity' => 2]],
                'member_id'     => '',
                'is_non_member' => '1',
            ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('purchases', [
            'customer_type' => 'non_member',
            'member_id'     => null,
            'product_name'  => 'Rice',
        ]);
    }

    public function test_checkout_creates_member_purchase(): void
    {
        $member = $this->createMember();
        $this->createItem();
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)
            ->postJson('/checkout', [
                'items'         => [['name' => 'Rice', 'quantity' => 2]],
                'member_id'     => (string) $member->id,
                'is_non_member' => '0',
            ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('purchases', [
            'customer_type' => 'member',
            'member_id'     => $member->id,
            'product_name'  => 'Rice',
        ]);
    }

    public function test_checkout_fails_without_member_or_non_member(): void
    {
        $this->createItem();
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)
            ->postJson('/checkout', [
                'items'         => [['name' => 'Rice', 'quantity' => 1]],
                'member_id'     => '',
                'is_non_member' => '0',
            ]);

        $response->assertStatus(400);
    }
}
