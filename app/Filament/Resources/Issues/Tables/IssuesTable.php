<?php

namespace App\Filament\Resources\Issues\Tables;

use App\Enums\IssueCategory;
use App\Enums\IssuePortal;
use App\Enums\IssueStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IssuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Issue')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('portal_type')
                    ->label('Portal')
                    ->badge(),
                TextColumn::make('category')
                    ->label('Type')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('resource')
                    ->label('Resource')
                    ->toggleable(),
                TextColumn::make('date_assigned')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('qa_test_result')
                    ->label('QA')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Pass' => 'success',
                        'Fail' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('release.version')
                    ->label('Release')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('closure_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(IssueStatus::class),
                SelectFilter::make('portal_type')
                    ->label('Portal')
                    ->options(IssuePortal::class),
                SelectFilter::make('category')
                    ->label('Type')
                    ->options(IssueCategory::class),
                SelectFilter::make('release')
                    ->relationship('release', 'version'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
