<?php

namespace App\Filament\Resources\Chakama\Pages;

use App\Filament\Resources\Chakama\FundAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFundAccounts extends ListRecords
{
    protected static string $resource = FundAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
