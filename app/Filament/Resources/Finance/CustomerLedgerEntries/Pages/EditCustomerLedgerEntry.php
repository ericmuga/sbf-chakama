<?php

namespace App\Filament\Resources\Finance\CustomerLedgerEntries\Pages;

use App\Filament\Resources\Finance\CustomerLedgerEntries\CustomerLedgerEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerLedgerEntry extends EditRecord
{
    protected static string $resource = CustomerLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
