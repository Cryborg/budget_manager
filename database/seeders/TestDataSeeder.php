<?php

namespace Database\Seeders;

use App\Models\BalanceAdjustment;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeder pour créer des données de test.
     */
    public function run(): void
    {
        // Créer un utilisateur de test
        $testUser = User::firstOrCreate(
            ['email' => 'test@budget.local'],
            [
                'name' => 'Marie Test',
                'password' => bcrypt('password'),
            ]
        );

        // Se connecter en tant qu'utilisateur de test pour que les global scopes fonctionnent
        Auth::login($testUser);

        // Créer des banques fictives
        $creditAgricole = Bank::create([
            'name' => 'Crédit Agricole',
            'code' => 'CA',
            'color' => '#00B894',
        ]);

        $lcl = Bank::create([
            'name' => 'LCL',
            'code' => 'LCL',
            'color' => '#0066CC',
        ]);

        // Créer des comptes fictifs
        $caCompte = BankAccount::create([
            'bank_id' => $creditAgricole->id,
            'name' => 'Compte Principal',
            'type' => 'current',
            'current_balance' => 2500.00,
            'initial_balance' => 2000.00,
        ]);

        $caLivretA = BankAccount::create([
            'bank_id' => $creditAgricole->id,
            'name' => 'Livret A',
            'type' => 'savings',
            'current_balance' => 5000.00,
            'initial_balance' => 4500.00,
        ]);

        $lclCompte = BankAccount::create([
            'bank_id' => $lcl->id,
            'name' => 'Compte Courant LCL',
            'type' => 'current',
            'current_balance' => 800.00,
            'initial_balance' => 500.00,
        ]);

        // Créer des revenus fictifs
        Income::create([
            'bank_account_id' => $caCompte->id,
            'name' => 'Salaire Marie',
            'description' => 'Salaire mensuel',
            'amount' => 2800.00,
            'date' => Carbon::now()->startOfMonth(),
            'frequency' => 'monthly',
            'start_date' => Carbon::now()->startOfMonth(),
            'category' => 'Salaire',
        ]);

        Income::create([
            'bank_account_id' => $caCompte->id,
            'name' => 'Freelance',
            'description' => 'Missions ponctuelles',
            'amount' => 500.00,
            'date' => Carbon::now()->startOfMonth(),
            'frequency' => 'monthly',
            'start_date' => Carbon::now()->startOfMonth(),
            'category' => 'Activité',
        ]);

        Income::create([
            'bank_account_id' => $caLivretA->id,
            'name' => 'Intérêts Livret A',
            'description' => 'Intérêts annuels',
            'amount' => 150.00,
            'date' => Carbon::now()->startOfYear(),
            'frequency' => 'yearly',
            'start_date' => Carbon::now()->startOfYear(),
            'category' => 'Épargne',
        ]);

        // Créer des dépenses fictives
        $expensesTest = [
            ['account' => $caCompte->id, 'name' => 'Loyer', 'amount' => 850.00, 'category' => 'Logement'],
            ['account' => $caCompte->id, 'name' => 'Assurance auto', 'amount' => 45.00, 'category' => 'Transport'],
            ['account' => $caCompte->id, 'name' => 'Téléphone', 'amount' => 25.00, 'category' => 'Communication'],
            ['account' => $caCompte->id, 'name' => 'Internet', 'amount' => 35.00, 'category' => 'Communication'],
            ['account' => $caCompte->id, 'name' => 'Électricité', 'amount' => 65.00, 'category' => 'Énergie'],
            ['account' => $caCompte->id, 'name' => 'Courses', 'amount' => 280.00, 'category' => 'Alimentation'],
            ['account' => $lclCompte->id, 'name' => 'Essence', 'amount' => 120.00, 'category' => 'Transport'],
            ['account' => $lclCompte->id, 'name' => 'Restaurant', 'amount' => 150.00, 'category' => 'Loisirs'],
        ];

        foreach ($expensesTest as $expense) {
            Expense::create([
                'bank_account_id' => $expense['account'],
                'name' => $expense['name'],
                'amount' => $expense['amount'],
                'date' => Carbon::now()->startOfMonth(),
                'frequency' => 'monthly',
                'start_date' => Carbon::now()->startOfMonth(),
                'category' => $expense['category'],
            ]);
        }

        // Quelques dépenses ponctuelles
        Expense::create([
            'bank_account_id' => $caCompte->id,
            'name' => 'Réparation voiture',
            'description' => 'Révision annuelle',
            'amount' => 450.00,
            'date' => Carbon::now()->addDays(10),
            'frequency' => 'once',
            'category' => 'Transport',
        ]);

        Expense::create([
            'bank_account_id' => $caCompte->id,
            'name' => 'Vacances',
            'description' => 'Week-end en amoureux',
            'amount' => 600.00,
            'date' => Carbon::now()->addDays(15),
            'frequency' => 'once',
            'category' => 'Loisirs',
        ]);

        // Créer des virements fictifs
        Transfer::create([
            'from_account_id' => $caCompte->id,
            'to_account_id' => $caLivretA->id,
            'name' => 'Épargne mensuelle',
            'description' => 'Virement automatique vers épargne',
            'amount' => 300.00,
            'date' => Carbon::now()->startOfMonth(),
            'frequency' => 'monthly',
            'start_date' => Carbon::now()->startOfMonth(),
        ]);

        Transfer::create([
            'from_account_id' => $caCompte->id,
            'to_account_id' => $lclCompte->id,
            'name' => 'Frais courants',
            'description' => 'Alimentation du compte LCL',
            'amount' => 200.00,
            'date' => Carbon::now()->startOfMonth(),
            'frequency' => 'monthly',
            'start_date' => Carbon::now()->startOfMonth(),
        ]);

        // Ajouter un ajustement de solde pour exemple
        BalanceAdjustment::create([
            'bank_account_id' => $caCompte->id,
            'actual_balance' => 2600.00,
            'adjustment_date' => Carbon::now()->addMonth(),
            'description' => 'Correction après vérification bancaire',
        ]);

        // Se déconnecter après les seeds
        Auth::logout();
    }
}