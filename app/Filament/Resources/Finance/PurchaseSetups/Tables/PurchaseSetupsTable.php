<?php

namespace App\Filament\Resources\Finance\PurchaseSetups\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseSetupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_nos')
                    ->label('Invoice No Series'),
                TextColumn::make('posted_invoice_nos')
                    ->label('Posted Invoice No Series'),
                TextColumn::make('vendor_nos')
                    ->label('Vendor No Series'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
