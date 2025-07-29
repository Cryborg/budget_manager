<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankAccountResource\Pages;
use App\Models\BankAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Comptes';

    protected static ?string $modelLabel = 'compte';

    protected static ?string $pluralModelLabel = 'comptes';

    public static function getModelLabel(): string
    {
        return 'compte';
    }

    public static function getPluralModelLabel(): string
    {
        return 'comptes';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bank_id')
                    ->label('Banque')
                    ->relationship('bank', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Nom du compte')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label('Type de compte')
                    ->options([
                        'current' => 'Compte courant',
                        'savings' => 'Compte épargne',
                        'investment' => 'Compte investissement',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('current_balance')
                    ->label('Solde actuel')
                    ->numeric()
                    ->step(0.01)
                    ->default(0),
                Forms\Components\TextInput::make('initial_balance')
                    ->label('Solde initial')
                    ->numeric()
                    ->step(0.01)
                    ->default(0),
                Forms\Components\TextInput::make('account_number')
                    ->label('Numéro de compte')
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
                Tables\Columns\TextColumn::make('bank.name')
                    ->label('Banque')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom du compte')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'current' => 'Compte courant',
                        'savings' => 'Compte épargne',
                        'investment' => 'Compte investissement',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Solde actuel')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }
}
