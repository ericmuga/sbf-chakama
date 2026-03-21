<?php

namespace App\Filament\Resources\Finance\VendorLedgerEntries\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VendorLedgerEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('entry_no')
                    ->label('Entry No')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('vendor.name')
                    ->label('Vendor')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('document_type')
                    ->label('Document Type')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('document_no')
                    ->label('Document No')
                    ->disabled()
                    ->dehydrated(false),
                DatePicker::make('posting_date')
                    ->label('Posting Date')
                    ->disabled()
                    ->dehydrated(false),
                DatePicker::make('due_date')
                    ->label('Due Date')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('amount')
                    ->label('Amount')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('remaining_amount')
                    ->label('Remaining Amount')
                    ->disabled()
                    ->dehydrated(false),
                Toggle::make('is_open')
                    ->label('Open')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }
}
