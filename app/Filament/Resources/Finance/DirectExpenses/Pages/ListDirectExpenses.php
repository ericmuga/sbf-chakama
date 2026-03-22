<?php

namespace App\Filament\Resources\Finance\DirectExpenses\Pages;

use App\Filament\Resources\Finance\DirectExpenses\DirectExpenseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDirectExpenses extends ListRecords
{
    protected static string $resource = DirectExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
