<?php

namespace App\Filament\Member\Resources\Claims\Schemas;

use App\Enums\ClaimPaymentMethod;
use App\Enums\ClaimType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ClaimForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Claim Details')
                    ->columns(2)
                    ->schema([
                        Select::make('claim_type')
                            ->options(ClaimType::class)
                            ->required(),
                        TextInput::make('subject')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ]),
                Section::make('Claim Items')
                    ->schema([
                        Repeater::make('lines')
                            ->relationship('lines')
                            ->schema([
                                TextInput::make('description')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, Get $get, ?string $state) => $set('line_amount', (float) ($state ?? 0) * (float) ($get('unit_amount') ?? 0))),
                                TextInput::make('unit_amount')
                                    ->label('Unit Amount (KES)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('KES')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, Get $get, ?string $state) => $set('line_amount', (float) ($state ?? 0) * (float) ($get('quantity') ?? 0))),
                                TextInput::make('line_amount')
                                    ->label('Line Total (KES)')
                                    ->numeric()
                                    ->prefix('KES')
                                    ->disabled()
                                    ->dehydrated(),
                                FileUpload::make('attachment_paths')
                                    ->label('Supporting Documents')
                                    ->multiple()
                                    ->directory('claim-line-attachments')
                                    ->visibility('private')
                                    ->downloadable()
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->addActionLabel('Add Item'),
                    ]),
                Section::make('Payment Details')
                    ->columns(3)
                    ->schema([
                        TextInput::make('payee_name')
                            ->required()
                            ->maxLength(255)
                            ->default(fn () => auth()->user()?->member?->name ?? auth()->user()?->name),
                        Select::make('payment_method')
                            ->options(ClaimPaymentMethod::class)
                            ->live(),
                        TextInput::make('mpesa_phone')
                            ->tel()
                            ->maxLength(20)
                            ->default(fn () => auth()->user()?->member?->mpesa_phone)
                            ->visible(fn (Get $get) => $get('payment_method') === ClaimPaymentMethod::Mpesa->value),
                        TextInput::make('bank_name')
                            ->maxLength(100)
                            ->default(fn () => auth()->user()?->member?->bank_name)
                            ->visible(fn (Get $get) => $get('payment_method') === ClaimPaymentMethod::BankTransfer->value),
                        TextInput::make('bank_account_name')
                            ->maxLength(100)
                            ->default(fn () => auth()->user()?->member?->bank_account_name)
                            ->visible(fn (Get $get) => $get('payment_method') === ClaimPaymentMethod::BankTransfer->value),
                        TextInput::make('bank_account_no')
                            ->maxLength(50)
                            ->default(fn () => auth()->user()?->member?->bank_account_no)
                            ->visible(fn (Get $get) => $get('payment_method') === ClaimPaymentMethod::BankTransfer->value),
                        TextInput::make('bank_branch')
                            ->maxLength(100)
                            ->default(fn () => auth()->user()?->member?->bank_branch)
                            ->visible(fn (Get $get) => $get('payment_method') === ClaimPaymentMethod::BankTransfer->value),
                    ]),
            ]);
    }
}
