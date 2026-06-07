<?php

namespace App\Filament\Member\Resources\Shares\Pages;

use App\Filament\Member\Resources\Shares\MyShareResource;
use Filament\Resources\Pages\ListRecords;

class ListMyShares extends ListRecords
{
    protected static string $resource = MyShareResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
