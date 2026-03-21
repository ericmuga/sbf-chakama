<?php

namespace App\Filament\Resources\Finance\VendorPayments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VendorPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('no')
                    ->label('Payment No')
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Select::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
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
