<?php

namespace App\Filament\Resources\Finance\CustomerPostingGroups\Pages;

use App\Filament\Resources\Finance\CustomerPostingGroups\CustomerPostingGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerPostingGroup extends CreateRecord
{
    protected static string $resource = CustomerPostingGroupResource::class;
}
