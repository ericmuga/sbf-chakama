<?php

namespace App\Filament\Resources\Finance\DirectExpenses\Schemas;

use App\Models\Finance\Service;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class DirectExpenseForm
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
                Select::make('bank_account_id')
                    ->label('Bank Account')
                    ->relationship('bankAccount', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('posting_date')
                    ->default(today())
                    ->required(),
                TextInput::make('description')
                    ->maxLength(255),
                TextInput::make('status')
                    ->disabled()
                    ->dehydrated()
                    ->hidden(fn (string $operation): bool => $operation === 'create'),

                Repeater::make('lines')
                    ->relationship('lines')
                    ->label('Expense Lines')
                    ->schema([
                        Select::make('service_id')
                            ->label('Service')
                            ->options(fn () => Service::where('is_purchasable', true)->pluck('description', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if ($state) {
                                    $service = Service::find($state);
                                    $set('description', $service?->description);
                                }
                            }),
                        TextInput::make('description')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('amount')
                            ->numeric()
                            ->prefix('KES')
                            ->required()
                            ->minValue(0),
                    ])
                    ->columns(3)
                    ->addActionLabel('Add Line')
                    ->columnSpanFull(),
            ]);
    }
}
