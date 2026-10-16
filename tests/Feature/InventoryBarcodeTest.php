<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryBarcodeTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    private function createItem(array $overrides = []): Item
    {
        return Item::create(array_merge([
            'item_name' => 'Sugar',
            'quantity' => 100,
            'price' => 45.00,
            'category' => 'essentials',
        ], $overrides));
    }

    // ── Display ──────────────────────────────────────────────

    public function test_edit_page_shows_both_barcode_columns(): void
    {
        $item = $this->createItem(['manufacturer_barcode' => '012345678905']);
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/inventory/edit/'.$item->id);

        $response->assertOk();
        $response->assertSee($item->barcode);
        $response->assertSee('012345678905');
        $response->assertSee('<svg', false);
    }

    public function test_edit_page_shows_blank_manufacturer_barcode_when_unset(): void
    {
        $item = $this->createItem();
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/inventory/edit/'.$item->id);

        $response->assertOk();
        $response->assertSee($item->barcode);
        $this->assertNull($item->manufacturer_barcode);
    }

    public function test_edit_page_wires_up_barcode_scanning(): void
    {
        $item = $this->createItem();
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/inventory/edit/'.$item->id);

        $response->assertOk();
        $response->assertSee('id="manufacturer_barcode"', false);
        $response->assertSee('id="barcodeScanStatus"', false);
        $response->assertSee('id="scanButton"', false);
        $response->assertSee('autocomplete="off"', false);
        $response->assertSee("getElementById('manufacturer_barcode')", false);
        $response->assertSee("getElementById('scanButton')", false);
    }

    public function test_create_page_offers_a_manufacturer_barcode_field(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/inventory/add');

        $response->assertOk();
        $response->assertSee('name="manufacturer_barcode"', false);
    }

    // ── Store ────────────────────────────────────────────────

    public function test_store_saves_the_manufacturer_barcode(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/inventory/store', [
            'item_name' => 'Flour',
            'quantity' => 20,
            'price' => 30.00,
            'category' => 'essentials',
            'manufacturer_barcode' => '4800016138546',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('items', [
            'item_name' => 'Flour',
            'manufacturer_barcode' => '4800016138546',
        ]);
    }

    public function test_store_without_a_manufacturer_barcode_keeps_it_null(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post('/inventory/store', [
            'item_name' => 'Salt',
            'quantity' => 5,
            'price' => 12.00,
            'category' => 'essentials',
            'manufacturer_barcode' => '',
        ]);

        $item = Item::where('item_name', 'Salt')->firstOrFail();
        $this->assertNull($item->manufacturer_barcode);
    }

    public function test_store_auto_generates_the_internal_barcode(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post('/inventory/store', [
            'item_name' => 'Soap',
            'quantity' => 9,
            'price' => 20.00,
            'category' => 'laundry',
            'barcode' => 'P999999',
        ]);

        $item = Item::where('item_name', 'Soap')->firstOrFail();
        $this->assertNotNull($item->barcode);
        $this->assertStringStartsWith('P', $item->barcode);
        $this->assertNotSame('P999999', $item->barcode);
    }

    public function test_store_rejects_a_duplicate_manufacturer_barcode(): void
    {
        $this->createItem(['manufacturer_barcode' => '011223344556']);
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/inventory/store', [
            'item_name' => 'Milk',
            'quantity' => 3,
            'price' => 60.00,
            'category' => 'frozen',
            'manufacturer_barcode' => '011223344556',
        ]);

        $response->assertSessionHasErrors('manufacturer_barcode');
        $this->assertDatabaseMissing('items', ['item_name' => 'Milk']);
    }

    // ── Update ───────────────────────────────────────────────

    public function test_update_can_set_the_manufacturer_barcode(): void
    {
        $item = $this->createItem();
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put('/inventory/update/'.$item->id, [
            'item_name' => $item->item_name,
            'quantity' => $item->quantity,
            'price' => $item->price,
            'category' => $item->category,
            'manufacturer_barcode' => '4800123456789',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame('4800123456789', $item->fresh()->manufacturer_barcode);
    }

    public function test_update_can_clear_the_manufacturer_barcode(): void
    {
        $item = $this->createItem(['manufacturer_barcode' => '4800123456789']);
        $admin = $this->createAdmin();

        $this->actingAs($admin)->put('/inventory/update/'.$item->id, [
            'item_name' => $item->item_name,
            'quantity' => $item->quantity,
            'price' => $item->price,
            'category' => $item->category,
            'manufacturer_barcode' => '',
        ]);

        $this->assertNull($item->fresh()->manufacturer_barcode);
    }

    public function test_update_allows_keeping_its_own_manufacturer_barcode(): void
    {
        $item = $this->createItem(['manufacturer_barcode' => '012345678905']);
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put('/inventory/update/'.$item->id, [
            'item_name' => 'Sugar and Honey',
            'quantity' => $item->quantity,
            'price' => $item->price,
            'category' => $item->category,
            'manufacturer_barcode' => '012345678905',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame('012345678905', $item->fresh()->manufacturer_barcode);
    }

    public function test_update_rejects_another_items_manufacturer_barcode(): void
    {
        $item = $this->createItem();
        $taken = $this->createItem([
            'item_name' => 'Butter',
            'manufacturer_barcode' => '0555555555555',
        ]);
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->put('/inventory/update/'.$item->id, [
            'item_name' => $item->item_name,
            'quantity' => $item->quantity,
            'price' => $item->price,
            'category' => $item->category,
            'manufacturer_barcode' => '0555555555555',
        ]);

        $response->assertSessionHasErrors('manufacturer_barcode');
        $this->assertNotSame('0555555555555', $item->fresh()->manufacturer_barcode);
        $this->assertSame('0555555555555', $taken->fresh()->manufacturer_barcode);
    }

    public function test_update_never_changes_the_internal_barcode(): void
    {
        $item = $this->createItem();
        $admin = $this->createAdmin();

        $this->actingAs($admin)->put('/inventory/update/'.$item->id, [
            'item_name' => $item->item_name,
            'quantity' => $item->quantity,
            'price' => $item->price,
            'category' => $item->category,
            'barcode' => 'P000001',
        ]);

        $this->assertSame($item->barcode, $item->fresh()->barcode);
        $this->assertNotSame('P000001', $item->fresh()->barcode);
    }

    public function test_inventory_requires_an_admin(): void
    {
        $item = $this->createItem();

        $this->get('/inventory/edit/'.$item->id)->assertRedirect();
        $this->post('/inventory/store', [])->assertRedirect();
    }
}
