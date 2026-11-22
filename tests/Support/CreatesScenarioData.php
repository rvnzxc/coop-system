<?php

namespace Tests\Support;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Credit;
use App\Models\Item;
use App\Models\Member;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Database\Factories\AuditLogFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\CreditFactory;
use Database\Factories\ItemFactory;
use Database\Factories\MemberFactory;
use Database\Factories\ProductFactory;
use Database\Factories\PurchaseFactory;
use Database\Factories\SaleFactory;
use Database\Factories\SaleItemFactory;
use Database\Factories\UserFactory;

trait CreatesScenarioData
{
    protected function admin(array $attributes = []): User
    {
        return app(UserFactory::class)->create(array_merge(['role' => 'admin'], $attributes));
    }

    protected function cashier(array $attributes = []): User
    {
        return app(UserFactory::class)->create(array_merge(['role' => 'cashier'], $attributes));
    }

    protected function member(array $attributes = []): Member
    {
        return app(MemberFactory::class)->create($attributes);
    }

    protected function item(array $attributes = []): Item
    {
        return app(ItemFactory::class)->create($attributes);
    }

    protected function category(array $attributes = []): Category
    {
        return app(CategoryFactory::class)->create($attributes);
    }

    protected function sale(array $attributes = []): Sale
    {
        return app(SaleFactory::class)->create($attributes);
    }

    protected function saleItem(array $attributes = []): SaleItem
    {
        return app(SaleItemFactory::class)->create($attributes);
    }

    protected function credit(array $attributes = []): Credit
    {
        return app(CreditFactory::class)->create($attributes);
    }

    protected function purchase(array $attributes = []): Purchase
    {
        return app(PurchaseFactory::class)->create($attributes);
    }

    protected function audit(array $attributes = []): AuditLog
    {
        return app(AuditLogFactory::class)->create($attributes);
    }

    protected function product(array $attributes = []): Product
    {
        return app(ProductFactory::class)->create($attributes);
    }
}
