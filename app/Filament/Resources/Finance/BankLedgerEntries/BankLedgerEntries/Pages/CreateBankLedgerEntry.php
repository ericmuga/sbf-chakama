<?php

namespace App\Filament\Resources\Finance\BankLedgerEntries\BankLedgerEntries\Pages;

use App\Filament\Resources\Finance\BankLedgerEntries\BankLedgerEntries\BankLedgerEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankLedgerEntry extends CreateRecord
{
    protected static string $resource = BankLedgerEntryResource::class;
}
