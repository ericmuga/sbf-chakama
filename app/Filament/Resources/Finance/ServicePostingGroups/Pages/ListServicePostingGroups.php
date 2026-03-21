<?php

namespace App\Filament\Resources\Finance\ServicePostingGroups\Pages;

use App\Filament\Resources\Finance\ServicePostingGroups\ServicePostingGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServicePostingGroups extends ListRecords
{
    protected static string $resource = ServicePostingGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
