<?php

namespace App\Filament\Resources\Finance\VendorPayments\Pages;

use App\Filament\Resources\Finance\VendorPayments\VendorPaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVendorPayment extends CreateRecord
{
    protected static string $resource = VendorPaymentResource::class;
}
