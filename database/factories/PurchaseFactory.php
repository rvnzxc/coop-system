<?php

namespace Database\Factories;

use App\Models\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        return [
            'member_id' => null,
            'member_number' => null,
            'customer_type' => 'non_member',
            'payment_method' => 'cash',
            'amount' => fake()->randomFloat(2, 10, 500),
            'quantity' => fake()->numberBetween(1, 5),
            'product_name' => fake()->words(2, true),
            'purchase_date' => fake()->date(),
        ];
    }

    public function member(?int $memberId = null, ?string $memberNumber = null): static
    {
        return $this->state(fn () => [
            'member_id' => $memberId,
            'member_number' => $memberNumber,
            'customer_type' => 'member',
        ]);
    }
}
