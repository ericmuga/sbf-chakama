<?php

namespace App\Filament\Resources\Finance\CashReceipts\Pages;

use App\Filament\Resources\Finance\CashReceipts\CashReceiptResource;
use App\Models\Finance\CashReceipt;
use App\Services\Finance\ReceiptPostingService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCashReceipt extends EditRecord
{
    protected static string $resource = CashReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label('Post')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Post Cash Receipt')
                ->modalDescription('This will post the receipt to the customer and G/L ledgers. This cannot be undone.')
                ->hidden(fn (CashReceipt $record): bool => strtolower($record->status) === 'posted')
                ->action(function (CashReceipt $record): void {
                    try {
                        app(ReceiptPostingService::class)->post($record->load(['bankAccount.bankPostingGroup', 'customer.customerPostingGroup']));
                        Notification::make()->title('Receipt posted successfully')->success()->send();
                        $this->refreshFormData(['status', 'no']);
                    } catch (\RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            DeleteAction::make()
                ->hidden(fn (CashReceipt $record): bool => strtolower($record->status) === 'posted'),
        ];
    }
}
