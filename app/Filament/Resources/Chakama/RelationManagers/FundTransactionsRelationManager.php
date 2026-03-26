<?php

namespace App\Filament\Resources\Chakama\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FundTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Transactions';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('transaction_type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('description')
                    ->limit(50),
                TextColumn::make('amount')
                    ->money('KES')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('running_balance')
                    ->label('Running Balance')
                    ->money('KES')
                    ->sortable(),
            ])
            ->filters([])
            ->defaultSort('created_at', 'desc');
    }
}
