<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // Créer l'utilisateur principal Franck s'il n'existe pas
        $franck = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL')],
            [
                'name' => env('ADMIN_NAME'),
                'password' => bcrypt(env('ADMIN_PASSWORD')),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        // Se connecter en tant que Franck pour que les global scopes fonctionnent
        Auth::login($franck);
        // Création des banques
        $bnp = Bank::create([
            'name' => 'BNP Paribas',
            'code' => 'BNP',
            'color' => '#00A651',
        ]);

        $boursobank = Bank::create([
            'name' => 'Boursobank',
            'code' => 'BOURSO',
            'color' => '#FF6900',
        ]);

        $natixis = Bank::create([
            'name' => 'Natixis',
            'code' => 'NATIXIS',
            'color' => '#FF0000',
        ]);

        // Création des comptes
        $bnpCourant = BankAccount::create([
            'bank_id' => $bnp->id,
            'name' => 'Compte courant BNP',
            'type' => 'current',
            'current_balance' => 5900.00, // Fin juillet à +5900€
            'initial_balance' => 0.00,
            'account_number' => null,
            'is_active' => true,
            'blocked_at' => null,
        ]);

        $boursoLivretA = BankAccount::create([
            'bank_id' => $boursobank->id,
            'name' => 'Livret A',
            'type' => 'savings',
            'current_balance' => 10.00,
            'initial_balance' => 10.00,
            'account_number' => null,
            'is_active' => true,
            'blocked_at' => null,
        ]);

        $boursoLDDS = BankAccount::create([
            'bank_id' => $boursobank->id,
            'name' => 'LDDS',
            'type' => 'savings',
            'current_balance' => 10.00,
            'initial_balance' => 10.00,
            'account_number' => null,
            'is_active' => true,
            'blocked_at' => null,
        ]);

        $boursoInternet = BankAccount::create([
            'bank_id' => $boursobank->id,
            'name' => 'Compte Boursobank',
            'type' => 'current',
            'current_balance' => 90.00,
            'initial_balance' => 0.00,
            'account_number' => null,
            'is_active' => true,
            'blocked_at' => null,
        ]);

        $natixisPEI = BankAccount::create([
            'bank_id' => $natixis->id,
            'name' => 'PEI',
            'type' => 'investment',
            'current_balance' => 621.67,
            'initial_balance' => 0.00,
            'account_number' => null,
            'is_active' => true,
            'blocked_at' => Carbon::parse('2028-06-01'),
        ]);

        $natixisPERCOLI = BankAccount::create([
            'bank_id' => $natixis->id,
            'name' => 'PERCOLI',
            'type' => 'investment',
            'current_balance' => 0.00,
            'initial_balance' => 0.00,
            'account_number' => null,
            'is_active' => true,
            'blocked_at' => Carbon::parse('2028-06-01'),
        ]);

        // Revenus
        Income::create([
            'bank_account_id' => $bnpCourant->id,
            'name' => 'Salaire',
            'description' => 'Paie mensuelle',
            'amount' => 3840.00,
            'date' => Carbon::now()->startOfMonth(),
            'frequency' => 'monthly',
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => null,
            'category' => 'Salaire',
            'is_active' => true,
        ]);

        Income::create([
            'bank_account_id' => $bnpCourant->id,
            'name' => 'Remboursement assurance Enora',
            'description' => 'Ma fille me rembourse son assurance voiture',
            'amount' => 36.00,
            'date' => Carbon::now()->startOfMonth(),
            'frequency' => 'monthly',
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => null,
            'category' => 'Remboursement',
            'is_active' => true,
        ]);

        Income::create([
            'bank_account_id' => $natixisPEI->id,
            'name' => 'Abondement Smice',
            'description' => 'Abondement annuel de l\'entreprise',
            'amount' => 500.00,
            'date' => Carbon::now()->startOfYear(),
            'frequency' => 'yearly',
            'start_date' => Carbon::now()->startOfYear(),
            'end_date' => null,
            'category' => 'Abondement',
            'is_active' => true,
        ]);

        // Dépenses fixes BNP
        $expensesBnp = [
            ['name' => 'Loyer', 'amount' => 1045.00, 'end_date' => null],
            ['name' => 'Pension', 'amount' => 611.92, 'end_date' => Carbon::parse('2032-01-01')],
            ['name' => 'Crédit Tesla', 'amount' => 393.00, 'end_date' => Carbon::parse('2030-09-01')],
            ['name' => 'Assurance maison', 'amount' => 23.00, 'end_date' => null],
            ['name' => 'Assurance Tesla', 'amount' => 51.00, 'end_date' => null],
            ['name' => 'Abonnement Tesla', 'amount' => 10.00, 'end_date' => null],
            ['name' => 'Keepcool (x2)', 'amount' => 50.00, 'end_date' => null],
            ['name' => 'Préfon', 'amount' => 52.50, 'end_date' => null],
        ];

        foreach ($expensesBnp as $expense) {
            Expense::create([
                'bank_account_id' => $bnpCourant->id,
                'name' => $expense['name'],
                'description' => null,
                'amount' => $expense['amount'],
                'date' => Carbon::now()->startOfMonth(),
                'frequency' => 'monthly',
                'start_date' => Carbon::now()->startOfMonth(),
                'end_date' => $expense['end_date'],
                'category' => 'Fixe',
                'is_active' => true,
            ]);
        }

        // Remboursement Papa séparé car il a des dates spéciales
        Expense::create([
            'bank_account_id' => $bnpCourant->id,
            'name' => 'Remboursement Papa',
            'description' => null,
            'amount' => 500.00,
            'date' => Carbon::parse('2025-08-05'),
            'frequency' => 'monthly',
            'start_date' => Carbon::parse('2025-08-05'),
            'end_date' => Carbon::parse('2026-04-01'),
            'category' => 'Fixe',
            'is_active' => true,
        ]);

        // Dépenses Boursobank
        $expensesBourso = [
            ['name' => 'Électricité', 'amount' => 80.00],
            ['name' => 'Gaz', 'amount' => 70.00],
            ['name' => 'Box internet', 'amount' => 30.00],
            ['name' => 'Téléphone', 'amount' => 10.00],
            ['name' => 'Assurance voiture Enora', 'amount' => 38.00],
        ];

        foreach ($expensesBourso as $expense) {
            Expense::create([
                'bank_account_id' => $boursoInternet->id,
                'name' => $expense['name'],
                'description' => null,
                'amount' => $expense['amount'],
                'date' => Carbon::now()->startOfMonth(),
                'frequency' => 'monthly',
                'start_date' => Carbon::now()->startOfMonth(),
                'end_date' => null,
                'category' => 'Fixe',
                'is_active' => true,
            ]);
        }

        // Dépenses variables
        Expense::create([
            'bank_account_id' => $bnpCourant->id,
            'name' => 'Recharge voiture',
            'description' => 'Recharge électrique Tesla',
            'amount' => 40.00,
            'date' => Carbon::now()->startOfMonth(),
            'frequency' => 'monthly',
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => null,
            'category' => 'Transport',
            'is_active' => true,
        ]);

        Expense::create([
            'bank_account_id' => $bnpCourant->id,
            'name' => 'Nourriture',
            'description' => 'Courses alimentaires',
            'amount' => 300.00,
            'date' => Carbon::now()->startOfMonth(),
            'frequency' => 'monthly',
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => null,
            'category' => 'Alimentation',
            'is_active' => true,
        ]);

        // Chèque dentiste ponctuel
        Expense::create([
            'bank_account_id' => $bnpCourant->id,
            'name' => 'Dentiste',
            'description' => 'Chèque dentiste',
            'amount' => 386.00,
            'date' => Carbon::now()->addDays(5), // Pour août
            'frequency' => 'once',
            'start_date' => null,
            'end_date' => null,
            'category' => 'Santé',
            'is_active' => true,
        ]);

        // Virements épargne mensuels à partir d'août
        Transfer::create([
            'from_account_id' => $bnpCourant->id,
            'to_account_id' => $boursoLDDS->id,
            'name' => 'Épargne LDDS',
            'description' => 'Virement mensuel vers LDDS',
            'amount' => 100.00,
            'date' => Carbon::parse('2024-08-01'),
            'frequency' => 'monthly',
            'start_date' => Carbon::parse('2024-08-01'),
            'end_date' => null,
            'is_active' => true,
        ]);

        Transfer::create([
            'from_account_id' => $bnpCourant->id,
            'to_account_id' => $boursoLivretA->id,
            'name' => 'Épargne Livret A',
            'description' => 'Virement mensuel vers Livret A',
            'amount' => 250.00,
            'date' => Carbon::parse('2024-08-01'),
            'frequency' => 'monthly',
            'start_date' => Carbon::parse('2024-08-01'),
            'end_date' => null,
            'is_active' => true,
        ]);

        Transfer::create([
            'from_account_id' => $bnpCourant->id,
            'to_account_id' => $boursoInternet->id,
            'name' => 'Transfert',
            'description' => 'Virement mensuel vers Boursobank',
            'amount' => 500.00,
            'date' => Carbon::now()->startOfMonth(),
            'frequency' => 'monthly',
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => null,
            'is_active' => true,
        ]);

        Transfer::create([
            'from_account_id' => $bnpCourant->id,
            'to_account_id' => $natixisPEI->id,
            'name' => 'Plan d\'épargne interentreprises',
            'description' => 'Virement mensuel vers PEI',
            'amount' => 134.00,
            'date' => Carbon::now()->startOfMonth(),
            'frequency' => 'monthly',
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => null,
            'is_active' => true,
        ]);

        Transfer::create([
            'from_account_id' => $bnpCourant->id,
            'to_account_id' => $natixisPERCOLI->id,
            'name' => 'Plan d\'Épargne Retraite Collectif Interentreprises',
            'description' => 'Virement mensuel vers PERCOLI',
            'amount' => 167.00,
            'date' => Carbon::now()->startOfMonth(),
            'frequency' => 'monthly',
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => null,
            'is_active' => true,
        ]);

        // Se déconnecter après les seeds
        Auth::logout();
    }
}
