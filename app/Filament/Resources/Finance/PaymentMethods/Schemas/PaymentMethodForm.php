<?php

namespace App\Filament\Resources\Finance\PaymentMethods\Schemas;

use App\Models\Finance\BankAccount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                Select::make('bank_account_id')
                    ->label('Default Bank Account')
                    ->options(BankAccount::query()->pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),
            ]);
    }
}
