<?php

namespace App\Filament\Resources\Finance\ScheduledInvoices\Pages;

use App\Filament\Resources\Finance\ScheduledInvoices\ScheduledInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScheduledInvoice extends EditRecord
{
    protected static string $resource = ScheduledInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
