<?php

namespace App\Filament\Resources\Finance\GlEntries\Pages;

use App\Filament\Resources\Finance\GlEntries\GlEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGlEntry extends CreateRecord
{
    protected static string $resource = GlEntryResource::class;
}
