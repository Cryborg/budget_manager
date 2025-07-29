<?php

namespace Tests\Unit\Models;

use App\Models\BalanceAdjustment;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class BankAccountTest extends TestCase
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

    public function test_bank_account_belongs_to_bank(): void
    {
        $bank = Bank::factory()->create(['user_id' => $this->user->id]);
        $account = BankAccount::factory()->create([
            'user_id' => $this->user->id,
            'bank_id' => $bank->id,
        ]);

        $this->assertInstanceOf(Bank::class, $account->bank);
        $this->assertEquals($bank->id, $account->bank->id);
    }

    public function test_bank_account_has_many_incomes(): void
    {
        $account = BankAccount::factory()->create(['user_id' => $this->user->id]);
        $income = Income::factory()->create(['bank_account_id' => $account->id]);

        $this->assertCount(1, $account->incomes);
        $this->assertInstanceOf(Income::class, $account->incomes->first());
        $this->assertEquals($income->id, $account->incomes->first()->id);
    }

    public function test_bank_account_has_many_expenses(): void
    {
        $account = BankAccount::factory()->create(['user_id' => $this->user->id]);
        $expense = Expense::factory()->create(['bank_account_id' => $account->id]);

        $this->assertCount(1, $account->expenses);
        $this->assertInstanceOf(Expense::class, $account->expenses->first());
        $this->assertEquals($expense->id, $account->expenses->first()->id);
    }

    public function test_bank_account_has_many_from_transfers(): void
    {
        $fromAccount = BankAccount::factory()->create(['user_id' => $this->user->id]);
        $toAccount = BankAccount::factory()->create(['user_id' => $this->user->id]);
        $transfer = Transfer::factory()->create([
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
        ]);

        $this->assertCount(1, $fromAccount->transfersFrom);
        $this->assertInstanceOf(Transfer::class, $fromAccount->transfersFrom->first());
        $this->assertEquals($transfer->id, $fromAccount->transfersFrom->first()->id);
    }

    public function test_bank_account_has_many_to_transfers(): void
    {
        $fromAccount = BankAccount::factory()->create(['user_id' => $this->user->id]);
        $toAccount = BankAccount::factory()->create(['user_id' => $this->user->id]);
        $transfer = Transfer::factory()->create([
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
        ]);

        $this->assertCount(1, $toAccount->transfersTo);
        $this->assertInstanceOf(Transfer::class, $toAccount->transfersTo->first());
        $this->assertEquals($transfer->id, $toAccount->transfersTo->first()->id);
    }

    public function test_bank_account_has_many_balance_adjustments(): void
    {
        $account = BankAccount::factory()->create(['user_id' => $this->user->id]);
        $adjustment = BalanceAdjustment::factory()->create(['bank_account_id' => $account->id]);

        $this->assertCount(1, $account->balanceAdjustments);
        $this->assertInstanceOf(BalanceAdjustment::class, $account->balanceAdjustments->first());
        $this->assertEquals($adjustment->id, $account->balanceAdjustments->first()->id);
    }

    public function test_bank_account_casts_attributes_correctly(): void
    {
        $account = BankAccount::factory()->create([
            'current_balance' => 1234.56,
            'is_active' => true,
        ]);

        $this->assertIsFloat($account->current_balance);
        $this->assertEquals(1234.56, $account->current_balance);
        $this->assertIsBool($account->is_active);
        $this->assertTrue($account->is_active);
    }

    public function test_bank_account_fillable_attributes(): void
    {
        $attributes = [
            'bank_id' => 1,
            'name' => 'Test Account',
            'type' => 'current',
            'current_balance' => 100.00,
            'is_active' => true,
        ];

        $account = new BankAccount($attributes);

        $this->assertEquals($attributes['bank_id'], $account->bank_id);
        $this->assertEquals($attributes['name'], $account->name);
        $this->assertEquals($attributes['type'], $account->type);
        $this->assertEquals($attributes['current_balance'], $account->current_balance);
        $this->assertEquals($attributes['is_active'], $account->is_active);
    }
}
