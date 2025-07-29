<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasFinancialTransactionResource;
use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Resources\Resource;

class ExpenseResource extends Resource
{
    use HasFinancialTransactionResource;

    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';

    protected static ?string $navigationLabel = 'Dépenses';
    
    protected static ?string $navigationGroup = 'Finances';
    
    protected static ?int $navigationSort = 2;

    protected static function getSingularLabel(): string
    {
        return 'dépense';
    }

    public static function getPluralLabel(): string
    {
        return 'dépenses';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
