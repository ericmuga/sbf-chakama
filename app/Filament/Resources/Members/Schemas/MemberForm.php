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
                            ->maxLength(20)
                            ->unique(ignoreRecord: true),
                        Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('national_id')
                            ->label('National ID')
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
                            ->maxLength(20),
                        TextInput::make('vendor_no')
                            ->label('Vendor No')
                            ->maxLength(20),
                        Toggle::make('is_chakama')
                            ->label('Chakama Member')
                            ->inline(false),
                        Toggle::make('is_sbf')
                            ->label('SBF Member')
                            ->inline(false),
                    ])
                    ->columns(2),
            ]);
    }
}
