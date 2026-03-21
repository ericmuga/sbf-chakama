<?php

namespace App\Filament\Resources\Finance\CustomerLedgerEntries\Pages;

use App\Filament\Resources\Finance\CustomerLedgerEntries\CustomerLedgerEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerLedgerEntries extends ListRecords
{
    protected static string $resource = CustomerLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
