<?php

namespace App\Filament\Resources\Finance\ServicePostingGroups\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServicePostingGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Code')->sortable()->searchable(),
                TextColumn::make('description')->label('Description')->sortable()->searchable(),
                TextColumn::make('revenue_account_no')->label('Revenue Account')->sortable(),
                TextColumn::make('expense_account_no')->label('Expense Account')->sortable(),
            ])
            ->defaultSort('code');
    }
}
