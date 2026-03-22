<?php

namespace App\Filament\Member\Resources\MyInvoices\Pages;

use App\Filament\Member\Resources\MyInvoices\MyInvoicesResource;
use Filament\Resources\Pages\ListRecords;

class ListMyInvoices extends ListRecords
{
    protected static string $resource = MyInvoicesResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
