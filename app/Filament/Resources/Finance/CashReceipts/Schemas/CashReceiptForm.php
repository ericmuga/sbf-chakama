<?php

namespace App\Filament\Resources\Finance\CashReceipts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CashReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('no')
                    ->label('Receipt No')
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('bank_account_id')
                    ->label('Bank Account')
                    ->relationship('bankAccount', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('posting_date')
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                Select::make('status')
                    ->options([
                        'open' => 'Open',
                        'posted' => 'Posted',
                    ])
                    ->default('open')
                    ->required(),
            ]);
    }
}
