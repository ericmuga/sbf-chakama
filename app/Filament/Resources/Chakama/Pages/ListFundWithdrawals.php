<?php

namespace App\Filament\Resources\Chakama\Pages;

use App\Filament\Resources\Chakama\FundWithdrawalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFundWithdrawals extends ListRecords
{
    protected static string $resource = FundWithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
