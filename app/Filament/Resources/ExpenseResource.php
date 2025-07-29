<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Filament\Resources\ExpenseResource\RelationManagers;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';
    protected static ?string $navigationLabel = 'Dépenses';
    protected static ?string $modelLabel = 'dépense';
    protected static ?string $pluralModelLabel = 'dépenses';
    
    public static function getModelLabel(): string
    {
        return 'dépense';
    }
    
    public static function getPluralModelLabel(): string  
    {
        return 'dépenses';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bank_account_id')
                    ->label('Compte bancaire')
                    ->relationship('bankAccount', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->maxLength(500),
                Forms\Components\TextInput::make('amount')
                    ->label('Montant')
                    ->numeric()
                    ->step(0.01)
                    ->required()
                    ->reactive(),
                Forms\Components\DatePicker::make('date')
                    ->label('Date')
                    ->required(),
                Forms\Components\Select::make('frequency')
                    ->label('Fréquence')
                    ->options([
                        'once' => 'Une fois',
                        'daily' => 'Quotidien',
                        'weekly' => 'Hebdomadaire',
                        'monthly' => 'Mensuel',
                        'yearly' => 'Annuel',
                    ])
                    ->default('once')
                    ->reactive(),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Date de début')
                    ->visible(fn (callable $get) => $get('frequency') !== 'once')
                    ->default(now())
                    ->reactive(),
                Forms\Components\DatePicker::make('end_date')
                    ->label('Date de fin')
                    ->visible(fn (callable $get) => $get('frequency') !== 'once')
                    ->reactive(),
                Forms\Components\Placeholder::make('total_amount')
                    ->label('Montant total sur la période')
                    ->visible(fn (callable $get) => $get('frequency') !== 'once' && $get('start_date') && $get('end_date'))
                    ->content(function (callable $get) {
                        $amount = $get('amount');
                        $frequency = $get('frequency');
                        $startDate = $get('start_date');
                        $endDate = $get('end_date');
                        
                        if (!$amount || !$frequency || !$startDate || !$endDate || $frequency === 'once') {
                            return 'Remplissez tous les champs pour voir le calcul';
                        }
                        
                        $start = \Carbon\Carbon::parse($startDate);
                        $end = \Carbon\Carbon::parse($endDate);
                        
                        $occurrences = match($frequency) {
                            'daily' => $start->diffInDays($end) + 1,
                            'weekly' => $start->diffInWeeks($end) + 1,
                            'monthly' => $start->diffInMonths($end) + 1,
                            'yearly' => $start->diffInYears($end) + 1,
                            default => 1,
                        };
                        
                        $total = $amount * $occurrences;
                        return number_format($total, 2, ',', ' ') . ' € (' . $occurrences . ' fois)';
                    })
                    ->helperText('Calcul automatique basé sur la fréquence et la période'),
                Forms\Components\TextInput::make('category')
                    ->label('Catégorie')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bankAccount.name')
                    ->label('Compte')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('frequency')
                    ->label('Fréquence')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'once' => 'Une fois',
                        'daily' => 'Quotidien',
                        'weekly' => 'Hebdomadaire',
                        'monthly' => 'Mensuel',
                        'yearly' => 'Annuel',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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
