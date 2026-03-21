<?php

namespace App\Filament\Resources\Finance\PurchaseHeaders\Pages;

use App\Filament\Resources\Finance\PurchaseHeaders\PurchaseHeaderResource;
use App\Services\Finance\PurchasePostingService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseHeader extends CreateRecord
{
    protected static string $resource = PurchaseHeaderResource::class;

    private bool $shouldPost = false;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            Action::make('createAndPost')
                ->label('Create & Post')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->action(function (): void {
                    $this->shouldPost = true;
                    $this->create();
                }),
            $this->getCancelFormAction(),
        ];
    }

    protected function afterCreate(): void
    {
        if (! $this->shouldPost) {
            return;
        }

        try {
            app(PurchasePostingService::class)->post($this->record);
            Notification::make()->title('Document created and posted')->success()->send();
            $this->redirect(PurchaseHeaderResource::getUrl('index'));
        } catch (\RuntimeException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}
