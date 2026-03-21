<?php

namespace App\Filament\Resources\Finance\PaymentMethods\Pages;

use App\Filament\Resources\Finance\PaymentMethods\PaymentMethodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentMethod extends EditRecord
{
    protected static string $resource = PaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
