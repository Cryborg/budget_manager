<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasFinancialTransactionResource;
use App\Filament\Resources\IncomeResource\Pages;
use App\Models\Income;
use Filament\Resources\Resource;

class IncomeResource extends Resource
{
    use HasFinancialTransactionResource;

    protected static ?string $model = Income::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $navigationLabel = 'Revenus';

    protected static ?string $navigationGroup = 'Finances';

    protected static ?int $navigationSort = 1;

    protected static function getSingularLabel(): string
    {
        return 'revenu';
    }

    public static function getPluralLabel(): string
    {
        return 'revenus';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncomes::route('/'),
            'create' => Pages\CreateIncome::route('/create'),
            'edit' => Pages\EditIncome::route('/{record}/edit'),
        ];
    }
}
