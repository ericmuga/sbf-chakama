<?php

namespace App\Filament\Resources\Finance\DirectIncomes\Tables;

use App\Models\Finance\DirectIncome;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DirectIncomesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Document No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bankAccount.name')
                    ->label('Bank Account')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('posting_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'posted' => 'success',
                        default => 'warning',
                    }),
                TextColumn::make('total')
                    ->label('Total')
                    ->state(fn (DirectIncome $record): string => 'KES '.number_format($record->lines->sum('amount'), 2)),
            ])
            ->defaultSort('posting_date', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
