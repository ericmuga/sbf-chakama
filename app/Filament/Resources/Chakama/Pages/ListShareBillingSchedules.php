<?php

namespace App\Filament\Resources\Chakama\Pages;

use App\Filament\Resources\Chakama\ShareBillingScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShareBillingSchedules extends ListRecords
{
    protected static string $resource = ShareBillingScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
