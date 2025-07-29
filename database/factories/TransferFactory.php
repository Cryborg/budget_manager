<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\Transfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transfer>
 */
class TransferFactory extends Factory
{
    protected $model = Transfer::class;

    public function definition(): array
    {
        $frequency = fake()->randomElement(['once', 'daily', 'weekly', 'monthly', 'yearly']);
        
        return [
            'from_account_id' => BankAccount::factory(),
            'to_account_id' => BankAccount::factory(),
            'name' => fake()->randomElement(['Virement épargne', 'Remboursement', 'Transfer automatique', 'Prélèvement']),
            'description' => fake()->optional()->sentence(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'date' => fake()->dateTimeBetween('-1 year', '+1 year'),
            'frequency' => $frequency,
            'start_date' => $frequency !== 'once' ? fake()->optional()->dateTimeBetween('-6 months', 'now') : null,
            'end_date' => $frequency !== 'once' ? fake()->optional()->dateTimeBetween('now', '+2 years') : null,
            'is_active' => fake()->boolean(85),
        ];
    }

    public function once(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'once',
            'start_date' => null,
            'end_date' => null,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'monthly',
        ]);
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