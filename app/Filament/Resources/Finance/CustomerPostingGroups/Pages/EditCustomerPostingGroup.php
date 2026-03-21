<?php

namespace App\Filament\Resources\Finance\CustomerPostingGroups\Pages;

use App\Filament\Resources\Finance\CustomerPostingGroups\CustomerPostingGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerPostingGroup extends EditRecord
{
    protected static string $resource = CustomerPostingGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
