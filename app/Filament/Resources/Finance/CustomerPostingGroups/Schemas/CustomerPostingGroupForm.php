<?php

namespace App\Filament\Resources\Finance\CustomerPostingGroups\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerPostingGroupForm
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
                TextInput::make('receivables_account_no')
                    ->label('Receivables Account No')
                    ->maxLength(20),
                TextInput::make('service_charge_account_no')
                    ->label('Service Charge Account No')
                    ->maxLength(20),
            ]);
    }
}
