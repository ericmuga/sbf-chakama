<?php

namespace App\Filament\Resources\Finance\VendorLedgerEntries\Pages;

use App\Filament\Resources\Finance\VendorLedgerEntries\VendorLedgerEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVendorLedgerEntry extends EditRecord
{
    protected static string $resource = VendorLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
