<?php

namespace App\Filament\Resources\Finance\CustomerLedgerEntries\Pages;

use App\Filament\Resources\Finance\CustomerLedgerEntries\CustomerLedgerEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerLedgerEntry extends CreateRecord
{
    protected static string $resource = CustomerLedgerEntryResource::class;
}
