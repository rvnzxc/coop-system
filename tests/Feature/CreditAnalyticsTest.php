<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Member;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::create([
            'name' => 'Test Cashier',
            'email' => 'analytics@test.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
        ]);
    }

    private function createMember(): Member
    {
        return Member::create([
            'first_name' => 'Test',
            'last_name' => 'Member',
            'member_number' => 'MEM00001',
            'email' => 'analytics-member@test.com',
            'total_purchases' => 0,
            'purchase_count' => 0,
            'is_active' => true,
        ]);
    }

    private function createItem(string $name, string $category): Item
    {
        return Item::create([
            'item_name' => $name,
            'quantity' => 100,
            'price' => 10.00,
            'category' => $category,
        ]);
    }

    private function createSale(User $user, Member $member, string $paymentMethod, string $status, float $total): Sale
    {
        return Sale::create([
            'cashier_id' => $user->id,
            'member_id' => $member->id,
            'member_number' => $member->member_number,
            'customer_type' => 'member',
            'payment_method' => $paymentMethod,
            'total' => $total,
            'status' => $status,
        ]);
    }

    private function createSaleItem(Sale $sale, Item $item, int $quantity, float $unitPrice): SaleItem
    {
        return SaleItem::create([
            'sale_id' => $sale->id,
            'item_id' => $item->id,
            'product_name' => $item->item_name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $quantity * $unitPrice,
        ]);
    }

    public function test_analytics_only_counts_completed_credit_sales(): void
    {
        $user = $this->createUser();
        $member = $this->createMember();
        $coffee = $this->createItem('Coffee', 'Beverages');
        $rice = $this->createItem('Rice', 'Essentials');
        $sugar = $this->createItem('Sugar', 'Essentials');

        $creditSale = $this->createSale($user, $member, 'credit', Sale::STATUS_COMPLETED, 100.00);
        $this->createSaleItem($creditSale, $coffee, 3, 10.00);

        $cashSale = $this->createSale($user, $member, 'cash', Sale::STATUS_COMPLETED, 500.00);
        $this->createSaleItem($cashSale, $rice, 10, 50.00);

        $voidedSale = $this->createSale($user, $member, 'credit', Sale::STATUS_VOIDED, 200.00);
        $this->createSaleItem($voidedSale, $sugar, 5, 40.00);

        $response = $this->actingAs($user)->get(route('credits.index', ['tab' => 'analytics']));

        $response->assertOk();
        $response->assertSee('data-credit-tabs', false);
        $response->assertSee('data-credit-panel="credits"', false);
        $response->assertSee('data-credit-panel="analytics"', false);
        $response->assertSee('Credit purchases by category');

        $analytics = $response->viewData('creditAnalytics');

        $this->assertSame(100.0, $analytics['total_sales']);
        $this->assertSame(1, $analytics['transactions']);
        $this->assertSame(3, $analytics['total_quantity']);
        $this->assertSame('Beverages', $analytics['top_category']['name']);
        $this->assertSame(3, $analytics['top_category']['quantity']);
        $this->assertCount(1, $analytics['categories']);
    }

    public function test_analytics_applies_date_range(): void
    {
        $user = $this->createUser();
        $member = $this->createMember();
        $coffee = $this->createItem('Coffee', 'Beverages');
        $rice = $this->createItem('Rice', 'Essentials');

        $recentSale = $this->createSale($user, $member, 'credit', Sale::STATUS_COMPLETED, 30.00);
        $this->createSaleItem($recentSale, $coffee, 3, 10.00);

        $oldSale = $this->createSale($user, $member, 'credit', Sale::STATUS_COMPLETED, 100.00);
        $this->createSaleItem($oldSale, $rice, 10, 10.00);
        $oldSale->forceFill(['created_at' => now()->subDays(10)])->save();

        $response = $this->actingAs($user)->get(route('credits.index', [
            'tab' => 'analytics',
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertOk();

        $analytics = $response->viewData('creditAnalytics');

        $this->assertSame(30.0, $analytics['total_sales']);
        $this->assertSame(1, $analytics['transactions']);
        $this->assertSame(3, $analytics['total_quantity']);
        $this->assertSame('Beverages', $analytics['top_category']['name']);
    }
}
