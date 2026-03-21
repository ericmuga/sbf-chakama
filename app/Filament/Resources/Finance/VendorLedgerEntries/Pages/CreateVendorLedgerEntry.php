<?php

namespace App\Filament\Resources\Finance\VendorLedgerEntries\Pages;

use App\Filament\Resources\Finance\VendorLedgerEntries\VendorLedgerEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVendorLedgerEntry extends CreateRecord
{
    protected static string $resource = VendorLedgerEntryResource::class;
}
