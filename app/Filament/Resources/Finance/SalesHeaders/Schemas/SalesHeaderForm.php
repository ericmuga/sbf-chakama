<?php

namespace App\Filament\Resources\Finance\SalesHeaders\Schemas;

use App\Models\Finance\Customer;
use App\Models\Finance\Service;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SalesHeaderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('no')
                    ->label('Document No')
                    ->disabled()
                    ->dehydrated()
                    ->hidden(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(50),
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
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        if ($state) {
                            $customer = Customer::find($state);
                            $set('customer_posting_group_id', $customer?->customer_posting_group_id);
                        }
                    }),
                Select::make('customer_posting_group_id')
                    ->label('Customer Posting Group')
                    ->relationship('customerPostingGroup', 'description')
                    ->searchable()
                    ->preload()
                    ->disabled()
                    ->dehydrated(),
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

                Repeater::make('salesLines')
                    ->relationship('salesLines')
                    ->label('Sales Lines')
                    ->schema([
                        Select::make('service_id')
                            ->label('Service')
                            ->options(fn () => Service::all()->pluck('description', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if ($state) {
                                    $service = Service::find($state);
                                    $set('description', $service?->description);
                                    $set('unit_price', $service?->unit_price);
                                }
                            }),
                        TextInput::make('description')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                $set('line_amount', round((float) $state * (float) $get('unit_price'), 4));
                            }),
                        TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                $set('line_amount', round((float) $get('quantity') * (float) $state, 4));
                            }),
                        TextInput::make('line_amount')
                            ->label('Line Amount')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(5)
                    ->addActionLabel('Add Line')
                    ->columnSpanFull(),
            ]);
    }
}
