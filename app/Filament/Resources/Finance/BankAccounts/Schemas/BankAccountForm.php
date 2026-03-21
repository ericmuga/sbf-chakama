<?php

namespace App\Filament\Resources\Finance\BankAccounts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('bank_account_no')
                    ->label('Bank Account No')
                    ->maxLength(50),
                Select::make('bank_posting_group_id')
                    ->label('Bank Posting Group')
                    ->relationship('bankPostingGroup', 'description')
                    ->searchable()
                    ->preload(),
                TextInput::make('currency_code')
                    ->label('Currency Code')
                    ->maxLength(10)
                    ->default('KES'),
            ]);
    }
}
