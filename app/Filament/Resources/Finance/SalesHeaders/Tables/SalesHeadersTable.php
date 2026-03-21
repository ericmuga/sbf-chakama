<?php

namespace App\Filament\Resources\Finance\SalesHeaders\Tables;

use App\Models\Finance\SalesHeader;
use App\Services\Finance\SalesPostingService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesHeadersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Document No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('document_type')
                    ->badge(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('posting_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
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
                    ->hidden(fn (SalesHeader $record): bool => $record->status === 'posted')
                    ->action(function (SalesHeader $record): void {
                        try {
                            app(SalesPostingService::class)->post($record);
                            Notification::make()->title('Posted successfully')->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                EditAction::make()
                    ->hidden(fn (SalesHeader $record): bool => $record->status === 'posted'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
