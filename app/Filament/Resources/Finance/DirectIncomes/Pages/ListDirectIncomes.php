<?php

namespace App\Filament\Resources\Finance\DirectIncomes\Pages;

use App\Filament\Resources\Finance\DirectIncomes\DirectIncomeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDirectIncomes extends ListRecords
{
    protected static string $resource = DirectIncomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
