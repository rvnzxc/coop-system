<?php

namespace Tests\Feature;

use App\Models\Credit;
use App\Models\Item;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodeScanningTest extends TestCase
{
    use RefreshDatabase;

    private int $memberCounter = 0;

    private function createMember(array $overrides = []): Member
    {
        $this->memberCounter++;

        return Member::create(array_merge([
            'first_name' => 'Member',
            'last_name' => $this->memberCounter,
            'member_number' => 'MEM'.str_pad($this->memberCounter, 5, '0', STR_PAD_LEFT),
            'email' => "member{$this->memberCounter}@example.com",
            'total_purchases' => 0,
            'purchase_count' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function createItem(array $overrides = []): Item
    {
        return Item::create(array_merge([
            'item_name' => 'Rice',
            'quantity' => 100,
            'price' => 50.00,
            'category' => 'essentials',
        ], $overrides));
    }

    private function createCashier(): User
    {
        return User::create([
            'name' => 'Test Cashier',
            'email' => 'cashier@test.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
        ]);
    }

    // ── Cart keyed by item id ────────────────────────────────

    public function test_checkout_accepts_cart_lines_keyed_by_item_id(): void
    {
        $item = $this->createItem(['quantity' => 10, 'price' => 25.00]);
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)->postJson('/checkout', [
            'items' => [['id' => $item->id, 'quantity' => 3]],
            'member_id' => '',
            'is_non_member' => '1',
        ]);

        $response->assertJson(['success' => true]);
        $this->assertSame(7, $item->fresh()->quantity);
        $this->assertDatabaseHas('purchases', [
            'customer_type' => 'non_member',
            'product_name' => 'Rice',
            'quantity' => 3,
            'amount' => 75.00,
        ]);
    }

    public function test_checkout_by_id_distinguishes_items_with_the_same_name(): void
    {
        $first = $this->createItem(['item_name' => 'Rice', 'price' => 10.00, 'quantity' => 5]);
        $second = $this->createItem(['item_name' => 'Rice', 'price' => 99.00, 'quantity' => 5]);
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)->postJson('/checkout', [
            'items' => [['id' => $second->id, 'quantity' => 1]],
            'member_id' => '',
            'is_non_member' => '1',
        ]);

        $response->assertJson(['success' => true]);
        $this->assertSame(5, $first->fresh()->quantity);
        $this->assertSame(4, $second->fresh()->quantity);
        $this->assertDatabaseHas('purchases', ['product_name' => 'Rice', 'amount' => 99.00]);
    }

    // ── Scan → lookup → checkout round trip ──────────────────

    public function test_scanning_a_manufacturer_barcode_adds_the_right_item(): void
    {
        $item = $this->createItem([
            'manufacturer_barcode' => '012345678905',
            'price' => 15.00,
            'quantity' => 4,
        ]);
        $cashier = $this->createCashier();

        // The POS resolves the scan against the lookup endpoint first ...
        $lookup = $this->actingAs($cashier)->getJson('/items/lookup?code=012345678905');
        $lookup->assertJson(['found' => true]);

        // ... then checks out the resolved id.
        $resolvedId = $lookup->json('item.id');
        $response = $this->actingAs($cashier)->postJson('/checkout', [
            'items' => [['id' => $resolvedId, 'quantity' => 2]],
            'member_id' => '',
            'is_non_member' => '1',
        ]);

        $response->assertJson(['success' => true]);
        $this->assertSame(2, $item->fresh()->quantity);
    }

    // ── Guard rails ──────────────────────────────────────────

    public function test_checkout_still_fails_when_stock_is_insufficient(): void
    {
        $item = $this->createItem(['quantity' => 1]);
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)->postJson('/checkout', [
            'items' => [['id' => $item->id, 'quantity' => 5]],
            'member_id' => '',
            'is_non_member' => '1',
        ]);

        $response->assertStatus(400);
        $this->assertSame(1, $item->fresh()->quantity);
    }

    public function test_checkout_rejects_unknown_item_id(): void
    {
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)->postJson('/checkout', [
            'items' => [['id' => 999999, 'quantity' => 1]],
            'member_id' => '',
            'is_non_member' => '1',
        ]);

        $response->assertStatus(400);
    }

    // ── Credit snapshot correctness ──────────────────────────

    public function test_credit_snapshot_uses_the_resolved_item_name(): void
    {
        $member = $this->createMember();
        $item = $this->createItem(['item_name' => 'brown rice', 'price' => 30.00, 'quantity' => 10]);
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)->postJson('/checkout', [
            'items' => [['id' => $item->id, 'quantity' => 2]],
            'member_id' => (string) $member->id,
            'is_non_member' => '0',
            'payment_method' => 'credit',
        ]);

        $response->assertJson(['success' => true]);

        $credit = Credit::where('member_id', $member->id)->firstOrFail();
        $this->assertSame(60.00, (float) $credit->amount);
        $this->assertSame('brown rice', $credit->items_snapshot[0]['product_name']);
    }

    // ── Scan routing ────────────────────────────────────────

    public function test_pos_sends_every_scan_through_a_single_handler(): void
    {
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)->get('/pos');

        $response->assertOk();
        $response->assertSee('routeScan(this.value)', false);
        $response->assertSee('async function routeScan(code, fromScanner = false, fromMemberInput = false)', false);
        $response->assertSee('target !== document.body && target !== document.documentElement', false);
        $response->assertSee('focusSearch()', false);
        $response->assertSee('const resetScans = () => {', false);
        $response->assertSee("if (searchInput) searchInput.value = '';", false);
        $response->assertSee("if (memberInput) memberInput.value = '';", false);
        $response->assertSee('lookupMember(value, !cameFromMemberInput)', false);
        $response->assertSee('scanItem(value)', false);
        $response->assertDontSee('itemScanInput', false);
        $response->assertDontSee('lookupMember(true)', false);
        $response->assertDontSee('scanItem(value, true)', false);
    }

    public function test_pos_keeps_one_search_input_focused_and_scannable(): void
    {
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)->get('/pos');

        $response->assertOk();
        $response->assertSee('id="search-input"', false);
        $response->assertSee('placeholder="Search item or scan barcode..."', false);
        $response->assertSee('searchInput.addEventListener(\'keydown\'', false);
        $response->assertSee('showToast(\'Item/Member not found\')', false);
        $response->assertSee('focusSearch();', false);
        $response->assertDontSee('id="itemScanInput"', false);
        $response->assertDontSee('placeholder="Scan product barcode..."', false);
    }

    public function test_member_lookup_exact_only_matches_the_card_number(): void
    {
        $member = $this->createMember(['first_name' => 'John', 'last_name' => 'Ersando']);
        $cashier = $this->createCashier();

        $byName = $this->actingAs($cashier)->getJson('/members/lookup?q=John&exact=1');
        $byName->assertJson(['found' => false]);

        $byCard = $this->actingAs($cashier)->getJson('/members/lookup?q='.$member->member_number.'&exact=1');
        $byCard->assertJson(['found' => true]);
    }
}
