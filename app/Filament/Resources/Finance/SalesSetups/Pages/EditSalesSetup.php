<?php

namespace App\Filament\Resources\Finance\SalesSetups\Pages;

use App\Filament\Resources\Finance\SalesSetups\SalesSetupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSalesSetup extends EditRecord
{
    protected static string $resource = SalesSetupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
