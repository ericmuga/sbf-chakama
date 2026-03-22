<?php

namespace App\Filament\Resources\Finance\GlEntries\Tables;

use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GlEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    TextColumn::make('posting_date')->label('Date')->date()->sortable()->grow(false),
                    TextColumn::make('document_no')->label('Document No')->sortable()->searchable()->grow(false),
                    TextColumn::make('account_no')->label('Account No')->sortable()->searchable(),
                    TextColumn::make('debit_amount')->label('Debit')->money()->sortable()->grow(false),
                    TextColumn::make('credit_amount')->label('Credit')->money()->sortable()->grow(false),
                ]),
                Panel::make([
                    Stack::make([
                        TextColumn::make('source_type')->label('Source')->icon('heroicon-o-document-text'),
                        TextColumn::make('createdBy.name')->label('Posted by')->icon('heroicon-o-user'),
                        TextColumn::make('created_at')->label('Posted at')->dateTime()->icon('heroicon-o-clock'),
                    ])->space(1),
                ])->collapsible(),
            ])
            ->defaultSort('id', 'desc');
    }
}
