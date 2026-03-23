<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Enums\ProjectModule;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('module')
                    ->badge()
                    ->color(fn ($state) => $state instanceof ProjectModule ? $state->color() : 'gray'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => $state instanceof ProjectStatus ? $state->color() : 'gray'),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn ($state) => $state instanceof ProjectPriority ? $state->color() : 'gray'),
                TextColumn::make('budget')
                    ->money('KES')
                    ->sortable(),
                TextColumn::make('spent')
                    ->money('KES')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Created By'),
            ])
            ->filters([
                SelectFilter::make('module')
                    ->options(ProjectModule::class),
                SelectFilter::make('status')
                    ->options(ProjectStatus::class),
                SelectFilter::make('priority')
                    ->options(ProjectPriority::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }
}
