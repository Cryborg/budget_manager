<?php

namespace Tests\Feature\Security;

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

class GlobalScopeSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userA = User::factory()->create(['name' => 'User A']);
        $this->userB = User::factory()->create(['name' => 'User B']);
    }

    public function test_all_models_with_global_scopes_are_properly_isolated(): void
    {
        // Test pour Bank - a un global scope direct
        Auth::login($this->userA);
        $bankA = Bank::factory()->create(['name' => 'Bank A']);
        Auth::logout();
        
        Auth::login($this->userB);
        $bankB = Bank::factory()->create(['name' => 'Bank B']);
        Auth::logout();
        
        // Vérification Bank
        Auth::login($this->userA);
        $this->assertCount(1, Bank::all());
        $this->assertEquals('Bank A', Bank::first()->name);
        Auth::logout();
        
        Auth::login($this->userB);
        $this->assertCount(1, Bank::all());
        $this->assertEquals('Bank B', Bank::first()->name);
        Auth::logout();
        
        // Test pour BankAccount - a un global scope direct
        Auth::login($this->userA);
        $accountA = BankAccount::factory()->create([
            'bank_id' => $bankA->id,
            'name' => 'Account A'
        ]);
        Auth::logout();
        
        Auth::login($this->userB);
        $accountB = BankAccount::factory()->create([
            'bank_id' => $bankB->id,
            'name' => 'Account B'
        ]);
        Auth::logout();
        
        // Vérification BankAccount
        Auth::login($this->userA);
        $this->assertCount(1, BankAccount::all());
        $this->assertEquals('Account A', BankAccount::first()->name);
        Auth::logout();
        
        Auth::login($this->userB);
        $this->assertCount(1, BankAccount::all());
        $this->assertEquals('Account B', BankAccount::first()->name);
    }

    public function test_models_without_direct_global_scopes_inherit_security_through_relationships(): void
    {
        // Créer des données de base
        Auth::login($this->userA);
        $bankA = Bank::factory()->create();
        $accountA = BankAccount::factory()->create(['bank_id' => $bankA->id]);
        Auth::logout();
        
        Auth::login($this->userB);
        $bankB = Bank::factory()->create();
        $accountB = BankAccount::factory()->create(['bank_id' => $bankB->id]);
        Auth::logout();
        
        // Test Income - sécurisé via relation avec BankAccount
        Auth::login($this->userA);
        $incomeA = Income::factory()->create([
            'bank_account_id' => $accountA->id,
            'name' => 'Income A'
        ]);
        Auth::logout();
        
        Auth::login($this->userB);
        $incomeB = Income::factory()->create([
            'bank_account_id' => $accountB->id,
            'name' => 'Income B'
        ]);
        Auth::logout();
        
        // Vérifier que chaque utilisateur ne voit que ses revenus
        // (via les relations avec les comptes qui ont des global scopes)
        Auth::login($this->userA);
        $userAIncomes = Income::whereHas('bankAccount')->get();
        $this->assertCount(1, $userAIncomes);
        $this->assertEquals('Income A', $userAIncomes->first()->name);
        Auth::logout();
        
        Auth::login($this->userB);
        $userBIncomes = Income::whereHas('bankAccount')->get();
        $this->assertCount(1, $userBIncomes);
        $this->assertEquals('Income B', $userBIncomes->first()->name);
        Auth::logout();
        
        // Test Expense - sécurisé via relation avec BankAccount
        Auth::login($this->userA);
        $expenseA = Expense::factory()->create([
            'bank_account_id' => $accountA->id,
            'name' => 'Expense A'
        ]);
        Auth::logout();
        
        Auth::login($this->userB);
        $expenseB = Expense::factory()->create([
            'bank_account_id' => $accountB->id,
            'name' => 'Expense B'
        ]);
        Auth::logout();
        
        // Vérifier la sécurité des dépenses
        Auth::login($this->userA);
        $userAExpenses = Expense::whereHas('bankAccount')->get();
        $this->assertCount(1, $userAExpenses);
        $this->assertEquals('Expense A', $userAExpenses->first()->name);
        Auth::logout();
        
        Auth::login($this->userB);
        $userBExpenses = Expense::whereHas('bankAccount')->get();
        $this->assertCount(1, $userBExpenses);
        $this->assertEquals('Expense B', $userBExpenses->first()->name);
    }

    public function test_transfer_security_with_both_accounts_belonging_to_same_user(): void
    {
        // Créer des comptes pour chaque utilisateur
        Auth::login($this->userA);
        $bankA = Bank::factory()->create();
        $accountA1 = BankAccount::factory()->create(['bank_id' => $bankA->id, 'name' => 'Account A1']);
        $accountA2 = BankAccount::factory()->create(['bank_id' => $bankA->id, 'name' => 'Account A2']);
        Auth::logout();
        
        Auth::login($this->userB);
        $bankB = Bank::factory()->create();
        $accountB1 = BankAccount::factory()->create(['bank_id' => $bankB->id, 'name' => 'Account B1']);
        $accountB2 = BankAccount::factory()->create(['bank_id' => $bankB->id, 'name' => 'Account B2']);
        Auth::logout();
        
        // Créer des virements pour chaque utilisateur
        Auth::login($this->userA);
        $transferA = Transfer::factory()->create([
            'from_account_id' => $accountA1->id,
            'to_account_id' => $accountA2->id,
            'name' => 'Transfer A'
        ]);
        Auth::logout();
        
        Auth::login($this->userB);
        $transferB = Transfer::factory()->create([
            'from_account_id' => $accountB1->id,
            'to_account_id' => $accountB2->id,
            'name' => 'Transfer B'
        ]);
        Auth::logout();
        
        // Vérifier que chaque utilisateur ne voit que ses virements
        Auth::login($this->userA);
        $userATransfers = Transfer::whereHas('fromAccount')
            ->orWhereHas('toAccount')
            ->get();
        $this->assertCount(1, $userATransfers);
        $this->assertEquals('Transfer A', $userATransfers->first()->name);
        Auth::logout();
        
        Auth::login($this->userB);
        $userBTransfers = Transfer::whereHas('fromAccount')
            ->orWhereHas('toAccount')
            ->get();
        $this->assertCount(1, $userBTransfers);
        $this->assertEquals('Transfer B', $userBTransfers->first()->name);
    }

    public function test_balance_adjustment_security(): void
    {
        // Créer des comptes pour chaque utilisateur
        Auth::login($this->userA);
        $bankA = Bank::factory()->create();
        $accountA = BankAccount::factory()->create(['bank_id' => $bankA->id]);
        Auth::logout();
        
        Auth::login($this->userB);
        $bankB = Bank::factory()->create();
        $accountB = BankAccount::factory()->create(['bank_id' => $bankB->id]);
        Auth::logout();
        
        // Créer des ajustements pour chaque utilisateur
        Auth::login($this->userA);
        $adjustmentA = BalanceAdjustment::factory()->create([
            'bank_account_id' => $accountA->id,
            'description' => 'Adjustment A'
        ]);
        Auth::logout();
        
        Auth::login($this->userB);
        $adjustmentB = BalanceAdjustment::factory()->create([
            'bank_account_id' => $accountB->id,
            'description' => 'Adjustment B'
        ]);
        Auth::logout();
        
        // Vérifier que chaque utilisateur ne voit que ses ajustements
        Auth::login($this->userA);
        $userAAdjustments = BalanceAdjustment::whereHas('bankAccount')->get();
        $this->assertCount(1, $userAAdjustments);
        $this->assertEquals('Adjustment A', $userAAdjustments->first()->description);
        Auth::logout();
        
        Auth::login($this->userB);
        $userBAdjustments = BalanceAdjustment::whereHas('bankAccount')->get();
        $this->assertCount(1, $userBAdjustments);
        $this->assertEquals('Adjustment B', $userBAdjustments->first()->description);
    }

    public function test_cross_user_data_access_is_impossible(): void
    {
        // Créer des données complètes pour chaque utilisateur
        Auth::login($this->userA);
        $bankA = Bank::factory()->create(['name' => 'Bank A']);
        $accountA = BankAccount::factory()->create(['bank_id' => $bankA->id, 'name' => 'Account A']);
        $incomeA = Income::factory()->create(['bank_account_id' => $accountA->id, 'name' => 'Income A']);
        $expenseA = Expense::factory()->create(['bank_account_id' => $accountA->id, 'name' => 'Expense A']);
        Auth::logout();
        
        Auth::login($this->userB);
        $bankB = Bank::factory()->create(['name' => 'Bank B']);
        $accountB = BankAccount::factory()->create(['bank_id' => $bankB->id, 'name' => 'Account B']);
        $incomeB = Income::factory()->create(['bank_account_id' => $accountB->id, 'name' => 'Income B']);
        $expenseB = Expense::factory()->create(['bank_account_id' => $accountB->id, 'name' => 'Expense B']);
        Auth::logout();
        
        // User A tente d'accéder aux données de User B par différents moyens
        Auth::login($this->userA);
        
        // Tentative d'accès direct par ID
        $this->assertNull(Bank::find($bankB->id));
        $this->assertNull(BankAccount::find($accountB->id));
        
        // Tentative d'accès via les relations
        $this->assertEmpty(Income::where('bank_account_id', $accountB->id)->get());
        $this->assertEmpty(Expense::where('bank_account_id', $accountB->id)->get());
        
        // Vérifier que User A ne voit que ses propres données
        $this->assertCount(1, Bank::all());
        $this->assertCount(1, BankAccount::all());
        $this->assertEquals('Bank A', Bank::first()->name);
        $this->assertEquals('Account A', BankAccount::first()->name);
        
        Auth::logout();
        
        // Même test pour User B
        Auth::login($this->userB);
        
        $this->assertNull(Bank::find($bankA->id));
        $this->assertNull(BankAccount::find($accountA->id));
        $this->assertEmpty(Income::where('bank_account_id', $accountA->id)->get());
        $this->assertEmpty(Expense::where('bank_account_id', $accountA->id)->get());
        
        $this->assertCount(1, Bank::all());
        $this->assertCount(1, BankAccount::all());
        $this->assertEquals('Bank B', Bank::first()->name);
        $this->assertEquals('Account B', BankAccount::first()->name);
    }

    public function test_global_scope_works_with_query_builder_methods(): void
    {
        // Créer des données pour deux utilisateurs
        Auth::login($this->userA);
        $bankA = Bank::factory()->create(['name' => 'Test Bank A', 'code' => 'TESTA']);
        Auth::logout();
        
        Auth::login($this->userB);
        $bankB = Bank::factory()->create(['name' => 'Test Bank B', 'code' => 'TESTB']);
        Auth::logout();
        
        // Tester différentes méthodes de query avec User A
        Auth::login($this->userA);
        
        // where()
        $this->assertCount(1, Bank::where('name', 'like', '%Test%')->get());
        $this->assertEquals('Test Bank A', Bank::where('name', 'like', '%Test%')->first()->name);
        
        // first()
        $this->assertEquals('Test Bank A', Bank::first()->name);
        
        // pluck()
        $names = Bank::pluck('name');
        $this->assertCount(1, $names);
        $this->assertEquals('Test Bank A', $names->first());
        
        // count()
        $this->assertEquals(1, Bank::count());
        
        Auth::logout();
        
        // Même test avec User B
        Auth::login($this->userB);
        
        $this->assertCount(1, Bank::where('name', 'like', '%Test%')->get());
        $this->assertEquals('Test Bank B', Bank::where('name', 'like', '%Test%')->first()->name);
        $this->assertEquals('Test Bank B', Bank::first()->name);
        $this->assertEquals(1, Bank::count());
        
        $names = Bank::pluck('name');
        $this->assertEquals('Test Bank B', $names->first());
    }
}