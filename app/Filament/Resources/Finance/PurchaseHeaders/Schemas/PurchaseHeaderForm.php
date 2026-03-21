<?php

namespace App\Filament\Resources\Finance\PurchaseHeaders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PurchaseHeaderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('no')
                    ->label('Document No')
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Select::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('vendor_posting_group_id')
                    ->label('Vendor Posting Group')
                    ->relationship('vendorPostingGroup', 'description')
                    ->searchable()
                    ->preload(),
                DatePicker::make('posting_date')
                    ->required(),
                DatePicker::make('due_date'),
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
