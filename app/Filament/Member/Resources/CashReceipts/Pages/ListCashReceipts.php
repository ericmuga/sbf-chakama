<?php

namespace App\Filament\Member\Resources\CashReceipts\Pages;

use App\Filament\Member\Resources\CashReceipts\CashReceiptResource;
use Filament\Resources\Pages\ListRecords;

class ListCashReceipts extends ListRecords
{
    protected static string $resource = CashReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
