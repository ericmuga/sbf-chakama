<?php

namespace App\Filament\Resources\Finance\BankLedgerEntries\Tables;

use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BankLedgerEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    TextColumn::make('entry_no')->label('Entry #')->sortable()->grow(false),
                    TextColumn::make('bankAccount.name')->label('Bank Account')->sortable()->searchable(),
                    TextColumn::make('document_type')->label('Type')->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'receipt', 'income' => 'success',
                            'payment', 'expense' => 'danger',
                            default => 'gray',
                        })
                        ->grow(false),
                    TextColumn::make('document_no')->label('Document No')->sortable()->searchable()->grow(false),
                    TextColumn::make('posting_date')->label('Date')->date()->sortable()->grow(false),
                    TextColumn::make('amount')->label('Amount')
                        ->money()
                        ->sortable()
                        ->color(fn (mixed $record): string => $record->amount >= 0 ? 'success' : 'danger')
                        ->grow(false),
                ]),
                Panel::make([
                    Stack::make([
                        TextColumn::make('description')->label('Description')->icon('heroicon-o-document-text')->placeholder('—'),
                        TextColumn::make('createdBy.name')->label('Posted by')->icon('heroicon-o-user'),
                        TextColumn::make('created_at')->label('Posted at')->dateTime()->icon('heroicon-o-clock'),
                    ])->space(1),
                ])->collapsible(),
            ])
            ->filters([
                SelectFilter::make('bank_account_id')
                    ->label('Bank Account')
                    ->relationship('bankAccount', 'name'),
                SelectFilter::make('document_type')
                    ->label('Type')
                    ->options([
                        'receipt' => 'Receipt',
                        'payment' => 'Payment',
                        'income' => 'Income',
                        'expense' => 'Expense',
                    ]),
            ])
            ->defaultSort('entry_no', 'desc');
    }
}
