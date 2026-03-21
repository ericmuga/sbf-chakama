<?php

namespace App\Filament\Resources\Finance\CashReceipts\Pages;

use App\Filament\Resources\Finance\CashReceipts\CashReceiptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashReceipts extends ListRecords
{
    protected static string $resource = CashReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
