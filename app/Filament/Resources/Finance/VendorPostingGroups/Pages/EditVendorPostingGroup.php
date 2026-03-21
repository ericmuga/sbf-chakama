<?php

namespace App\Filament\Resources\Finance\VendorPostingGroups\Pages;

use App\Filament\Resources\Finance\VendorPostingGroups\VendorPostingGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVendorPostingGroup extends EditRecord
{
    protected static string $resource = VendorPostingGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
