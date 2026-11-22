<?php

namespace Tests\Feature\FullCoverage;

use App\Models\Credit;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_deducts_stock_saves_sale_and_line_items_with_correct_totals(): void
    {
        $cashier = $this->cashier();
        $item = $this->item(['item_name' => 'Notebook', 'price' => 100, 'quantity' => 10]);

        $response = $this->actingAs($cashier)->postJson(route('shop.checkout'), [
            'items' => [['id' => $item->id, 'quantity' => 2]],
            'is_non_member' => true,
            'payment_method' => 'cash',
            'amount_received' => 250,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame(8, $item->fresh()->quantity);

        $sale = Sale::sole();
        $this->assertSame($cashier->id, $sale->cashier_id);
        $this->assertSame('non_member', $sale->customer_type);
        $this->assertSame('cash', $sale->payment_method);
        $this->assertSame('completed', $sale->status);
        $this->assertEquals(200, $sale->total);
        $this->assertEquals(250, $sale->amount_received);
        $this->assertEquals(50, $sale->change_amount);

        $this->assertSame(1, SaleItem::count());
        $this->assertEquals(2, SaleItem::sole()->quantity);
        $this->assertEquals(100, SaleItem::sole()->unit_price);
        $this->assertEquals(200, SaleItem::sole()->subtotal);
        $this->assertSame(1, Purchase::count());
        $this->assertSame(2, Purchase::sole()->quantity);
        $this->assertEquals(200, Purchase::sole()->amount);
    }

    public function test_checkout_rejects_quantity_greater_than_stock(): void
    {
        $item = $this->item(['price' => 50, 'quantity' => 1]);

        $response = $this->actingAs($this->cashier())->postJson(route('shop.checkout'), [
            'items' => [['id' => $item->id, 'quantity' => 2]],
            'is_non_member' => true,
            'payment_method' => 'cash',
            'amount_received' => 200,
        ]);

        $response->assertStatus(400)->assertJsonPath('success', false);
        $this->assertSame(1, $item->fresh()->quantity);
        $this->assertSame(0, Sale::count());
    }

    public function test_checkout_requires_member_or_non_member_selection(): void
    {
        $item = $this->item(['price' => 50, 'quantity' => 5]);

        $this->actingAs($this->cashier())->postJson(route('shop.checkout'), [
            'items' => [['id' => $item->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'amount_received' => 50,
        ])->assertStatus(400)->assertJsonPath('success', false);

        $this->assertSame(5, $item->fresh()->quantity);
        $this->assertSame(0, Sale::count());
    }

    public function test_checkout_updates_member_total_purchases_and_purchase_count(): void
    {
        $member = $this->member();
        $first = $this->item(['price' => 50, 'quantity' => 5]);
        $second = $this->item(['price' => 25, 'quantity' => 5]);

        $response = $this->actingAs($this->cashier())->postJson(route('shop.checkout'), [
            'items' => [
                ['id' => $first->id, 'quantity' => 1],
                ['id' => $second->id, 'quantity' => 1],
            ],
            'member_id' => $member->id,
            'payment_method' => 'cash',
            'amount_received' => 100,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $member->refresh();
        $this->assertEquals(75, $member->total_purchases);
        $this->assertSame(2, $member->purchase_count);
        $this->assertNotNull($member->last_purchase_date);
    }

    public function test_credit_checkout_records_credit_and_is_members_only(): void
    {
        $member = $this->member();
        $item = $this->item(['price' => 300, 'quantity' => 4]);

        $this->actingAs($this->cashier())->postJson(route('shop.checkout'), [
            'items' => [['id' => $item->id, 'quantity' => 1]],
            'is_non_member' => true,
            'payment_method' => 'credit',
        ])->assertStatus(400)->assertJsonPath('success', false);

        $this->actingAs($this->cashier())->postJson(route('shop.checkout'), [
            'items' => [['id' => $item->id, 'quantity' => 2]],
            'member_id' => $member->id,
            'payment_method' => 'credit',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame(1, Credit::count());
        $credit = Credit::sole();
        $this->assertSame($member->id, $credit->member_id);
        $this->assertEquals(600, $credit->amount);
        $this->assertCount(1, $credit->items_snapshot);
        $this->assertSame('credit', Sale::sole()->payment_method);
    }

    public function test_checkout_rejects_cash_payment_below_total(): void
    {
        $item = $this->item(['price' => 100, 'quantity' => 5]);

        $this->actingAs($this->cashier())->postJson(route('shop.checkout'), [
            'items' => [['id' => $item->id, 'quantity' => 2]],
            'is_non_member' => true,
            'payment_method' => 'cash',
            'amount_received' => 199,
        ])->assertStatus(400)->assertJsonPath('success', false);

        $this->assertSame(5, $item->fresh()->quantity);
        $this->assertSame(0, Sale::count());
    }

    public function test_checkout_blocks_inactive_and_archived_members(): void
    {
        $item = $this->item(['price' => 100, 'quantity' => 5]);
        $inactive = $this->member(['is_active' => false]);
        $archived = $this->member();

        $this->actingAs($this->cashier())->postJson(route('shop.checkout'), [
            'items' => [['id' => $item->id, 'quantity' => 1]],
            'member_id' => $inactive->id,
            'payment_method' => 'cash',
            'amount_received' => 100,
        ])->assertStatus(403)->assertJsonPath('is_active', false);

        $archived->archived_at = now();
        $archived->is_active = false;
        $archived->save();

        $this->actingAs($this->cashier())->postJson(route('shop.checkout'), [
            'items' => [['id' => $item->id, 'quantity' => 1]],
            'member_id' => $archived->id,
            'payment_method' => 'cash',
            'amount_received' => 100,
        ])->assertStatus(400)->assertJsonPath('success', false);

        $this->assertSame(5, $item->fresh()->quantity);
        $this->assertSame(0, Sale::count());
    }
}
