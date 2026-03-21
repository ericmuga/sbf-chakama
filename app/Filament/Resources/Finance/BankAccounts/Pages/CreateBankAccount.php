<?php

namespace App\Filament\Resources\Finance\BankAccounts\Pages;

use App\Filament\Resources\Finance\BankAccounts\BankAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankAccount extends CreateRecord
{
    protected static string $resource = BankAccountResource::class;
}
