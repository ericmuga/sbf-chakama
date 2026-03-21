<?php

namespace App\Filament\Resources\Finance\PurchaseHeaders\Pages;

use App\Filament\Resources\Finance\PurchaseHeaders\PurchaseHeaderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseHeader extends CreateRecord
{
    protected static string $resource = PurchaseHeaderResource::class;
}
