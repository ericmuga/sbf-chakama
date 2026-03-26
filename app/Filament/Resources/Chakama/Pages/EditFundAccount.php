<?php

namespace App\Filament\Resources\Chakama\Pages;

use App\Filament\Resources\Chakama\FundAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFundAccount extends EditRecord
{
    protected static string $resource = FundAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
