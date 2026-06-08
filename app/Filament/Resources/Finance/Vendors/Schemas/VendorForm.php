<?php

namespace App\Filament\Resources\Finance\Vendors\Schemas;

use App\Filament\Resources\Finance\Vendors\VendorResource;
use App\Models\Finance\NumberSeries;
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
                    ->default(fn (): string => NumberSeries::preview(VendorResource::numberSeriesCode() ?? ''))
                    ->placeholder('Assigned automatically on save')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Generated from the vendor number series when the record is saved.'),
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
