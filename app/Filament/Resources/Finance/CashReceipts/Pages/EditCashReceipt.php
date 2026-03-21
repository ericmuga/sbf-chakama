<?php

namespace App\Filament\Resources\Finance\CashReceipts\Pages;

use App\Filament\Resources\Finance\CashReceipts\CashReceiptResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCashReceipt extends EditRecord
{
    protected static string $resource = CashReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
