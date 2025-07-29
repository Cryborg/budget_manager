<?php

namespace App\Filament\Widgets;

use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\Income;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalBalance = BankAccount::where('is_active', true)->sum('current_balance');

        $monthlyIncome = Income::where('is_active', true)
            ->where('frequency', 'monthly')
            ->sum('amount');

        $monthlyExpenses = Expense::where('is_active', true)
            ->where('frequency', 'monthly')
            ->sum('amount');

        $netMonthly = $monthlyIncome - $monthlyExpenses;

        return [
            Stat::make('Solde total', number_format($totalBalance, 2, ',', ' ').' €')
                ->description('Tous comptes confondus')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($totalBalance >= 0 ? 'success' : 'danger'),

            Stat::make('Revenus mensuels', number_format($monthlyIncome, 2, ',', ' ').' €')
                ->description('Revenus récurrents')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Dépenses mensuelles', number_format($monthlyExpenses, 2, ',', ' ').' €')
                ->description('Dépenses récurrentes')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Résultat mensuel', number_format($netMonthly, 2, ',', ' ').' €')
                ->description($netMonthly >= 0 ? 'Excédent' : 'Déficit')
                ->descriptionIcon($netMonthly >= 0 ? 'heroicon-m-arrow-up' : 'heroicon-m-arrow-down')
                ->color($netMonthly >= 0 ? 'success' : 'danger'),
        ];
    }
}
