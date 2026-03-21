<?php

namespace App\Filament\Resources\Finance\CashReceipts\Pages;

use App\Filament\Resources\Finance\CashReceipts\CashReceiptResource;
use App\Models\Finance\CashReceipt;
use App\Services\Finance\ReceiptPostingService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateCashReceipt extends CreateRecord
{
    protected static string $resource = CashReceiptResource::class;

    protected function getFormActions(): array
    {
        return [
            Action::make('postReceipt')
                ->label('Post Receipt')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Post Receipt')
                ->modalDescription('This will create and post the receipt to the ledgers. This cannot be undone.')
                ->action('postReceipt'),
            $this->getCancelFormAction(),
        ];
    }

    public function postReceipt(): void
    {
        $this->form->validate();

        $state = $this->form->getState();
        $allocations = collect($state['allocations'] ?? [])
            ->filter(fn ($a) => isset($a['amount_applied']) && (float) $a['amount_applied'] > 0)
            ->values()
            ->toArray();
        unset($state['allocations']);

        $totalAllocated = collect($allocations)->sum('amount_applied');

        if ($totalAllocated > (float) $state['amount']) {
            Notification::make()
                ->title('Allocated amount exceeds the receipt amount.')
                ->body('Total allocated: '.number_format($totalAllocated, 2).' — Receipt amount: '.number_format($state['amount'], 2))
                ->danger()
                ->send();

            return;
        }

        try {
            DB::transaction(function () use ($state, $allocations): void {
                $receipt = CashReceipt::create($state);
                app(ReceiptPostingService::class)->post(
                    $receipt->load(['bankAccount.bankPostingGroup', 'customer.customerPostingGroup']),
                    $allocations
                );
            });

            Notification::make()->title('Receipt posted successfully')->success()->send();
            $this->redirect($this->getResource()::getUrl('index'));
        } catch (\RuntimeException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}
