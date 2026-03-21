<?php

namespace App\Filament\Resources\Finance\VendorPostingGroups\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VendorPostingGroupForm
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
                TextInput::make('payables_account_no')
                    ->label('Payables Account No')
                    ->required()
                    ->maxLength(20),
            ]);
    }
}
