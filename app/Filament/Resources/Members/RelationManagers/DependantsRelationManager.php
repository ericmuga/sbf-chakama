<?php

namespace App\Filament\Resources\Members\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DependantsRelationManager extends RelationManager
{
    protected static string $relationship = 'dependants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
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
                    ->maxLength(50),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                DatePicker::make('date_of_birth')
                    ->label('Date of Birth'),
                TextInput::make('relationship')
                    ->maxLength(100),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('relationship')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('date_of_birth')
                    ->label('Date of Birth')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
