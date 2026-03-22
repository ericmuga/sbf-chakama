<?php

namespace App\Filament\Resources\Finance\VendorPayments\Pages;

use App\Filament\Resources\Finance\VendorPayments\VendorPaymentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListVendorPayments extends ListRecords
{
    protected static string $resource = VendorPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newPayment')
                ->label('Post New Payment')
                ->icon('heroicon-o-plus')
                ->url($this->getResource()::getUrl('create')),
        ];
    }
}
