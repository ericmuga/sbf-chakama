<?php

namespace App\Filament\Resources\Finance\SalesHeaders\Pages;

use App\Filament\Resources\Finance\SalesHeaders\SalesHeaderResource;
use App\Models\Finance\SalesHeader;
use App\Services\Finance\SalesPostingService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSalesHeader extends EditRecord
{
    protected static string $resource = SalesHeaderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label('Post')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Post Sales Invoice')
                ->modalDescription('This will post the document to the customer and G/L ledgers. This cannot be undone.')
                ->hidden(fn (SalesHeader $record): bool => $record->status === 'posted')
                ->action(function (SalesHeader $record): void {
                    try {
                        app(SalesPostingService::class)->post($record);
                        Notification::make()->title('Posted successfully')->success()->send();
                        $this->redirect(SalesHeaderResource::getUrl('index'));
                    } catch (\RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            DeleteAction::make()
                ->hidden(fn (SalesHeader $record): bool => $record->status === 'posted'),
        ];
    }
}
