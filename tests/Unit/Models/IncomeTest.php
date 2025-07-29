<?php

namespace Tests\Unit\Models;

use App\Models\BankAccount;
use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_income_belongs_to_bank_account(): void
    {
        $account = BankAccount::factory()->create();
        $income = Income::factory()->create(['bank_account_id' => $account->id]);

        $this->assertInstanceOf(BankAccount::class, $income->bankAccount);
        $this->assertEquals($account->id, $income->bankAccount->id);
    }

    public function test_income_casts_attributes_correctly(): void
    {
        $income = Income::factory()->create([
            'amount' => 1234.56,
            'date' => '2024-01-15',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'is_active' => true,
        ]);

        $this->assertIsFloat($income->amount);
        $this->assertEquals(1234.56, $income->amount);
        $this->assertInstanceOf(Carbon::class, $income->date);
        $this->assertInstanceOf(Carbon::class, $income->start_date);
        $this->assertInstanceOf(Carbon::class, $income->end_date);
        $this->assertIsBool($income->is_active);
        $this->assertTrue($income->is_active);
    }

    public function test_income_fillable_attributes(): void
    {
        $attributes = [
            'bank_account_id' => 1,
            'name' => 'Salaire',
            'description' => 'Salaire mensuel',
            'amount' => 2500.00,
            'date' => '2024-01-15',
            'frequency' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'category' => 'Travail',
            'is_active' => true,
        ];

        $income = new Income($attributes);

        $this->assertEquals($attributes['bank_account_id'], $income->bank_account_id);
        $this->assertEquals($attributes['name'], $income->name);
        $this->assertEquals($attributes['description'], $income->description);
        $this->assertEquals($attributes['amount'], $income->amount);
        $this->assertEquals($attributes['frequency'], $income->frequency);
        $this->assertEquals($attributes['category'], $income->category);
        $this->assertEquals($attributes['is_active'], $income->is_active);
    }

    public function test_income_factory_states(): void
    {
        $onceIncome = Income::factory()->once()->create();
        $this->assertEquals('once', $onceIncome->frequency);
        $this->assertNull($onceIncome->start_date);
        $this->assertNull($onceIncome->end_date);

        $monthlyIncome = Income::factory()->monthly()->create();
        $this->assertEquals('monthly', $monthlyIncome->frequency);

        $yearlyIncome = Income::factory()->yearly()->create();
        $this->assertEquals('yearly', $yearlyIncome->frequency);

        $activeIncome = Income::factory()->active()->create();
        $this->assertTrue($activeIncome->is_active);

        $inactiveIncome = Income::factory()->inactive()->create();
        $this->assertFalse($inactiveIncome->is_active);
    }

    public function test_income_can_have_null_dates_for_once_frequency(): void
    {
        $income = Income::factory()->once()->create();

        $this->assertEquals('once', $income->frequency);
        $this->assertNull($income->start_date);
        $this->assertNull($income->end_date);
    }

    public function test_income_can_have_optional_description_and_category(): void
    {
        $income = Income::factory()->create([
            'description' => null,
            'category' => null,
        ]);

        $this->assertNull($income->description);
        $this->assertNull($income->category);
    }
}