<?php

namespace App\Filament\Resources\Finance\GlEntries\Pages;

use App\Filament\Resources\Finance\GlEntries\GlEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListGlEntries extends ListRecords
{
    protected static string $resource = GlEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
