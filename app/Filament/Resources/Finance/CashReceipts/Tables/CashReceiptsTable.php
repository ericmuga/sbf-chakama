<?php

namespace App\Filament\Resources\Finance\CashReceipts\Tables;

use App\Models\Finance\CashReceipt;
use App\Models\Finance\PaymentMethod;
use App\Services\Finance\ReceiptPostingService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->label('Member')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('shareSubscription.no')
                    ->label('Share Ref')
                    ->placeholder('—')
                    ->searchable(),
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
            ->filters([
                SelectFilter::make('payment_method_id')
                    ->label('Payment Method')
                    ->options(PaymentMethod::query()->pluck('description', 'id'))
                    ->searchable(),
                SelectFilter::make('status')
                    ->options([
                        'posted' => 'Posted',
                        'pending' => 'Pending',
                    ]),
                Filter::make('posting_date')
                    ->form([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('to')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('posting_date', '>=', $data['from']))
                            ->when($data['to'], fn ($q) => $q->whereDate('posting_date', '<=', $data['to']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'From: '.$data['from'];
                        }
                        if ($data['to'] ?? null) {
                            $indicators[] = 'To: '.$data['to'];
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                Action::make('downloadPdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->hidden(fn (CashReceipt $record): bool => strtolower($record->status) !== 'posted')
                    ->url(fn (CashReceipt $record): string => route('admin.reports.receipt.pdf', $record))
                    ->openUrlInNewTab(),
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
