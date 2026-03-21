<?php

namespace App\Filament\Resources\Finance\CustomerLedgerEntries\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerLedgerEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entry_no')->label('Entry No')->sortable(),
                TextColumn::make('customer.name')->label('Customer')->sortable()->searchable(),
                TextColumn::make('document_type')->label('Type')->sortable(),
                TextColumn::make('document_no')->label('Document No')->sortable()->searchable(),
                TextColumn::make('posting_date')->label('Posting Date')->date()->sortable(),
                TextColumn::make('due_date')->label('Due Date')->date()->sortable(),
                TextColumn::make('amount')->label('Amount')->money()->sortable(),
                TextColumn::make('remaining_amount')->label('Remaining')->money()->sortable(),
                IconColumn::make('is_open')->label('Open')->boolean(),
            ])
            ->defaultSort('entry_no', 'desc');
    }
}
