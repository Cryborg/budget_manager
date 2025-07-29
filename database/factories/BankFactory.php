<?php

namespace Database\Factories;

use App\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bank>
 */
class BankFactory extends Factory
{
    protected $model = Bank::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Bank',
            'code' => fake()->unique()->regexify('[A-Z]{2}[0-9]{4}'),
            'color' => fake()->hexColor(),
        ];
    }
}