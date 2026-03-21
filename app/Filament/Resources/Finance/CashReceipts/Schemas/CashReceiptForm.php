<?php

namespace App\Filament\Resources\Finance\CashReceipts\Schemas;

use App\Models\Finance\PaymentMethod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CashReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('no')
                    ->label('Receipt No')
                    ->disabled()
                    ->dehydrated()
                    ->hidden(fn (string $operation): bool => $operation === 'create'),
                Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('payment_method_id')
                    ->label('Payment Method')
                    ->options(PaymentMethod::query()->pluck('description', 'id'))
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?int $state, Set $set): void {
                        if ($state) {
                            $method = PaymentMethod::find($state);
                            $set('bank_account_id', $method?->bank_account_id);
                        }
                    }),
                Select::make('bank_account_id')
                    ->label('Bank Account')
                    ->relationship('bankAccount', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                DatePicker::make('posting_date')
                    ->default(now())
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
            ]);
    }
}
