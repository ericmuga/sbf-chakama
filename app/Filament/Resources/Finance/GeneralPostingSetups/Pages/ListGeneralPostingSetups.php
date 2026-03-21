<?php

namespace App\Filament\Resources\Finance\GeneralPostingSetups\Pages;

use App\Filament\Resources\Finance\GeneralPostingSetups\GeneralPostingSetupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGeneralPostingSetups extends ListRecords
{
    protected static string $resource = GeneralPostingSetupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
