<?php

namespace App\Filament\Resources\Finance\VendorLedgerEntries\Pages;

use App\Filament\Resources\Finance\VendorLedgerEntries\VendorLedgerEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListVendorLedgerEntries extends ListRecords
{
    protected static string $resource = VendorLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
