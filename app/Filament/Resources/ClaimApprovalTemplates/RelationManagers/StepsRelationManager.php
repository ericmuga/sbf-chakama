<?php

namespace App\Filament\Resources\ClaimApprovalTemplates\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StepsRelationManager extends RelationManager
{
    protected static string $relationship = 'steps';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('step_order')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->label('Step Order'),
                TextInput::make('label')
                    ->maxLength(255),
                Select::make('approver_user_id')
                    ->label('Approver')
                    ->relationship('approver', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('role_name')
                    ->label('Role Name')
                    ->maxLength(100),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->defaultSort('step_order')
            ->columns([
                TextColumn::make('step_order')
                    ->label('Step')
                    ->sortable(),
                TextColumn::make('label')
                    ->searchable(),
                TextColumn::make('approver.name')
                    ->label('Approver')
                    ->searchable(),
                TextColumn::make('role_name')
                    ->label('Role'),
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
