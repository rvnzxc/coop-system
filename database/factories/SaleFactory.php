<?php

namespace Database\Factories;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'cashier_id' => null,
            'member_id' => null,
            'member_number' => null,
            'customer_type' => 'non_member',
            'payment_method' => 'cash',
            'total' => fake()->randomFloat(2, 10, 500),
            'amount_received' => null,
            'change_amount' => null,
            'status' => Sale::STATUS_COMPLETED,
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

    public function credit(): static
    {
        return $this->state(fn () => ['payment_method' => 'credit']);
    }
}
