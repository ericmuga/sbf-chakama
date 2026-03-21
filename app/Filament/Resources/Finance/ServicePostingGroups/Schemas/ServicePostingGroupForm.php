<?php

namespace App\Filament\Resources\Finance\ServicePostingGroups\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ServicePostingGroupForm
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
                TextInput::make('revenue_account_no')
                    ->label('Revenue Account No')
                    ->helperText('Required for sellable services.')
                    ->maxLength(20),
                TextInput::make('expense_account_no')
                    ->label('Expense Account No')
                    ->helperText('Required for purchasable services.')
                    ->maxLength(20),
            ]);
    }
}
