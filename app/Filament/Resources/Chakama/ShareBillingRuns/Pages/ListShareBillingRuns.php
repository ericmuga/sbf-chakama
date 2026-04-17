<?php

namespace App\Filament\Resources\Chakama\ShareBillingRuns\Pages;

use App\Filament\Resources\Chakama\ShareBillingRuns\ShareBillingRunResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShareBillingRuns extends ListRecords
{
    protected static string $resource = ShareBillingRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
