<?php

namespace App\Filament\Resources\Finance\GlAccounts\Pages;

use App\Filament\Resources\Finance\GlAccounts\GlAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGlAccount extends EditRecord
{
    protected static string $resource = GlAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
