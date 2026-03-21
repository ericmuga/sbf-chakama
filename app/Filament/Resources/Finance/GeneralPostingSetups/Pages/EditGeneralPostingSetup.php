<?php

namespace App\Filament\Resources\Finance\GeneralPostingSetups\Pages;

use App\Filament\Resources\Finance\GeneralPostingSetups\GeneralPostingSetupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGeneralPostingSetup extends EditRecord
{
    protected static string $resource = GeneralPostingSetupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
