<?php

namespace App\Filament\Resources\Finance\GlEntries\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GlEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('posting_date')->label('Posting Date')->date()->sortable(),
                TextColumn::make('document_no')->label('Document No')->sortable()->searchable(),
                TextColumn::make('account_no')->label('Account No')->sortable()->searchable(),
                TextColumn::make('debit_amount')->label('Debit')->money()->sortable(),
                TextColumn::make('credit_amount')->label('Credit')->money()->sortable(),
                TextColumn::make('source_type')->label('Source Type')->sortable(),
            ])
            ->defaultSort('id', 'desc');
    }
}
