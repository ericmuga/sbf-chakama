<?php

namespace App\Filament\Resources\Finance\NumberSeries\Pages;

use App\Filament\Resources\Finance\NumberSeries\NumberSeriesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNumberSeries extends ListRecords
{
    protected static string $resource = NumberSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
