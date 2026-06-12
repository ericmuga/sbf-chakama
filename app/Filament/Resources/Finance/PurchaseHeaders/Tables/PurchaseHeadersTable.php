<?php

namespace App\Filament\Resources\Finance\PurchaseHeaders\Tables;

use App\Models\Finance\PurchaseHeader;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseHeadersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Document No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('claim.no')
                    ->label('Claim Ref')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('posting_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'posted' => 'success',
                        default => 'warning',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'posted' => 'Posted',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'] ?? null;

                        return $status
                            ? $query->whereRaw('LOWER(status) = ?', [$status])
                            : $query;
                    }),
            ])
            ->defaultSort('posting_date', 'desc')
            ->recordActions([
                EditAction::make()
                    ->hidden(fn (PurchaseHeader $record): bool => $record->isPosted()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
