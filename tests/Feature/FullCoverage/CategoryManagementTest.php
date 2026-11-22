<?php

namespace Tests\Feature\FullCoverage;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_categories_and_duplicates_are_rejected(): void
    {
        $name = 'Beverages '.fake()->unique()->numberBetween(1000, 9999);
        $this->actingAs($this->admin())->postJson(route('categories.store'), ['name' => $name])
            ->assertStatus(201)
            ->assertJsonPath('name', $name);

        $this->assertDatabaseHas('categories', ['name' => $name]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_CATEGORY_CREATED,
            'target_type' => 'category',
        ]);

        $this->postJson(route('categories.store'), ['name' => '  '.strtolower($name).'  '])
            ->assertOk()
            ->assertJsonPath('name', $name);

        $this->assertSame(1, Category::whereRaw('LOWER(name) = ?', [strtolower($name)])->count());
    }

    public function test_category_with_items_cannot_be_deleted_but_empty_category_can(): void
    {
        $admin = $this->admin();
        $used = $this->category(['name' => 'Used']);
        $empty = $this->category(['name' => 'Empty']);
        $this->item(['category' => 'Used', 'category_id' => $used->id]);

        $this->actingAs($admin)->deleteJson(route('categories.destroy', $used->id))
            ->assertStatus(422)
            ->assertJsonPath('message', "Cannot delete 'Used': 1 item still use it.");

        $this->assertDatabaseHas('categories', ['id' => $used->id]);

        $this->deleteJson(route('categories.destroy', $empty->id))
            ->assertOk()
            ->assertJsonPath('message', "Category 'Empty' deleted successfully!");

        $this->assertDatabaseMissing('categories', ['id' => $empty->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_CATEGORY_DELETED,
            'target_id' => $empty->id,
        ]);
    }

    public function test_cashier_cannot_create_or_delete_categories(): void
    {
        $category = $this->category();
        $this->item(['category_id' => $category->id]);

        $this->actingAs($this->cashier())->postJson(route('categories.store'), ['name' => 'Denied'])
            ->assertForbidden();
        $this->deleteJson(route('categories.destroy', $category->id))->assertForbidden();

        $this->assertDatabaseMissing('categories', ['name' => 'Denied']);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertSame(1, Item::where('category_id', $category->id)->count());
    }
}
