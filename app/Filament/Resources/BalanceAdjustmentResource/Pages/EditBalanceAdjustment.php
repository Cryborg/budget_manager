<?php

namespace App\Filament\Resources\BalanceAdjustmentResource\Pages;

use App\Filament\Resources\BalanceAdjustmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBalanceAdjustment extends EditRecord
{
    protected static string $resource = BalanceAdjustmentResource::class;

    public function getTitle(): string
    {
        return 'Modifier l\'ajustement de solde';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
