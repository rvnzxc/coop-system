<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodeLookupTest extends TestCase
{
    use RefreshDatabase;

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

    // ── Automatic barcode assignment ─────────────────────────

    public function test_item_receives_a_generated_barcode_on_create(): void
    {
        $item = $this->createItem();

        $this->assertNotNull($item->barcode);
        $this->assertStringStartsWith('P', $item->barcode);
    }

    public function test_generated_barcodes_are_unique(): void
    {
        $first = $this->createItem(['item_name' => 'Rice']);
        $second = $this->createItem(['item_name' => 'Sugar']);

        $this->assertNotSame($first->barcode, $second->barcode);
    }

    public function test_supplied_barcode_is_not_overwritten(): void
    {
        $item = $this->createItem(['barcode' => 'P654321']);

        $this->assertSame('P654321', $item->barcode);
    }

    // ── Lookup endpoint ──────────────────────────────────────

    public function test_lookup_finds_item_by_internal_barcode(): void
    {
        $item = $this->createItem(['barcode' => 'P123456']);
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)->getJson('/items/lookup?code=P123456');

        $response->assertOk();
        $response->assertJson([
            'found' => true,
            'matched_on' => 'barcode',
            'item' => ['id' => $item->id],
        ]);
    }

    public function test_lookup_finds_item_by_manufacturer_barcode(): void
    {
        $item = $this->createItem(['manufacturer_barcode' => '012345678905']);
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)->getJson('/items/lookup?code=012345678905');

        $response->assertOk();
        $response->assertJson([
            'found' => true,
            'matched_on' => 'manufacturer_barcode',
            'item' => ['id' => $item->id],
        ]);
    }

    public function test_lookup_accepts_the_ean13_form_of_a_stored_upca(): void
    {
        $item = $this->createItem(['manufacturer_barcode' => '012345678905']);
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)->getJson('/items/lookup?code=0012345678905');

        $response->assertJson([
            'found' => true,
            'item' => ['id' => $item->id],
        ]);
    }

    public function test_lookup_trims_scanner_line_terminator(): void
    {
        $item = $this->createItem(['barcode' => 'P123456']);
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)->getJson('/items/lookup?code=P123456%0D%0A');

        $response->assertJson([
            'found' => true,
            'item' => ['id' => $item->id],
        ]);
    }

    public function test_lookup_reports_not_found_for_unknown_barcode(): void
    {
        $this->createItem();
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)->getJson('/items/lookup?code=P999999');

        $response->assertOk();
        $response->assertJson(['found' => false]);
    }

    public function test_lookup_returns_display_data_for_the_cart(): void
    {
        $item = $this->createItem([
            'item_name' => 'brown rice',
            'price' => 42.50,
            'barcode' => 'P123456',
        ]);
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)->getJson('/items/lookup?code=P123456');

        $response->assertJson([
            'item' => [
                'display_name' => 'Brown Rice',
                'price' => 42.50,
            ],
        ]);
    }

    public function test_lookup_requires_authentication(): void
    {
        $this->getJson('/items/lookup?code=P123456')->assertUnauthorized();
    }
}
