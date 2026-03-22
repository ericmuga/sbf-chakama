<?php

namespace App\Filament\Resources\Finance\ScheduledInvoices\Pages;

use App\Filament\Resources\Finance\ScheduledInvoices\ScheduledInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateScheduledInvoice extends CreateRecord
{
    protected static string $resource = ScheduledInvoiceResource::class;
}
