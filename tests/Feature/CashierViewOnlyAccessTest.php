<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierViewOnlyAccessTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' User',
            'username' => $role,
            'email' => $role.'@test.com',
            'password' => 'password123',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function createItem(array $overrides = []): Item
    {
        return Item::create(array_merge([
            'item_name' => 'Coffee',
            'quantity' => 25,
            'price' => 50.00,
            'category' => 'beverages',
        ], $overrides));
    }

    public function test_cashier_sidebar_has_read_only_pages_in_order(): void
    {
        $cashier = $this->createUser('cashier');

        $this->actingAs($cashier)
            ->get(route('shop.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'href="'.route('shop.index').'"',
                'href="'.route('inventory.index').'"',
                'href="'.route('analytics.index').'"',
                'href="'.route('credits.index').'"',
            ], false)
            ->assertDontSee('href="'.route('members.index').'"', false)
            ->assertDontSee('href="'.route('transactions.index').'"', false)
            ->assertDontSee('href="'.route('accounts.index').'"', false);
    }

    public function test_admin_sidebar_keeps_management_links(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('href="'.route('members.index').'"', false)
            ->assertSee('href="'.route('transactions.index').'"', false)
            ->assertSee('href="'.route('accounts.index').'"', false);
    }

    public function test_cashier_can_view_inventory_without_management_controls(): void
    {
        $cashier = $this->createUser('cashier');
        $item = $this->createItem();
        $logCount = AuditLog::count();

        $this->actingAs($cashier)
            ->get(route('inventory.index'))
            ->assertOk()
            ->assertSee('View only')
            ->assertSee($item->item_name)
            ->assertDontSee('Add Item')
            ->assertDontSee(route('inventory.create'), false)
            ->assertDontSee(route('inventory.edit', $item->id), false)
            ->assertDontSee(route('inventory.destroy', $item->id), false)
            ->assertDontSee('Remove Item');

        $this->assertSame($logCount, AuditLog::count());
    }

    public function test_cashier_inventory_has_no_actions_column_but_keeps_barcode_download(): void
    {
        $cashier = $this->createUser('cashier');
        $item = $this->createItem();

        $this->actingAs($cashier)
            ->get(route('inventory.index'))
            ->assertOk()
            ->assertDontSee('>Actions<', false)
            ->assertDontSee('title="Edit Item"', false)
            ->assertDontSee('title="Remove Item"', false)
            ->assertSee(route('inventory.barcode.download', $item->barcode), false);

        $this->actingAs($cashier)
            ->get(route('inventory.index', ['search' => 'no-such-item']))
            ->assertOk()
            ->assertSee('colspan="6"', false);
    }

    public function test_admin_inventory_keeps_actions_column_and_empty_state_colspan(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->get(route('inventory.index'))
            ->assertOk()
            ->assertSee('>Actions<', false);

        $this->actingAs($admin)
            ->get(route('inventory.index', ['search' => 'no-such-item']))
            ->assertOk()
            ->assertSee('colspan="7"', false);
    }

    public function test_admin_inventory_keeps_management_controls_without_view_only_badge(): void
    {
        $admin = $this->createUser('admin');
        $item = $this->createItem();

        $this->actingAs($admin)
            ->get(route('inventory.index'))
            ->assertOk()
            ->assertDontSee('View only')
            ->assertSee('Add Item')
            ->assertSee(route('inventory.create'), false)
            ->assertSee(route('inventory.edit', $item->id), false)
            ->assertSee(route('inventory.destroy', $item->id), false);
    }

    public function test_cashier_can_view_analytics_with_view_only_badge(): void
    {
        $cashier = $this->createUser('cashier');
        $logCount = AuditLog::count();

        $this->actingAs($cashier)
            ->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('Sales Performance Analytics')
            ->assertSee('View only');

        $this->assertSame($logCount, AuditLog::count());
    }

    public function test_admin_analytics_has_no_view_only_badge(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->get(route('analytics.index'))
            ->assertOk()
            ->assertDontSee('View only');
    }

    public function test_cashier_direct_inventory_management_pages_redirect_to_pos(): void
    {
        $cashier = $this->createUser('cashier');
        $item = $this->createItem();

        $this->actingAs($cashier)
            ->get(route('inventory.create'))
            ->assertRedirect(route('shop.index'));

        $this->actingAs($cashier)
            ->get(route('inventory.edit', $item->id))
            ->assertRedirect(route('shop.index'));
    }

    public function test_cashier_blocked_store_is_forbidden_logged_and_displays_in_activity_log(): void
    {
        $cashier = $this->createUser('cashier');
        $admin = $this->createUser('admin');

        $this->actingAs($cashier)
            ->post(route('inventory.store'), [
                'item_name' => 'Blocked Item',
                'quantity' => 10,
                'price' => 20,
                'category' => 'snacks',
            ])
            ->assertForbidden()
            ->assertSee("You don't have permission to modify inventory.");

        $this->assertDatabaseMissing('items', ['item_name' => 'Blocked Item']);

        $log = AuditLog::where('action', AuditLog::ACTION_PERMISSION_DENIED)->firstOrFail();

        $this->assertSame($cashier->id, $log->user_id);
        $this->assertSame('inventory', $log->target_type);
        $this->assertSame('cashier', $log->details['username']);
        $this->assertSame('add an inventory item', $log->details['attempted_action']);

        $this->actingAs($admin)
            ->get(route('transactions.activity'))
            ->assertOk()
            ->assertSee('Permission denied')
            ->assertSee('Permission denied for @cashier while attempting to add an inventory item.');
    }

    public function test_cashier_blocked_update_is_forbidden_logged_and_leaves_item_unchanged(): void
    {
        $cashier = $this->createUser('cashier');
        $item = $this->createItem();

        $this->actingAs($cashier)
            ->put(route('inventory.update', $item->id), [
                'item_name' => 'Changed Name',
                'quantity' => 1,
                'price' => 1,
                'category' => 'snacks',
            ])
            ->assertForbidden()
            ->assertSee("You don't have permission to modify inventory.");

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'item_name' => 'Coffee',
            'quantity' => 25,
        ]);

        $log = AuditLog::where('action', AuditLog::ACTION_PERMISSION_DENIED)->firstOrFail();

        $this->assertSame($item->id, $log->target_id);
        $this->assertSame('update inventory item #'.$item->id, $log->details['attempted_action']);
    }

    public function test_cashier_blocked_delete_is_forbidden_logged_and_leaves_item_unchanged(): void
    {
        $cashier = $this->createUser('cashier');
        $item = $this->createItem();

        $this->actingAs($cashier)
            ->delete(route('inventory.destroy', $item->id))
            ->assertForbidden()
            ->assertSee("You don't have permission to modify inventory.");

        $this->assertDatabaseHas('items', ['id' => $item->id]);

        $log = AuditLog::where('action', AuditLog::ACTION_PERMISSION_DENIED)->firstOrFail();

        $this->assertSame($item->id, $log->target_id);
        $this->assertSame('delete inventory item #'.$item->id, $log->details['attempted_action']);
    }

    public function test_read_only_pages_keep_notification_bell_and_logout_for_both_roles(): void
    {
        $cashier = $this->createUser('cashier');
        $admin = $this->createUser('admin');

        foreach ([
            [$cashier, route('inventory.index')],
            [$cashier, route('analytics.index')],
            [$admin, route('inventory.index')],
            [$admin, route('analytics.index')],
        ] as [$user, $url]) {
            $this->actingAs($user)
                ->get($url)
                ->assertOk()
                ->assertSee('id="notificationBadge"', false)
                ->assertSee("getElementById('logout-form').requestSubmit()", false);
        }
    }
}
