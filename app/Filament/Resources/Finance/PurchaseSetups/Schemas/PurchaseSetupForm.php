<?php

namespace App\Filament\Resources\Finance\PurchaseSetups\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class PurchaseSetupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('invoice_nos')
                    ->label('Invoice Number Series')
                    ->relationship('invoiceNumberSeries', 'code')
                    ->searchable()
                    ->preload(),
                Select::make('posted_invoice_nos')
                    ->label('Posted Invoice Number Series')
                    ->relationship('postedInvoiceNumberSeries', 'code')
                    ->searchable()
                    ->preload(),
                Select::make('vendor_nos')
                    ->label('Vendor Number Series')
                    ->relationship('vendorNumberSeries', 'code')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
