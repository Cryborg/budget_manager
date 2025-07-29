<?php

namespace Database\Factories;

use App\Models\BalanceAdjustment;
use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BalanceAdjustment>
 */
class BalanceAdjustmentFactory extends Factory
{
    protected $model = BalanceAdjustment::class;

    public function definition(): array
    {
        return [
            'bank_account_id' => BankAccount::factory(),
            'adjustment_date' => fake()->dateTimeBetween('-1 year', '+1 year'),
            'actual_balance' => fake()->randomFloat(2, -5000, 15000),
            'description' => fake()->optional()->sentence(),
            'is_active' => fake()->boolean(90),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
