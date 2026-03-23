<?php

namespace App\Filament\Resources\Chakama\Pages;

use App\Filament\Resources\Chakama\ShareBillingScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditShareBillingSchedule extends EditRecord
{
    protected static string $resource = ShareBillingScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
