<?php

namespace App\Filament\Resources\Finance\PurchaseSetups\Pages;

use App\Filament\Resources\Finance\PurchaseSetups\PurchaseSetupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseSetup extends EditRecord
{
    protected static string $resource = PurchaseSetupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
