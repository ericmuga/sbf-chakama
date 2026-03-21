<?php

namespace App\Filament\Resources\Finance\NumberSeries\Pages;

use App\Filament\Resources\Finance\NumberSeries\NumberSeriesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNumberSeries extends EditRecord
{
    protected static string $resource = NumberSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
