<?php

namespace App\Filament\Resources\Finance\BankPostingGroups\Pages;

use App\Filament\Resources\Finance\BankPostingGroups\BankPostingGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBankPostingGroups extends ListRecords
{
    protected static string $resource = BankPostingGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
