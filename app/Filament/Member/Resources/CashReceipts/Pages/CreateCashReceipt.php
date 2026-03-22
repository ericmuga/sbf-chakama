<?php

namespace App\Filament\Member\Resources\CashReceipts\Pages;

use App\Filament\Member\Resources\CashReceipts\CashReceiptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCashReceipt extends CreateRecord
{
    protected static string $resource = CashReceiptResource::class;
}
