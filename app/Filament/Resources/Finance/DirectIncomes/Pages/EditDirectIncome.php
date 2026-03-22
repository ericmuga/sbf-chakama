<?php

namespace App\Filament\Resources\Finance\DirectIncomes\Pages;

use App\Filament\Resources\Finance\DirectIncomes\DirectIncomeResource;
use App\Models\Finance\DirectIncome;
use App\Services\Finance\DirectIncomePostingService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDirectIncome extends EditRecord
{
    protected static string $resource = DirectIncomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label('Post Document')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Post Club Income')
                ->modalDescription('This will post the document to the G/L ledger. This cannot be undone.')
                ->hidden(fn (DirectIncome $record): bool => $record->status === 'posted')
                ->action(function (DirectIncome $record): void {
                    try {
                        app(DirectIncomePostingService::class)->post($record);
                        Notification::make()->title('Posted successfully')->success()->send();
                        $this->redirect(DirectIncomeResource::getUrl('index'));
                    } catch (\RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            DeleteAction::make()
                ->hidden(fn (DirectIncome $record): bool => $record->status === 'posted'),
        ];
    }
}
