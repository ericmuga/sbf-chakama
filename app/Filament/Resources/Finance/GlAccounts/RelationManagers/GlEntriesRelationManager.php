<?php

namespace App\Filament\Resources\Finance\GlAccounts\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GlEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'glEntries';

    protected static ?string $title = 'G/L Entries';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_no')
            ->columns([
                TextColumn::make('posting_date')->label('Posting Date')->date()->sortable(),
                TextColumn::make('document_no')->label('Document No')->searchable(),
                TextColumn::make('debit_amount')->label('Debit')->money()->sortable(),
                TextColumn::make('credit_amount')->label('Credit')->money()->sortable(),
                TextColumn::make('source_type')->label('Source'),
            ])
            ->defaultSort('id', 'desc');
    }
}
