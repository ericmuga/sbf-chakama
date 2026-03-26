<?php

namespace App\Filament\Resources\Chakama\Pages;

use App\Filament\Resources\Chakama\FundAccountResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFundAccount extends ViewRecord
{
    protected static string $resource = FundAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
