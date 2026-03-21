<?php

namespace App\Filament\Resources\Finance\CashReceipts\Pages;

use App\Filament\Resources\Finance\CashReceipts\CashReceiptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCashReceipt extends CreateRecord
{
    protected static string $resource = CashReceiptResource::class;
}
