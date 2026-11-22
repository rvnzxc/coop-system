<?php

namespace Database\Factories;

use App\Models\Credit;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditFactory extends Factory
{
    protected $model = Credit::class;

    public function definition(): array
    {
        return [
            'member_id' => null,
            'amount' => fake()->randomFloat(2, 10, 500),
            'sale_reference' => 'SLE'.fake()->unique()->numerify('#####'),
            'notes' => null,
            'items_snapshot' => [],
        ];
    }
}
