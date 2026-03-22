<?php

namespace App\Filament\Resources\Finance\BankLedgerEntries\Pages;

use App\Filament\Resources\Finance\BankLedgerEntries\BankLedgerEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListBankLedgerEntries extends ListRecords
{
    protected static string $resource = BankLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
