<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'item_name' => fake()->unique()->words(2, true),
            'quantity' => fake()->numberBetween(0, 100),
            'price' => fake()->randomFloat(2, 1, 500),
            'category' => 'essentials',
        ];
    }
}
