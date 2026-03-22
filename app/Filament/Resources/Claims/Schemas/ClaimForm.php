<?php

namespace App\Filament\Resources\Claims\Schemas;

use App\Enums\ClaimPaymentMethod;
use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClaimForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Claim Details')
                    ->columns(3)
                    ->schema([
                        TextInput::make('no')
                            ->label('Claim No')
                            ->required()
                            ->maxLength(50),
                        Select::make('member_id')
                            ->relationship('member', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('claim_type')
                            ->options(ClaimType::class)
                            ->required(),
                        TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Select::make('status')
                            ->options(ClaimStatus::class)
                            ->default('draft')
                            ->required(),
                        Textarea::make('description')
                            ->columnSpanFull(),
                        TextInput::make('claimed_amount')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('approved_amount')
                            ->numeric()
                            ->minValue(0),
                        Select::make('approval_template_id')
                            ->relationship('approvalTemplate', 'name')
                            ->label('Approval Template'),
                        Textarea::make('rejection_reason')
                            ->columnSpanFull(),
                    ]),
                Section::make('Payee & Payment Details')
                    ->columns(3)
                    ->schema([
                        TextInput::make('payee_name')
                            ->required()
                            ->maxLength(255),
                        Select::make('payment_method')
                            ->options(ClaimPaymentMethod::class),
                        TextInput::make('mpesa_phone')
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('bank_name')
                            ->maxLength(100),
                        TextInput::make('bank_account_name')
                            ->maxLength(100),
                        TextInput::make('bank_account_no')
                            ->maxLength(50),
                        TextInput::make('bank_branch')
                            ->maxLength(100),
                    ]),
            ]);
    }
}
