<?php

namespace App\Filament\Resources\Finance\SalesSetups\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class SalesSetupForm
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
                Select::make('customer_nos')
                    ->label('Customer Number Series')
                    ->relationship('customerNumberSeries', 'code')
                    ->searchable()
                    ->preload(),
                Select::make('member_nos')
                    ->label('Member Number Series')
                    ->relationship('memberNumberSeries', 'code')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
