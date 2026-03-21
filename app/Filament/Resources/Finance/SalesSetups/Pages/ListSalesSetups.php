<?php

namespace App\Filament\Resources\Finance\SalesSetups\Pages;

use App\Filament\Resources\Finance\SalesSetups\SalesSetupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesSetups extends ListRecords
{
    protected static string $resource = SalesSetupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
