<?php

namespace App\Filament\Resources\Finance\SalesHeaders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SalesHeaderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('no')
                    ->label('Document No')
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Select::make('document_type')
                    ->options([
                        'invoice' => 'Invoice',
                        'credit_memo' => 'Credit Memo',
                    ])
                    ->required(),
                Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('customer_posting_group_id')
                    ->label('Customer Posting Group')
                    ->relationship('customerPostingGroup', 'description')
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
