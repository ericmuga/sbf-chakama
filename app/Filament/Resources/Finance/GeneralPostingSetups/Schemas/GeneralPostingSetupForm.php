<?php

namespace App\Filament\Resources\Finance\GeneralPostingSetups\Schemas;

use App\Models\Finance\GlAccount;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class GeneralPostingSetupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_posting_group_id')
                    ->label('Customer Posting Group')
                    ->relationship('customerPostingGroup', 'description')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('service_posting_group_id')
                    ->label('Service Posting Group')
                    ->relationship('servicePostingGroup', 'description')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('sales_account_no')
                    ->label('Sales Account')
                    ->relationship('salesAccount', 'name')
                    ->getOptionLabelFromRecordUsing(fn (GlAccount $record): string => "{$record->no} - {$record->name}")
                    ->searchable(['no', 'name'])
                    ->preload()
                    ->required(),
            ]);
    }
}
