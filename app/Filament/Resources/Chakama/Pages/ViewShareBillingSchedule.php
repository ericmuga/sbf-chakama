<?php

namespace App\Filament\Resources\Chakama\Pages;

use App\Filament\Resources\Chakama\ShareBillingScheduleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewShareBillingSchedule extends ViewRecord
{
    protected static string $resource = ShareBillingScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
