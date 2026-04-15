<?php

namespace App\Filament\Resources\Finance\VendorPayments\Pages;

use App\Filament\Resources\Finance\VendorPayments\VendorPaymentResource;
use App\Models\Finance\VendorPayment;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewVendorPayment extends ViewRecord
{
    protected static string $resource = VendorPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->hidden(fn (VendorPayment $record): bool => strtolower($record->status) === 'posted'),
        ];
    }
}
