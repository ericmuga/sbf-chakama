<?php

namespace App\Filament\Resources\Finance\Vendors\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('no')
                    ->label('Vendor No')
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('vendor_posting_group_id')
                    ->label('Vendor Posting Group')
                    ->relationship('vendorPostingGroup', 'description')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('payment_terms_code')
                    ->label('Payment Terms')
                    ->relationship('paymentTerms', 'description')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
