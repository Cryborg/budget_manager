<?php

namespace Tests\Feature\Security;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DataIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private Bank $bankA;
    private Bank $bankB;
    private BankAccount $accountA;
    private BankAccount $accountB;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer deux utilisateurs distincts
        $this->userA = User::factory()->create([
            'name' => 'Alice Hacker',
            'email' => 'alice@test.com'
        ]);
        
        $this->userB = User::factory()->create([
            'name' => 'Bob Victim',
            'email' => 'bob@test.com'
        ]);
        
        // Créer des données pour l'utilisateur A en utilisant les Factory correctement
        $this->bankA = Bank::factory()->create([
            'user_id' => $this->userA->id,
            'name' => 'Bank Alice'
        ]);
        $this->accountA = BankAccount::factory()->create([
            'user_id' => $this->userA->id,
            'bank_id' => $this->bankA->id,
            'name' => 'Account Alice'
        ]);
        
        // Créer des données pour l'utilisateur B
        $this->bankB = Bank::factory()->create([
            'user_id' => $this->userB->id,
            'name' => 'Bank Bob'
        ]);
        $this->accountB = BankAccount::factory()->create([
            'user_id' => $this->userB->id,
            'bank_id' => $this->bankB->id,
            'name' => 'Account Bob'
        ]);
    }

    public function test_user_cannot_see_other_users_banks(): void
    {
        // Alice se connecte et essaie d'accéder aux banques
        Auth::login($this->userA);
        
        $visibleBanks = Bank::all();
        
        // Alice ne doit voir que sa banque
        $this->assertCount(1, $visibleBanks);
        $this->assertEquals('Bank Alice', $visibleBanks->first()->name);
        $this->assertNotContains($this->bankB->id, $visibleBanks->pluck('id'));
        
        Auth::logout();
        
        // Bob se connecte et essaie d'accéder aux banques
        Auth::login($this->userB);
        
        $visibleBanks = Bank::all();
        
        // Bob ne doit voir que sa banque
        $this->assertCount(1, $visibleBanks);
        $this->assertEquals('Bank Bob', $visibleBanks->first()->name);
        $this->assertNotContains($this->bankA->id, $visibleBanks->pluck('id'));
    }

    public function test_user_cannot_see_other_users_bank_accounts(): void
    {
        // Alice se connecte
        Auth::login($this->userA);
        
        $visibleAccounts = BankAccount::all();
        
        // Alice ne doit voir que son compte
        $this->assertCount(1, $visibleAccounts);
        $this->assertEquals('Account Alice', $visibleAccounts->first()->name);
        $this->assertNotContains($this->accountB->id, $visibleAccounts->pluck('id'));
        
        Auth::logout();
        
        // Bob se connecte
        Auth::login($this->userB);
        
        $visibleAccounts = BankAccount::all();
        
        // Bob ne doit voir que son compte
        $this->assertCount(1, $visibleAccounts);
        $this->assertEquals('Account Bob', $visibleAccounts->first()->name);
        $this->assertNotContains($this->accountA->id, $visibleAccounts->pluck('id'));
    }

    public function test_user_cannot_find_other_users_bank_by_id(): void
    {
        // Alice essaie de récupérer la banque de Bob par son ID
        Auth::login($this->userA);
        
        $foundBank = Bank::find($this->bankB->id);
        
        // Alice ne doit pas pouvoir récupérer la banque de Bob
        $this->assertNull($foundBank);
        
        Auth::logout();
        
        // Bob essaie de récupérer la banque d'Alice par son ID
        Auth::login($this->userB);
        
        $foundBank = Bank::find($this->bankA->id);
        
        // Bob ne doit pas pouvoir récupérer la banque d'Alice
        $this->assertNull($foundBank);
    }

    public function test_user_cannot_find_other_users_bank_account_by_id(): void
    {
        // Alice essaie de récupérer le compte de Bob par son ID
        Auth::login($this->userA);
        
        $foundAccount = BankAccount::find($this->accountB->id);
        
        // Alice ne doit pas pouvoir récupérer le compte de Bob
        $this->assertNull($foundAccount);
        
        Auth::logout();
        
        // Bob essaie de récupérer le compte d'Alice par son ID
        Auth::login($this->userB);
        
        $foundAccount = BankAccount::find($this->accountA->id);
        
        // Bob ne doit pas pouvoir récupérer le compte d'Alice
        $this->assertNull($foundAccount);
    }

    public function test_incomes_are_isolated_between_users(): void
    {
        // Créer des revenus pour chaque utilisateur
        $incomeA = Income::factory()->create([
            'bank_account_id' => $this->accountA->id,
            'name' => 'Income Alice'
        ]);
        
        $incomeB = Income::factory()->create([
            'bank_account_id' => $this->accountB->id,
            'name' => 'Income Bob'
        ]);
        
        // Alice ne doit voir que ses revenus
        Auth::login($this->userA);
        $visibleIncomes = Income::all();
        $this->assertCount(1, $visibleIncomes);
        $this->assertEquals('Income Alice', $visibleIncomes->first()->name);
        Auth::logout();
        
        // Bob ne doit voir que ses revenus
        Auth::login($this->userB);
        $visibleIncomes = Income::all();
        $this->assertCount(1, $visibleIncomes);
        $this->assertEquals('Income Bob', $visibleIncomes->first()->name);
    }

    public function test_expenses_are_isolated_between_users(): void
    {
        // Créer des dépenses pour chaque utilisateur
        $expenseA = Expense::factory()->create([
            'bank_account_id' => $this->accountA->id,
            'name' => 'Expense Alice'
        ]);
        
        $expenseB = Expense::factory()->create([
            'bank_account_id' => $this->accountB->id,
            'name' => 'Expense Bob'
        ]);
        
        // Alice ne doit voir que ses dépenses
        Auth::login($this->userA);
        $visibleExpenses = Expense::all();
        $this->assertCount(1, $visibleExpenses);
        $this->assertEquals('Expense Alice', $visibleExpenses->first()->name);
        Auth::logout();
        
        // Bob ne doit voir que ses dépenses
        Auth::login($this->userB);
        $visibleExpenses = Expense::all();
        $this->assertCount(1, $visibleExpenses);
        $this->assertEquals('Expense Bob', $visibleExpenses->first()->name);
    }

    public function test_transfers_are_isolated_between_users(): void
    {
        // Créer un deuxième compte pour chaque utilisateur pour les virements
        $accountA2 = BankAccount::factory()->create([
            'user_id' => $this->userA->id,
            'bank_id' => $this->bankA->id,
            'name' => 'Account Alice 2'
        ]);
        $transferA = Transfer::factory()->create([
            'from_account_id' => $this->accountA->id,
            'to_account_id' => $accountA2->id,
            'name' => 'Transfer Alice'
        ]);
        
        $accountB2 = BankAccount::factory()->create([
            'user_id' => $this->userB->id,
            'bank_id' => $this->bankB->id,
            'name' => 'Account Bob 2'
        ]);
        $transferB = Transfer::factory()->create([
            'from_account_id' => $this->accountB->id,
            'to_account_id' => $accountB2->id,
            'name' => 'Transfer Bob'
        ]);
        
        // Alice ne doit voir que ses virements
        Auth::login($this->userA);
        $visibleTransfers = Transfer::all();
        $this->assertCount(1, $visibleTransfers);
        $this->assertEquals('Transfer Alice', $visibleTransfers->first()->name);
        Auth::logout();
        
        // Bob ne doit voir que ses virements
        Auth::login($this->userB);
        $visibleTransfers = Transfer::all();
        $this->assertCount(1, $visibleTransfers);
        $this->assertEquals('Transfer Bob', $visibleTransfers->first()->name);
    }

    public function test_user_cannot_create_account_on_other_users_bank(): void
    {
        // Alice essaie de créer un compte sur la banque de Bob
        Auth::login($this->userA);
        
        // Tenter de créer un compte avec l'ID de la banque de Bob
        $account = new BankAccount([
            'bank_id' => $this->bankB->id, // ID de la banque de Bob !
            'name' => 'Malicious Account',
            'type' => 'current',
            'current_balance' => 1000.00,
            'initial_balance' => 1000.00,
            'is_active' => true
        ]);
        
        // Sauvegarder devrait soit échouer, soit auto-assigner user_id à Alice
        $account->save();
        
        // Vérifier que le compte créé appartient bien à Alice, pas à Bob
        $this->assertEquals($this->userA->id, $account->user_id);
        
        // Bob ne doit pas voir ce compte
        Auth::logout();
        Auth::login($this->userB);
        
        $bobAccounts = BankAccount::all();
        $this->assertNotContains($account->id, $bobAccounts->pluck('id'));
        Auth::logout();
    }

    public function test_unauthenticated_user_sees_no_data(): void
    {
        // S'assurer qu'aucun utilisateur n'est connecté
        Auth::logout();
        
        // Aucune donnée ne doit être visible sans authentification
        $this->assertCount(0, Bank::all());
        $this->assertCount(0, BankAccount::all());
        $this->assertCount(0, Income::all());
        $this->assertCount(0, Expense::all());
        $this->assertCount(0, Transfer::all());
    }
}