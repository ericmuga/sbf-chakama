<?php

namespace App\Filament\Resources\Finance\DirectExpenses\Pages;

use App\Filament\Resources\Finance\DirectExpenses\DirectExpenseResource;
use App\Models\Finance\DirectExpense;
use App\Services\Finance\DirectExpensePostingService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDirectExpense extends EditRecord
{
    protected static string $resource = DirectExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label('Post Document')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Post Club Expense')
                ->modalDescription('This will post the document to the G/L ledger. This cannot be undone.')
                ->hidden(fn (DirectExpense $record): bool => $record->status === 'posted')
                ->action(function (DirectExpense $record): void {
                    try {
                        app(DirectExpensePostingService::class)->post($record);
                        Notification::make()->title('Posted successfully')->success()->send();
                        $this->redirect(DirectExpenseResource::getUrl('index'));
                    } catch (\RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            DeleteAction::make()
                ->hidden(fn (DirectExpense $record): bool => $record->status === 'posted'),
        ];
    }
}
