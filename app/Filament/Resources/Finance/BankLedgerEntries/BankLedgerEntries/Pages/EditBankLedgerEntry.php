<?php

namespace App\Filament\Resources\Finance\BankLedgerEntries\BankLedgerEntries\Pages;

use App\Filament\Resources\Finance\BankLedgerEntries\BankLedgerEntries\BankLedgerEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBankLedgerEntry extends EditRecord
{
    protected static string $resource = BankLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
