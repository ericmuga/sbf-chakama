<?php

namespace App\Filament\Resources\Finance\SalesHeaders\Pages;

use App\Filament\Resources\Finance\SalesHeaders\SalesHeaderResource;
use App\Services\Finance\SalesPostingService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesHeader extends CreateRecord
{
    protected static string $resource = SalesHeaderResource::class;

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
            app(SalesPostingService::class)->post($this->record);
            Notification::make()->title('Document created and posted')->success()->send();
            $this->redirect(SalesHeaderResource::getUrl('index'));
        } catch (\RuntimeException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}
