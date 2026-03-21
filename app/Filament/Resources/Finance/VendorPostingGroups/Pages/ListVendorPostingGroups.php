<?php

namespace App\Filament\Resources\Finance\VendorPostingGroups\Pages;

use App\Filament\Resources\Finance\VendorPostingGroups\VendorPostingGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVendorPostingGroups extends ListRecords
{
    protected static string $resource = VendorPostingGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
