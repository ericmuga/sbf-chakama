<?php

namespace App\Filament\Resources\Finance\ServicePostingGroups\Pages;

use App\Filament\Resources\Finance\ServicePostingGroups\ServicePostingGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServicePostingGroup extends EditRecord
{
    protected static string $resource = ServicePostingGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
