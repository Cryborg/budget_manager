<?php

namespace App\Filament\Widgets;

use App\Models\BankAccount;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class BankAccountsWidget extends BaseWidget
{
    protected static ?string $heading = 'Aperçu des comptes';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.bank-accounts-custom';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BankAccount::query()
                    ->with('bank')
                    ->where('bank_accounts.is_active', true)
                    ->join('banks', 'bank_accounts.bank_id', '=', 'banks.id')
                    ->orderBy('banks.created_at', 'asc')
                    ->orderBy('bank_accounts.name', 'asc')
                    ->select('bank_accounts.*')
            )
            ->columns([
                Tables\Columns\TextColumn::make('bank.name')
                    ->label('Banque')
                    ->badge()
                    ->color(fn ($record) => $record->bank->color ? 'primary' : 'gray'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Compte')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'current' => 'Courant',
                        'savings' => 'Épargne',
                        'investment' => 'Investissement',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'current' => 'primary',
                        'savings' => 'success',
                        'investment' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Solde')
                    ->money('EUR')
                    ->weight('bold')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
            ])
            ->paginated(false);
    }

    public function getGroupedAccounts()
    {
        return BankAccount::query()
            ->with('bank')
            ->where('bank_accounts.is_active', true)
            ->join('banks', 'bank_accounts.bank_id', '=', 'banks.id')
            ->orderBy('banks.created_at', 'asc')
            ->orderBy('bank_accounts.name', 'asc')
            ->select('bank_accounts.*')
            ->get()
            ->groupBy('bank.name');
    }
}
