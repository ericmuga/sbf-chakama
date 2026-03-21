<?php

namespace App\Filament\Resources\Finance\VendorPayments\Pages;

use App\Filament\Resources\Finance\VendorPayments\VendorPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVendorPayment extends EditRecord
{
    protected static string $resource = VendorPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
