<?php

namespace App\Filament\Resources\Chakama\Pages;

use App\Filament\Resources\Chakama\ShareSubscriptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShareSubscriptions extends ListRecords
{
    protected static string $resource = ShareSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
