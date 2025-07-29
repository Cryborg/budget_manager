<?php

namespace App\Filament\Resources\BalanceAdjustmentResource\Pages;

use App\Filament\Resources\BalanceAdjustmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBalanceAdjustments extends ListRecords
{
    protected static string $resource = BalanceAdjustmentResource::class;

    public function getTitle(): string
    {
        return 'Ajustements de solde';
    }

    public function getBreadcrumb(): string
    {
        return 'Liste';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouvel ajustement de solde'),
        ];
    }
}
