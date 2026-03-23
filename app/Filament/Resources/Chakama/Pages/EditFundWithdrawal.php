<?php

namespace App\Filament\Resources\Chakama\Pages;

use App\Enums\FundWithdrawalStatus;
use App\Filament\Resources\Chakama\FundWithdrawalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFundWithdrawal extends EditRecord
{
    protected static string $resource = FundWithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => $this->getRecord()->status === FundWithdrawalStatus::Draft),
        ];
    }
}
