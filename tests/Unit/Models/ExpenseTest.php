<?php

namespace Tests\Unit\Models;

use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer et connecter un utilisateur pour les tests
        $this->user = User::factory()->create();
        Auth::login($this->user);
    }

    public function test_expense_belongs_to_bank_account(): void
    {
        $account = BankAccount::factory()->create(['user_id' => $this->user->id]);
        $expense = Expense::factory()->create(['bank_account_id' => $account->id]);

        $this->assertInstanceOf(BankAccount::class, $expense->bankAccount);
        $this->assertEquals($account->id, $expense->bankAccount->id);
    }

    public function test_expense_casts_attributes_correctly(): void
    {
        $expense = Expense::factory()->create([
            'amount' => 123.45,
            'date' => '2024-01-15',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'is_active' => true,
        ]);

        $this->assertIsFloat($expense->amount);
        $this->assertEquals(123.45, $expense->amount);
        $this->assertInstanceOf(Carbon::class, $expense->date);
        $this->assertInstanceOf(Carbon::class, $expense->start_date);
        $this->assertInstanceOf(Carbon::class, $expense->end_date);
        $this->assertIsBool($expense->is_active);
        $this->assertTrue($expense->is_active);
    }

    public function test_expense_fillable_attributes(): void
    {
        $attributes = [
            'bank_account_id' => 1,
            'name' => 'Loyer',
            'description' => 'Loyer appartement',
            'amount' => 800.00,
            'date' => '2024-01-01',
            'frequency' => 'monthly',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'category' => 'Logement',
            'is_active' => true,
        ];

        $expense = new Expense($attributes);

        $this->assertEquals($attributes['bank_account_id'], $expense->bank_account_id);
        $this->assertEquals($attributes['name'], $expense->name);
        $this->assertEquals($attributes['description'], $expense->description);
        $this->assertEquals($attributes['amount'], $expense->amount);
        $this->assertEquals($attributes['frequency'], $expense->frequency);
        $this->assertEquals($attributes['category'], $expense->category);
        $this->assertEquals($attributes['is_active'], $expense->is_active);
    }

    public function test_expense_factory_states(): void
    {
        $onceExpense = Expense::factory()->once()->create();
        $this->assertEquals('once', $onceExpense->frequency);
        $this->assertNull($onceExpense->start_date);
        $this->assertNull($onceExpense->end_date);

        $monthlyExpense = Expense::factory()->monthly()->create();
        $this->assertEquals('monthly', $monthlyExpense->frequency);

        $yearlyExpense = Expense::factory()->yearly()->create();
        $this->assertEquals('yearly', $yearlyExpense->frequency);

        $activeExpense = Expense::factory()->active()->create();
        $this->assertTrue($activeExpense->is_active);

        $inactiveExpense = Expense::factory()->inactive()->create();
        $this->assertFalse($inactiveExpense->is_active);
    }

    public function test_expense_can_have_null_dates_for_once_frequency(): void
    {
        $expense = Expense::factory()->once()->create();

        $this->assertEquals('once', $expense->frequency);
        $this->assertNull($expense->start_date);
        $this->assertNull($expense->end_date);
    }

    public function test_expense_can_have_optional_description_and_category(): void
    {
        $expense = Expense::factory()->create([
            'description' => null,
            'category' => null,
        ]);

        $this->assertNull($expense->description);
        $this->assertNull($expense->category);
    }
}
