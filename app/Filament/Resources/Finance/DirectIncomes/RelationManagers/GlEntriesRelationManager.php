<?php

namespace App\Filament\Resources\Finance\DirectExpenses\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GlEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'glEntries';

    protected static ?string $title = 'Double-Entry Ledger';

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
                TextColumn::make('account_no')->label('Account No')->sortable(),
                TextColumn::make('debit_amount')->label('Debit (Dr)')->money()->sortable()
                    ->color(fn (mixed $record): string => $record->debit_amount > 0 ? 'danger' : 'gray'),
                TextColumn::make('credit_amount')->label('Credit (Cr)')->money()->sortable()
                    ->color(fn (mixed $record): string => $record->credit_amount > 0 ? 'success' : 'gray'),
                TextColumn::make('source_type')->label('Entry Type')->badge(),
                TextColumn::make('created_at')->label('Posted at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'asc')
            ->paginated(false);
    }
}
