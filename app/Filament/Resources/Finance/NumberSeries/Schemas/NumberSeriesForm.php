<?php

namespace App\Filament\Resources\Finance\NumberSeries\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class NumberSeriesForm
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
                TextInput::make('prefix')
                    ->maxLength(20),
                TextInput::make('last_no')
                    ->label('Last No')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('length')
                    ->numeric()
                    ->default(6)
                    ->required(),
                Toggle::make('is_manual_allowed')
                    ->label('Manual Allowed'),
                Toggle::make('prevent_repeats')
                    ->label('Prevent Repeats'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
