<?php

namespace App\Filament\Resources\Finance\ScheduledInvoices\Pages;

use App\Filament\Resources\Finance\ScheduledInvoices\ScheduledInvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScheduledInvoices extends ListRecords
{
    protected static string $resource = ScheduledInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
