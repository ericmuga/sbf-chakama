<?php

namespace App\Filament\Resources\Chakama\ShareBillingRuns\Pages;

use App\Filament\Resources\Chakama\ShareBillingRuns\ShareBillingRunResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditShareBillingRun extends EditRecord
{
    protected static string $resource = ShareBillingRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
