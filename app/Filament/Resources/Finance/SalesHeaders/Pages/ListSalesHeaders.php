<?php

namespace App\Filament\Resources\Finance\SalesHeaders\Pages;

use App\Filament\Resources\Finance\SalesHeaders\SalesHeaderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesHeaders extends ListRecords
{
    protected static string $resource = SalesHeaderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
