<?php

namespace App\Filament\Resources\Finance\BankLedgerEntries\BankLedgerEntries\Pages;

use App\Filament\Resources\Finance\BankLedgerEntries\BankLedgerEntries\BankLedgerEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBankLedgerEntries extends ListRecords
{
    protected static string $resource = BankLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
