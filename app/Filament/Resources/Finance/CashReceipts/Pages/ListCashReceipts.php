<?php

namespace App\Filament\Resources\Finance\CashReceipts\Pages;

use App\Filament\Resources\Finance\CashReceipts\CashReceiptResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListCashReceipts extends ListRecords
{
    protected static string $resource = CashReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newReceipt')
                ->label('Post New Receipt')
                ->icon('heroicon-o-plus')
                ->url($this->getResource()::getUrl('create')),
        ];
    }
}
