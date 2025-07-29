<?php

namespace App\Filament\Concerns;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Trait commun pour les ressources de transactions financières (Income/Expense)
 */
trait HasFinancialTransactionResource
{
    use HasCustomLabels;
    use HasFrequencyCalculation;

    /**
     * Définir le formulaire commun pour les transactions financières
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                static::getBankAccountSelectComponent(),
                static::getNameInputComponent(),
                static::getDescriptionTextareaComponent(),
                static::getAmountFormComponent(),
                static::getDatePickerComponent(),
                ...static::getFrequencyFormComponents(),
                static::getTotalAmountPlaceholderComponent(),
                static::getCategoryInputComponent(),
                static::getIsActiveToggleComponent(),
            ]);
    }

    /**
     * Définir le tableau commun pour les transactions financières
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getNameColumn(),
                static::getBankAccountColumn(),
                static::getAmountColumn(),
                static::getFrequencyColumn(),
                static::getDateColumn(),
                static::getCategoryColumn(),
                static::getIsActiveColumn(),
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

    /**
     * Composant select pour le compte bancaire avec sécurité
     */
    protected static function getBankAccountSelectComponent(): Forms\Components\Select
    {
        return Forms\Components\Select::make('bank_account_id')
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
            ]);
    }

    /**
     * Composant input pour le nom
     */
    protected static function getNameInputComponent(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('name')
            ->label('Nom')
            ->required()
            ->maxLength(255);
    }

    /**
     * Composant textarea pour la description
     */
    protected static function getDescriptionTextareaComponent(): Forms\Components\Textarea
    {
        return Forms\Components\Textarea::make('description')
            ->label('Description')
            ->maxLength(500);
    }

    /**
     * Composant date picker pour la date
     */
    protected static function getDatePickerComponent(): Forms\Components\DatePicker
    {
        return Forms\Components\DatePicker::make('date')
            ->label('Date')
            ->required();
    }

    /**
     * Composant placeholder pour le montant total
     */
    protected static function getTotalAmountPlaceholderComponent(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('total_amount')
            ->label('')
            ->content(fn (callable $get) => static::getAmountCalculationPlaceholder($get));
    }

    /**
     * Composant input pour la catégorie
     */
    protected static function getCategoryInputComponent(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('category')
            ->label('Catégorie')
            ->maxLength(255);
    }

    /**
     * Composant toggle pour is_active
     */
    protected static function getIsActiveToggleComponent(): Forms\Components\Toggle
    {
        return Forms\Components\Toggle::make('is_active')
            ->label('Actif')
            ->default(true);
    }

    /**
     * Colonne nom pour le tableau
     */
    protected static function getNameColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('name')
            ->label('Nom')
            ->searchable();
    }

    /**
     * Colonne compte bancaire pour le tableau
     */
    protected static function getBankAccountColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('bankAccount.name')
            ->label('Compte')
            ->searchable();
    }

    /**
     * Colonne montant pour le tableau
     */
    protected static function getAmountColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('amount')
            ->label('Montant')
            ->money('EUR')
            ->sortable();
    }

    /**
     * Colonne fréquence pour le tableau
     */
    protected static function getFrequencyColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('frequency')
            ->label('Fréquence')
            ->formatStateUsing(fn (string $state): string => static::formatFrequencyForTable($state));
    }

    /**
     * Colonne date pour le tableau
     */
    protected static function getDateColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('date')
            ->label('Date')
            ->date()
            ->sortable();
    }

    /**
     * Colonne catégorie pour le tableau
     */
    protected static function getCategoryColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('category')
            ->label('Catégorie')
            ->searchable();
    }

    /**
     * Colonne is_active pour le tableau
     */
    protected static function getIsActiveColumn(): Tables\Columns\IconColumn
    {
        return Tables\Columns\IconColumn::make('is_active')
            ->label('Actif')
            ->boolean();
    }

    /**
     * Relations communes (vide par défaut)
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
}