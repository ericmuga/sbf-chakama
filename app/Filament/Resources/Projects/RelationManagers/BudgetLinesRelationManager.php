<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\Finance\GlAccount;
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

class BudgetLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'budgetLines';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('gl_account_no')
                    ->label('GL Account')
                    ->options(
                        GlAccount::query()
                            ->where('account_type', 'Posting')
                            ->pluck('no', 'no')
                    )
                    ->searchable()
                    ->required(),
                TextInput::make('description')
                    ->maxLength(255),
                TextInput::make('budgeted_amount')
                    ->numeric()
                    ->required()
                    ->minValue(0),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('gl_account_no')
                    ->label('GL Account'),
                TextColumn::make('description'),
                TextColumn::make('budgeted_amount')
                    ->money('KES')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->numeric()
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
