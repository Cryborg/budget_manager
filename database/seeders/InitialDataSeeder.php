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
        ]);

        $boursoLivretA = BankAccount::create([
            'bank_id' => $boursobank->id,
            'name' => 'Livret A',
            'type' => 'savings',
            'current_balance' => 10.00,
            'initial_balance' => 10.00,
        ]);

        $boursoLDDS = BankAccount::create([
            'bank_id' => $boursobank->id,
            'name' => 'LDDS',
            'type' => 'savings',
            'current_balance' => 10.00,
            'initial_balance' => 10.00,
        ]);

        $boursoInternet = BankAccount::create([
            'bank_id' => $boursobank->id,
            'name' => 'Compte Boursobank',
            'type' => 'current',
            'current_balance' => 90.00,
            'initial_balance' => 0.00,
        ]);

        $natixisPEI = BankAccount::create([
            'bank_id' => $natixis->id,
            'name' => 'PEI',
            'type' => 'investment',
            'current_balance' => 621.67,
            'initial_balance' => 0.00,
        ]);

        $natixisPERCOLI = BankAccount::create([
            'bank_id' => $natixis->id,
            'name' => 'PERCOLI',
            'type' => 'investment',
            'current_balance' => 0.00,
            'initial_balance' => 0.00,
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
            'category' => 'Salaire',
        ]);

        Income::create([
            'bank_account_id' => $bnpCourant->id,
            'name' => 'Remboursement assurance Enora',
            'description' => 'Ma fille me rembourse son assurance voiture',
            'amount' => 36.00,
            'date' => Carbon::now()->startOfMonth(),
            'frequency' => 'monthly',
            'start_date' => Carbon::now()->startOfMonth(),
            'category' => 'Remboursement',
        ]);

        Income::create([
            'bank_account_id' => $natixisPEI->id,
            'name' => 'Abondement Smice',
            'description' => 'Abondement annuel de l\'entreprise',
            'amount' => 500.00,
            'date' => Carbon::now()->startOfYear(),
            'frequency' => 'yearly',
            'start_date' => Carbon::now()->startOfYear(),
            'category' => 'Abondement',
        ]);

        // Dépenses fixes BNP
        $expensesBnp = [
            ['name' => 'Loyer', 'amount' => 1045.00],
            ['name' => 'Pension', 'amount' => 611.92],
            ['name' => 'Crédit Tesla', 'amount' => 393.00],
            ['name' => 'Électricité', 'amount' => 80.00],
            ['name' => 'Gaz', 'amount' => 70.00],
            ['name' => 'Assurance maison', 'amount' => 23.00],
            ['name' => 'Assurance Tesla', 'amount' => 51.00],
            ['name' => 'Abonnement Tesla', 'amount' => 10.00],
            ['name' => 'Keepcool (x2)', 'amount' => 50.00],
            ['name' => 'Remboursement Papa', 'amount' => 500.00],
            ['name' => 'Préfon', 'amount' => 52.50],
        ];

        foreach ($expensesBnp as $expense) {
            Expense::create([
                'bank_account_id' => $bnpCourant->id,
                'name' => $expense['name'],
                'amount' => $expense['amount'],
                'date' => Carbon::now()->startOfMonth(),
                'frequency' => 'monthly',
                'start_date' => Carbon::now()->startOfMonth(),
                'category' => 'Fixe',
            ]);
        }

        // Dépenses Boursobank
        $expensesBourso = [
            ['name' => 'Box internet', 'amount' => 30.00],
            ['name' => 'Téléphone', 'amount' => 10.00],
            ['name' => 'Assurance voiture Enora', 'amount' => 38.00],
        ];

        foreach ($expensesBourso as $expense) {
            Expense::create([
                'bank_account_id' => $boursoInternet->id,
                'name' => $expense['name'],
                'amount' => $expense['amount'],
                'date' => Carbon::now()->startOfMonth(),
                'frequency' => 'monthly',
                'start_date' => Carbon::now()->startOfMonth(),
                'category' => 'Fixe',
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
            'category' => 'Transport',
        ]);

        Expense::create([
            'bank_account_id' => $bnpCourant->id,
            'name' => 'Nourriture',
            'description' => 'Courses alimentaires',
            'amount' => 300.00,
            'date' => Carbon::now()->startOfMonth(),
            'frequency' => 'monthly',
            'start_date' => Carbon::now()->startOfMonth(),
            'category' => 'Alimentation',
        ]);

        // Chèque dentiste ponctuel
        Expense::create([
            'bank_account_id' => $bnpCourant->id,
            'name' => 'Dentiste',
            'description' => 'Chèque dentiste',
            'amount' => 386.00,
            'date' => Carbon::now()->addDays(5), // Pour août
            'frequency' => 'once',
            'category' => 'Santé',
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
        ]);

        // Se déconnecter après les seeds
        Auth::logout();
    }
}
