<?php

namespace App\Filament\Resources\Finance\GlAccounts\Pages;

use App\Filament\Resources\Finance\GlAccounts\GlAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGlAccounts extends ListRecords
{
    protected static string $resource = GlAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
