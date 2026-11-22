<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'item_name' => fake()->unique()->words(2, true),
            'barcode' => null,
            'manufacturer_barcode' => null,
            'quantity' => fake()->numberBetween(0, 100),
            'price' => fake()->randomFloat(2, 1, 500),
            'category' => 'essentials',
            'category_id' => null,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn () => ['quantity' => 10]);
    }
}
