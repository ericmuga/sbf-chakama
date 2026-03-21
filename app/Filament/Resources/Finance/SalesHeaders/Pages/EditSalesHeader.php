<?php

namespace App\Filament\Resources\Finance\SalesHeaders\Pages;

use App\Filament\Resources\Finance\SalesHeaders\SalesHeaderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSalesHeader extends EditRecord
{
    protected static string $resource = SalesHeaderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
