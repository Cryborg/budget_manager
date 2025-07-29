<?php

namespace Database\Factories;

use App\Models\Bank;
use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'bank_id' => Bank::factory(),
            'name' => fake()->randomElement(['Compte Courant', 'Livret A', 'Compte Épargne', 'PEL', 'Compte Chèque']),
            'type' => fake()->randomElement(['current', 'savings', 'investment']),
            'current_balance' => fake()->randomFloat(2, -1000, 10000),
            'is_active' => fake()->boolean(90), // 90% chance d'être actif
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

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'current',
        ]);
    }

    public function savings(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'savings',
        ]);
    }

    public function investment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'investment',
        ]);
    }
}
