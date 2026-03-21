<?php

namespace App\Filament\Resources\Finance\SalesSetups\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesSetupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_nos')
                    ->label('Invoice No Series'),
                TextColumn::make('posted_invoice_nos')
                    ->label('Posted Invoice No Series'),
                TextColumn::make('customer_nos')
                    ->label('Customer No Series'),
                TextColumn::make('member_nos')
                    ->label('Member No Series'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
