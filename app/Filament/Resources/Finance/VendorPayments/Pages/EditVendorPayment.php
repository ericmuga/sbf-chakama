<?php

namespace App\Filament\Resources\Finance\VendorPayments\Pages;

use App\Filament\Resources\Finance\VendorPayments\VendorPaymentResource;
use App\Models\Finance\VendorPayment;
use App\Services\Finance\VendorPaymentPostingService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVendorPayment extends EditRecord
{
    protected static string $resource = VendorPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label('Post')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Post Vendor Payment')
                ->modalDescription('This will post the payment to the vendor and G/L ledgers. This cannot be undone.')
                ->hidden(fn (VendorPayment $record): bool => strtolower($record->status) === 'posted')
                ->action(function (VendorPayment $record): void {
                    try {
                        $allocations = collect($this->form->getRawState()['allocations'] ?? [])
                            ->map(fn (array $row): array => [
                                'vendor_ledger_entry_id' => $row['vendor_ledger_entry_id'],
                                'amount_applied' => (float) ($row['amount_applied'] ?? 0),
                            ])
                            ->toArray();

                        app(VendorPaymentPostingService::class)->post(
                            $record->load(['bankAccount.bankPostingGroup', 'vendor.vendorPostingGroup']),
                            $allocations
                        );

                        Notification::make()->title('Payment posted successfully')->success()->send();
                        $this->refreshFormData(['status', 'no']);
                    } catch (\RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            DeleteAction::make()
                ->hidden(fn (VendorPayment $record): bool => strtolower($record->status) === 'posted'),
        ];
    }
}
