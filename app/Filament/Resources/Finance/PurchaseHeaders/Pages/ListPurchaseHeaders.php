<?php

namespace App\Filament\Resources\Finance\PurchaseHeaders\Pages;

use App\Filament\Resources\Finance\PurchaseHeaders\PurchaseHeaderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseHeaders extends ListRecords
{
    protected static string $resource = PurchaseHeaderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
