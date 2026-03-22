<?php

namespace App\Filament\Resources\Members\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('type')->default('member'),
                Section::make('Member Details')
                    ->schema([
                        TextInput::make('no')
                            ->label('Member Number')
                            ->disabled()
                            ->dehydrated()
                            ->hidden(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(20),
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),
                        Select::make('user_id')
                            ->label('User Account')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('identity_type')
                            ->label('Identity Type')
                            ->options([
                                'national_id' => 'National ID',
                                'passport_no' => 'Passport No',
                                'birth_cert_no' => 'Birth Certificate No',
                                'driving_licence_no' => 'Driving Licence No',
                                'pin_no' => 'PIN No',
                            ])
                            ->required(),
                        TextInput::make('identity_no')
                            ->label('Identity Number')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                        Select::make('member_status')
                            ->label('Status')
                            ->options([
                                'active' => 'Active',
                                'lapsed' => 'Lapsed',
                                'suspended' => 'Suspended',
                            ]),
                        TextInput::make('customer_no')
                            ->label('Customer No')
                            ->disabled()
                            ->dehydrated()
                            ->hidden(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(20),
                        TextInput::make('vendor_no')
                            ->label('Vendor No')
                            ->disabled()
                            ->dehydrated()
                            ->hidden(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(20),
                        Toggle::make('is_chakama')
                            ->label('Chakama Member')
                            ->inline(false),
                        Toggle::make('is_sbf')
                            ->label('SBF Member')
                            ->inline(false),
                        Toggle::make('exclude_from_billing')
                            ->label('Exclude from Billing')
                            ->helperText('Skip this member when processing scheduled invoices')
                            ->inline(false),
                    ])
                    ->columns(2),
            ]);
    }
}
