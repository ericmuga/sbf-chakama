<?php

namespace App\Filament\Resources\Finance\DirectExpenses\Pages;

use App\Filament\Resources\Finance\DirectExpenses\DirectExpenseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDirectExpense extends CreateRecord
{
    protected static string $resource = DirectExpenseResource::class;
}
