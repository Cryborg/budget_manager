<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\Income;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Income>
 */
class IncomeFactory extends Factory
{
    protected $model = Income::class;

    public function definition(): array
    {
        $frequency = fake()->randomElement(['once', 'daily', 'weekly', 'monthly', 'yearly']);
        
        return [
            'bank_account_id' => BankAccount::factory(),
            'name' => fake()->randomElement(['Salaire', 'Prime', 'Freelance', 'Dividendes', 'Loyer', 'Vente']),
            'description' => fake()->optional()->sentence(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'date' => fake()->dateTimeBetween('-1 year', '+1 year'),
            'frequency' => $frequency,
            'start_date' => $frequency !== 'once' ? fake()->optional()->dateTimeBetween('-6 months', 'now') : null,
            'end_date' => $frequency !== 'once' ? fake()->optional()->dateTimeBetween('now', '+2 years') : null,
            'category' => fake()->optional()->randomElement(['Travail', 'Investissement', 'Immobilier', 'Autre']),
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

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'yearly',
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