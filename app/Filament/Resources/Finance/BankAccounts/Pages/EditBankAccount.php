<?php

namespace App\Filament\Resources\Finance\BankAccounts\Pages;

use App\Filament\Resources\Finance\BankAccounts\BankAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBankAccount extends EditRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
