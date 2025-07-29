<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BalanceAdjustmentResource\Pages;
use App\Filament\Resources\BalanceAdjustmentResource\RelationManagers;
use App\Models\BalanceAdjustment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BalanceAdjustmentResource extends Resource
{
    protected static ?string $model = BalanceAdjustment::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    
    protected static ?string $navigationLabel = 'Ajustements de solde';
    
    protected static ?string $modelLabel = 'ajustement de solde';
    
    protected static ?string $pluralModelLabel = 'ajustements de solde';
    
    public static function getModelLabel(): string
    {
        return 'ajustement de solde';
    }
    
    public static function getPluralModelLabel(): string  
    {
        return 'ajustements de solde';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bank_account_id')
                    ->label('Compte bancaire')
                    ->relationship('bankAccount', 'name')
                    ->preload()
                    ->required(),
                
                Forms\Components\DatePicker::make('adjustment_date')
                    ->label('Date d\'ajustement')
                    ->required()
                    ->default(now()),
                
                Forms\Components\TextInput::make('actual_balance')
                    ->label('Solde réel')
                    ->prefix('€')
                    ->numeric()
                    ->required()
                    ->step(0.01),
                
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->placeholder('Raison de l\'ajustement (optionnel)')
                    ->columnSpanFull(),
                
                Forms\Components\Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bankAccount.name')
                    ->label('Compte')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('adjustment_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('actual_balance')
                    ->label('Solde réel')
                    ->money('EUR')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(30)
                    ->placeholder('Aucune description'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('adjustment_date', 'desc')
            ->emptyStateHeading('Aucun ajustement de solde')
            ->emptyStateDescription('Créez votre premier ajustement pour commencer à suivre les corrections de solde.')
            ->emptyStateIcon('heroicon-o-adjustments-horizontal')
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
            'index' => Pages\ListBalanceAdjustments::route('/'),
            'create' => Pages\CreateBalanceAdjustment::route('/create'),
            'edit' => Pages\EditBalanceAdjustment::route('/{record}/edit'),
        ];
    }
}
