<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasCustomLabels;
use App\Filament\Concerns\HasFrequencyCalculation;
use App\Filament\Resources\TransferResource\Pages;
use App\Models\Transfer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TransferResource extends Resource
{
    use HasCustomLabels;
    use HasFrequencyCalculation;

    protected static ?string $model = Transfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Virements';
    
    protected static ?string $navigationGroup = 'Finances';
    
    protected static ?int $navigationSort = 3;

    protected static function getSingularLabel(): string
    {
        return 'virement';
    }

    public static function getPluralLabel(): string
    {
        return 'virements';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('from_account_id')
                    ->label('Compte émetteur')
                    ->relationship('fromAccount', 'name')
                    ->required(),
                Forms\Components\Select::make('to_account_id')
                    ->label('Compte destinataire')
                    ->relationship('toAccount', 'name')
                    ->required(),
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
                Tables\Columns\TextColumn::make('fromAccount.name')
                    ->label('De')
                    ->searchable(),
                Tables\Columns\TextColumn::make('toAccount.name')
                    ->label('Vers')
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
            'index' => Pages\ListTransfers::route('/'),
            'create' => Pages\CreateTransfer::route('/create'),
            'edit' => Pages\EditTransfer::route('/{record}/edit'),
        ];
    }
}
