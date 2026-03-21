<?php

namespace App\Filament\Resources\Finance\PaymentMethods\Pages;

use App\Filament\Resources\Finance\PaymentMethods\PaymentMethodResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentMethod extends CreateRecord
{
    protected static string $resource = PaymentMethodResource::class;
}
