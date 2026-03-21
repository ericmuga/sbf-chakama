<?php

namespace App\Filament\Resources\Finance\GlAccounts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GlAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('no')
                    ->label('Account No')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('account_type')
                    ->options([
                        'Posting' => 'Posting',
                        'Heading' => 'Heading',
                        'Total' => 'Total',
                        'Begin-Total' => 'Begin-Total',
                        'End-Total' => 'End-Total',
                    ])
                    ->required(),
            ]);
    }
}
