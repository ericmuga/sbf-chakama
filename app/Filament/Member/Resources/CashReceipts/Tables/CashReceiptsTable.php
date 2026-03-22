<?php

namespace App\Filament\Member\Resources\CashReceipts\Tables;

use App\Filament\Member\Pages\MakePayment;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CashReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Receipt No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('posting_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount (KES)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->defaultSort('posting_date', 'desc')
            ->filters([])
            ->headerActions([
                Action::make('make_payment')
                    ->label('Make Payment')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('success')
                    ->url(fn (): string => MakePayment::getUrl()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
