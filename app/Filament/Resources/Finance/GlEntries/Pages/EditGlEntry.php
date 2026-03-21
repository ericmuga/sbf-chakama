<?php

namespace App\Filament\Resources\Finance\GlEntries\Pages;

use App\Filament\Resources\Finance\GlEntries\GlEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGlEntry extends EditRecord
{
    protected static string $resource = GlEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
