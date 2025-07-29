<?php

namespace App\Filament\Resources\BalanceAdjustmentResource\Pages;

use App\Filament\Resources\BalanceAdjustmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBalanceAdjustment extends CreateRecord
{
    protected static string $resource = BalanceAdjustmentResource::class;

    public function getTitle(): string
    {
        return 'Nouvel ajustement de solde';
    }
}
