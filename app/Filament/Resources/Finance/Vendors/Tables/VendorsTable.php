<?php

namespace App\Filament\Resources\Finance\Vendors\Tables;

use App\Models\Finance\VendorLedgerEntry;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withSum('vendorLedgerEntries as balance_sum', 'amount'))
            ->columns([
                TextColumn::make('no')
                    ->label('Vendor No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendorPostingGroup.description')
                    ->label('Posting Group')
                    ->badge(),
                TextColumn::make('payment_terms_code')
                    ->label('Payment Terms'),
                TextColumn::make('balance_sum')
                    ->label('Balance (KES)')
                    ->badge()
                    ->color(fn ($state): string => (float) $state > 0 ? 'danger' : ((float) $state < 0 ? 'success' : 'gray'))
                    ->formatStateUsing(fn ($state): string => number_format(abs((float) $state), 2).((float) $state > 0 ? ' DR' : ((float) $state < 0 ? ' CR' : '')))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('vendor_posting_group_id')
                    ->label('Posting Group')
                    ->relationship('vendorPostingGroup', 'description')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('with_balance')
                    ->label('Outstanding balance')
                    ->placeholder('All')
                    ->trueLabel('With balance')
                    ->falseLabel('Zero balance')
                    ->queries(
                        true: fn (Builder $q): Builder => $q->whereIn('id', static::vendorIdsWithBalance()),
                        false: fn (Builder $q): Builder => $q->whereNotIn('id', static::vendorIdsWithBalance()),
                        blank: fn (Builder $q): Builder => $q,
                    ),
            ])
            ->defaultSort('no')
            ->headerActions([
                Action::make('exportVendorsExcel')
                    ->label('Export to Excel')
                    ->icon(Heroicon::ArrowDownTray)
                    ->color('success')
                    ->url(fn ($livewire): string => route('admin.reports.vendors.export-excel', array_filter([
                        'posting_group' => $livewire->getTableFilterState('vendor_posting_group_id')['value'] ?? null,
                        'with_balance' => ($livewire->getTableFilterState('with_balance')['value'] ?? null) === true ? 1 : null,
                    ])))
                    ->openUrlInNewTab(),
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

    protected static function vendorIdsWithBalance(): Builder
    {
        return VendorLedgerEntry::query()
            ->groupBy('vendor_id')
            ->havingRaw('SUM(amount) <> 0')
            ->select('vendor_id');
    }
}
