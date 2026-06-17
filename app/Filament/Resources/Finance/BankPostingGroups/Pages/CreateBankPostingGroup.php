<?php

namespace App\Filament\Resources\Finance\BankPostingGroups\Pages;

use App\Filament\Resources\Finance\BankPostingGroups\BankPostingGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankPostingGroup extends CreateRecord
{
    protected static string $resource = BankPostingGroupResource::class;
}
