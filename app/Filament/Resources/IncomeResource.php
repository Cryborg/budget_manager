<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasCustomLabels;
use App\Filament\Concerns\HasFrequencyCalculation;
use App\Filament\Resources\IncomeResource\Pages;
use App\Models\Income;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IncomeResource extends Resource
{
    use HasCustomLabels;
    use HasFrequencyCalculation;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bank_account_id')
                    ->label('Compte bancaire')
                    ->relationship('bankAccount', 'name', function ($query) {
                        return $query->where('user_id', auth()->id());
                    })
                    ->required()
                    ->rules([
                        function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                if (!$value) return;
                                
                                $account = \App\Models\BankAccount::find($value);
                                if (!$account || $account->user_id !== auth()->id()) {
                                    $fail('Le compte bancaire sélectionné ne vous appartient pas.');
                                }
                            };
                        }
                    ]),
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->maxLength(500),
                static::getAmountFormComponent(),
                Forms\Components\DatePicker::make('date')
                    ->label('Date')
                    ->required(),
                ...static::getFrequencyFormComponents(),
                Forms\Components\Placeholder::make('total_amount')
                    ->label('')
                    ->content(fn (callable $get) => static::getAmountCalculationPlaceholder($get)),
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
                    ->formatStateUsing(fn (string $state): string => static::formatFrequencyForTable($state)),
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
            'index' => Pages\ListIncomes::route('/'),
            'create' => Pages\CreateIncome::route('/create'),
            'edit' => Pages\EditIncome::route('/{record}/edit'),
        ];
    }
}
