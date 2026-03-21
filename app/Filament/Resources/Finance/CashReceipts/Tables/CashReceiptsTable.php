<?php

namespace App\Filament\Resources\Finance\CashReceipts\Tables;

use App\Models\Finance\CashReceipt;
use App\Services\Finance\ReceiptPostingService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CashReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Receipt No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('paymentMethod.description')
                    ->label('Payment Method'),
                TextColumn::make('bankAccount.name')
                    ->label('Bank Account'),
                TextColumn::make('posting_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('amount')
                    ->money('KES')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'posted' => 'success',
                        default => 'warning',
                    }),
            ])
            ->defaultSort('posting_date', 'desc')
            ->recordActions([
                Action::make('post')
                    ->label('Post')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->hidden(fn (CashReceipt $record): bool => strtolower($record->status) === 'posted')
                    ->action(function (CashReceipt $record): void {
                        try {
                            app(ReceiptPostingService::class)->post($record->load(['bankAccount.bankPostingGroup', 'customer.customerPostingGroup']));
                            Notification::make()->title('Receipt posted successfully')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                EditAction::make()
                    ->hidden(fn (CashReceipt $record): bool => strtolower($record->status) === 'posted'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
