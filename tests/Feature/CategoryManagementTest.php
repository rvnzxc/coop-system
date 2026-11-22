<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin User',
            'username' => 'category-admin',
            'email' => 'category-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createCashier(): User
    {
        return User::create([
            'name' => 'Cashier User',
            'username' => 'category-cashier',
            'email' => 'category-cashier@example.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_add_a_category_with_json_and_it_is_logged(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->postJson(route('categories.store'), [
            'name' => '  Gelato  ',
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Gelato');

        $this->assertDatabaseHas('categories', ['name' => 'Gelato']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_CATEGORY_CREATED,
            'target_type' => 'category',
        ]);
    }

    public function test_duplicate_category_json_returns_existing_category(): void
    {
        $admin = $this->createAdmin();
        $category = Category::create(['name' => 'Gelato']);

        $this->actingAs($admin)->postJson(route('categories.store'), [
            'name' => ' gelato ',
        ])->assertOk()->assertJsonPath('id', $category->id)->assertJsonPath('name', 'Gelato');

        $this->assertSame(1, Category::whereRaw('LOWER(name) = ?', ['gelato'])->count());
    }

    public function test_cashier_gets_403_on_category_routes(): void
    {
        $cashier = $this->createCashier();
        $category = Category::create(['name' => 'Frozen']);

        $this->actingAs($cashier)->postJson(route('categories.store'), [
            'name' => 'Drinks',
        ])->assertForbidden();

        $this->actingAs($cashier)->deleteJson(route('categories.destroy', $category))
            ->assertForbidden();

        $this->assertDatabaseHas('categories', ['name' => 'Frozen']);
        $this->assertDatabaseMissing('categories', ['name' => 'Drinks']);
    }

    public function test_category_with_items_cannot_be_deleted(): void
    {
        $admin = $this->createAdmin();
        $category = Category::create(['name' => 'Frozen']);

        Item::create([
            'item_name' => 'Ice cream',
            'quantity' => 12,
            'price' => 100,
            'category' => 'Frozen',
            'category_id' => $category->id,
        ]);

        $this->actingAs($admin)->deleteJson(route('categories.destroy', $category))
            ->assertUnprocessable()
            ->assertJsonPath('message', "Cannot delete 'Frozen': 1 item still use it.");

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('items', ['category_id' => $category->id]);
    }

    public function test_empty_category_can_be_deleted_and_logged(): void
    {
        $admin = $this->createAdmin();
        $category = Category::create(['name' => 'Drinks']);

        $this->actingAs($admin)->deleteJson(route('categories.destroy', $category))
            ->assertOk();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_CATEGORY_DELETED,
            'target_type' => 'category',
            'target_id' => $category->id,
        ]);
    }

    public function test_item_forms_use_shared_select_and_preselect_current_category(): void
    {
        $admin = $this->createAdmin();
        $category = Category::create(['name' => 'Gelato']);
        $item = Item::create([
            'item_name' => 'Ice cream',
            'quantity' => 5,
            'price' => 100,
            'category' => 'Gelato',
            'category_id' => $category->id,
        ]);

        $this->actingAs($admin)->get(route('inventory.create'))
            ->assertOk()
            ->assertSee('data-category-select', false)
            ->assertSee('+ Add new category')
            ->assertSee('const storeUrl = '.json_encode(route('categories.store')), false);

        $this->actingAs($admin)->get(route('inventory.edit', $item->id))
            ->assertOk()
            ->assertSee('data-category-select', false)
            ->assertSee('value="Gelato" selected', false);
    }

    public function test_manage_categories_button_is_admin_only(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier();

        $this->actingAs($admin)->get(route('inventory.index'))
            ->assertOk()
            ->assertSee('Manage Categories')
            ->assertSee('data-category-manager-modal', false);

        $this->actingAs($cashier)->get(route('inventory.index'))
            ->assertOk()
            ->assertDontSee('Manage Categories')
            ->assertDontSee('data-category-manager-modal', false)
            ->assertDontSee('+ Add new category');
    }

    public function test_pos_tabs_come_from_categories_table(): void
    {
        $cashier = $this->createCashier();
        Category::where('name', 'disposable')->delete();
        $category = Category::create(['name' => 'Frozen Treats']);

        Item::create([
            'item_name' => 'Ice cream',
            'quantity' => 5,
            'price' => 100,
            'category' => 'Frozen Treats',
            'category_id' => $category->id,
        ]);

        $this->actingAs($cashier)->get(route('shop.index'))
            ->assertOk()
            ->assertSee('data-category="Frozen Treats"', false)
            ->assertDontSee('data-category="disposable"', false);
    }
}
