<?php

namespace Tests\Feature\FullCoverage;

use App\Models\AuditLog;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_and_delete_items(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('inventory.store'), [
            'item_name' => 'Notebook',
            'quantity' => 12,
            'price' => 45.5,
            'category' => 'essentials',
            'manufacturer_barcode' => 'MFG-1001',
        ])->assertRedirect(route('inventory.index'));

        $item = Item::where('item_name', 'Notebook')->sole();
        $this->assertNotNull($item->barcode);
        $this->assertSame('MFG-1001', $item->manufacturer_barcode);

        $this->actingAs($admin)->put(route('inventory.update', $item->id), [
            'item_name' => 'Notebook Updated',
            'quantity' => 9,
            'price' => 60,
            'category' => 'essentials',
            'manufacturer_barcode' => 'MFG-1001',
        ])->assertRedirect(route('inventory.index'));

        $this->assertSame('Notebook Updated', $item->fresh()->item_name);
        $this->assertSame(9, $item->fresh()->quantity);
        $this->assertEquals(60, $item->fresh()->price);

        $this->actingAs($admin)->delete(route('inventory.destroy', $item->id))
            ->assertRedirect(route('inventory.index'));
        $this->assertNull(Item::find($item->id));
    }

    public function test_invalid_item_values_are_rejected(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('inventory.store'), [
            'item_name' => 'Bad quantity',
            'quantity' => -1,
            'price' => 10,
            'category' => 'essentials',
        ])->assertSessionHasErrors('quantity');

        $this->post(route('inventory.store'), [
            'item_name' => 'Bad price',
            'quantity' => 1,
            'price' => -5,
            'category' => 'essentials',
        ])->assertSessionHasErrors('price');

        $this->assertSame(0, Item::count());
    }

    public function test_duplicate_manufacturer_barcode_is_rejected(): void
    {
        $this->item(['manufacturer_barcode' => 'MFG-DUP-1']);

        $this->actingAs($this->admin())->postJson(route('inventory.store'), [
            'item_name' => 'Duplicate',
            'quantity' => 1,
            'price' => 10,
            'category' => 'essentials',
            'manufacturer_barcode' => 'MFG-DUP-1',
        ])->assertStatus(422)->assertJsonValidationErrors('manufacturer_barcode');

        $this->assertSame(1, Item::count());
    }

    public function test_item_mass_assignment_cannot_set_primary_key(): void
    {
        $this->actingAs($this->admin())->post(route('inventory.store'), [
            'id' => 9999,
            'item_name' => 'Safe Item',
            'quantity' => 1,
            'price' => 10,
            'category' => 'essentials',
        ])->assertRedirect(route('inventory.index'));

        $this->assertNotNull(Item::where('item_name', 'Safe Item')->first());
        $this->assertNull(Item::find(9999));
    }

    public function test_cashier_inventory_is_read_only_and_logs_denied_writes(): void
    {
        $cashier = $this->cashier();
        $item = $this->item(['item_name' => 'Visible Item']);

        $response = $this->actingAs($cashier)->get(route('inventory.index'));
        $response->assertOk()->assertSee('View only')->assertSee('Visible Item');
        $response->assertDontSee('>Actions</th>', false);

        $this->getJson(route('inventory.create'))->assertForbidden();
        $this->getJson(route('inventory.edit', $item->id))->assertForbidden();
        $this->postJson(route('inventory.store'), [
            'item_name' => 'Denied',
            'quantity' => 1,
            'price' => 1,
            'category' => 'essentials',
        ])->assertForbidden();
        $this->putJson(route('inventory.update', $item->id), [
            'item_name' => 'Denied',
            'quantity' => 1,
            'price' => 1,
            'category' => 'essentials',
        ])->assertForbidden();
        $this->deleteJson(route('inventory.destroy', $item->id))->assertForbidden();

        $this->assertSame(1, Item::count());
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $cashier->id,
            'action' => AuditLog::ACTION_PERMISSION_DENIED,
        ]);
    }

    public function test_low_stock_threshold_is_inclusive_at_ten(): void
    {
        $low = $this->item(['item_name' => 'Low Item', 'quantity' => 10]);
        $healthy = $this->item(['item_name' => 'Healthy Item', 'quantity' => 11]);

        $this->actingAs($this->cashier())->getJson(route('inventory.low-stock'))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('threshold', 10)
            ->assertJsonPath('low_stock_items.0.id', $low->id);

        $this->get(route('inventory.index'))
            ->assertOk()
            ->assertSee('Low Stock (&le;10)', false)
            ->assertSee($low->item_name)
            ->assertSee('id="notificationBadge"', false);

        $this->actingAs($this->admin())->put(route('inventory.update', $low->id), [
            'item_name' => $low->item_name,
            'quantity' => 11,
            'price' => $low->price,
            'category' => 'essentials',
        ])->assertRedirect(route('inventory.index'));

        $this->getJson(route('inventory.low-stock'))
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('threshold', 10)
            ->assertJsonCount(0, 'low_stock_items');
    }

    public function test_inventory_search_sort_and_summary_cards_use_current_items(): void
    {
        $this->item(['item_name' => 'Zebra Soap', 'quantity' => 20, 'price' => 2]);
        $this->item(['item_name' => 'Apple Juice', 'quantity' => 3, 'price' => 5]);

        $this->actingAs($this->admin())->get(route('inventory.index', ['search' => 'Zebra']))
            ->assertOk()
            ->assertSee('Zebra Soap')
            ->assertDontSee('Apple Juice');

        $response = $this->get(route('inventory.index', ['sort' => 'low']));
        $response->assertOk();
        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'Zebra Soap'), strpos($content, 'Apple Juice'));
        $response->assertSee('Total Items', false)
            ->assertSee('Stock Value', false)
            ->assertSee('&#8369;'.number_format(55, 2), false);
    }
}
