<?php

namespace App\Filament\Resources\Finance\PurchaseHeaders\Pages;

use App\Filament\Resources\Finance\PurchaseHeaders\PurchaseHeaderResource;
use App\Models\Finance\PurchaseHeader;
use App\Services\Finance\PurchasePostingService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseHeader extends EditRecord
{
    protected static string $resource = PurchaseHeaderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label('Post')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Post Purchase Invoice')
                ->modalDescription('This will post the document to the vendor and G/L ledgers. This cannot be undone.')
                ->hidden(fn (PurchaseHeader $record): bool => $record->status === 'posted')
                ->action(function (PurchaseHeader $record): void {
                    try {
                        app(PurchasePostingService::class)->post($record);
                        Notification::make()->title('Posted successfully')->success()->send();
                        $this->redirect(PurchaseHeaderResource::getUrl('index'));
                    } catch (\RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            DeleteAction::make()
                ->hidden(fn (PurchaseHeader $record): bool => $record->status === 'posted'),
        ];
    }
}
