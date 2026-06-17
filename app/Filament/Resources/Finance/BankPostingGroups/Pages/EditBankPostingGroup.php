<?php

namespace App\Filament\Resources\Finance\BankPostingGroups\Pages;

use App\Filament\Resources\Finance\BankPostingGroups\BankPostingGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBankPostingGroup extends EditRecord
{
    protected static string $resource = BankPostingGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
