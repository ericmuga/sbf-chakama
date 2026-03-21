<?php

namespace App\Filament\Resources\Finance\GeneralPostingSetups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GeneralPostingSetupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customerPostingGroup.code')
                    ->label('Customer Posting Group')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('servicePostingGroup.code')
                    ->label('Service Posting Group')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sales_account_no')
                    ->label('Sales A/C'),
            ])
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
