<?php

namespace App\Filament\Resources\Finance\PurchaseSetups\Pages;

use App\Filament\Resources\Finance\PurchaseSetups\PurchaseSetupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseSetups extends ListRecords
{
    protected static string $resource = PurchaseSetupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
