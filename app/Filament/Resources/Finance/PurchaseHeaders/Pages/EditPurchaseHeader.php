<?php

namespace App\Filament\Resources\Finance\PurchaseHeaders\Pages;

use App\Filament\Resources\Finance\PurchaseHeaders\PurchaseHeaderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseHeader extends EditRecord
{
    protected static string $resource = PurchaseHeaderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
