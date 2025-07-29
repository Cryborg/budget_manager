<?php

namespace Tests\Feature\Security;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Income;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SimpleSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_global_scope_bank(): void
    {
        // Créer deux utilisateurs
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        // Se connecter en tant qu'Alice et créer une banque
        Auth::login($userA);
        $bankA = Bank::create([
            'name' => 'Bank Alice',
            'code' => 'BA01',
            'color' => '#ff0000',
        ]);
        Auth::logout();

        // Se connecter en tant que Bob et créer une banque
        Auth::login($userB);
        $bankB = Bank::create([
            'name' => 'Bank Bob',
            'code' => 'BB01',
            'color' => '#00ff00',
        ]);
        Auth::logout();

        // Test: Alice ne doit voir que sa banque
        Auth::login($userA);
        $aliceBanks = Bank::all();
        $this->assertCount(1, $aliceBanks, 'Alice should see only 1 bank');
        $this->assertEquals('Bank Alice', $aliceBanks->first()->name);
        Auth::logout();

        // Test: Bob ne doit voir que sa banque
        Auth::login($userB);
        $bobBanks = Bank::all();
        $this->assertCount(1, $bobBanks, 'Bob should see only 1 bank');
        $this->assertEquals('Bank Bob', $bobBanks->first()->name);
        Auth::logout();

        // Test: Aucun utilisateur connecté = aucune donnée
        $noUserBanks = Bank::all();
        $this->assertCount(0, $noUserBanks, 'No user should see 0 banks');
    }

    public function test_debug_global_scope_income(): void
    {
        // Créer utilisateurs et comptes
        $userA = User::factory()->create(['name' => 'Alice']);
        $userB = User::factory()->create(['name' => 'Bob']);

        Auth::login($userA);
        $bankA = Bank::create(['name' => 'Bank A', 'code' => 'BA', 'color' => '#f00']);
        $accountA = BankAccount::create([
            'bank_id' => $bankA->id,
            'name' => 'Account A',
            'type' => 'current',
            'current_balance' => 0,
            'initial_balance' => 0,
        ]);
        $incomeA = Income::create([
            'bank_account_id' => $accountA->id,
            'name' => 'Salary Alice',
            'amount' => 1000,
            'date' => now(),
            'frequency' => 'monthly',
        ]);
        Auth::logout();

        Auth::login($userB);
        $bankB = Bank::create(['name' => 'Bank B', 'code' => 'BB', 'color' => '#0f0']);
        $accountB = BankAccount::create([
            'bank_id' => $bankB->id,
            'name' => 'Account B',
            'type' => 'current',
            'current_balance' => 0,
            'initial_balance' => 0,
        ]);
        $incomeB = Income::create([
            'bank_account_id' => $accountB->id,
            'name' => 'Salary Bob',
            'amount' => 2000,
            'date' => now(),
            'frequency' => 'monthly',
        ]);
        Auth::logout();

        // Test: Alice ne doit voir que ses revenus
        Auth::login($userA);
        $aliceIncomes = Income::all();
        $this->assertCount(1, $aliceIncomes, 'Alice should see only 1 income');
        $this->assertEquals('Salary Alice', $aliceIncomes->first()->name);
        Auth::logout();

        // Test: Bob ne doit voir que ses revenus
        Auth::login($userB);
        $bobIncomes = Income::all();
        $this->assertCount(1, $bobIncomes, 'Bob should see only 1 income');
        $this->assertEquals('Salary Bob', $bobIncomes->first()->name);
    }
}
