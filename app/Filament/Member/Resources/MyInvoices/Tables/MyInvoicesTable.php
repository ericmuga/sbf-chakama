<?php

namespace App\Filament\Member\Resources\MyInvoices\Tables;

use App\Filament\Member\Pages\MakePayment;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MyInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_no')
                    ->label('Invoice No')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('posting_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn ($record) => $record->due_date?->isPast() ? 'danger' : null),

                TextColumn::make('amount')
                    ->label('Invoice Amount (KES)')
                    ->numeric(decimalPlaces: 2)
                    ->alignRight(),

                TextColumn::make('remaining_amount')
                    ->label('Outstanding (KES)')
                    ->numeric(decimalPlaces: 2)
                    ->alignRight()
                    ->weight('bold')
                    ->color('warning'),
            ])
            ->defaultSort('due_date', 'asc')
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateHeading('No pending bills')
            ->emptyStateDescription('You have no outstanding invoices at this time.')
            ->headerActions([
                Action::make('pay_now')
                    ->label('Pay Now')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('success')
                    ->url(fn (): string => MakePayment::getUrl()),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
