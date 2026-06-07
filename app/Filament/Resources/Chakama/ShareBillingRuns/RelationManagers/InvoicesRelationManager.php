<?php

namespace App\Filament\Resources\Chakama\ShareBillingRuns\RelationManagers;

use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\SalesHeader;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Invoices Generated';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('no')
            ->modifyQueryUsing(fn ($query) => $query->with(['customer', 'salesLines']))
            ->columns([
                TextColumn::make('no')
                    ->label('Invoice No')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->url(fn (SalesHeader $record): string => route('admin.reports.invoice.pdf', ['invoice' => $record->no]), shouldOpenInNewTab: true),
                TextColumn::make('customer.name')
                    ->label('Member / Customer')
                    ->searchable(),
                TextColumn::make('posting_date')
                    ->label('Posted')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Due')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total (KES)')
                    ->state(fn (SalesHeader $record): float => (float) $record->salesLines->sum('line_amount'))
                    ->money('KES')
                    ->alignRight(),
                TextColumn::make('settlement')
                    ->label('Settlement')
                    ->state(function (SalesHeader $record): string {
                        $entry = CustomerLedgerEntry::query()
                            ->where('customer_id', $record->customer_id)
                            ->where('document_no', $record->no)
                            ->where('document_type', 'invoice')
                            ->first();

                        if (! $entry) {
                            return 'Not posted';
                        }

                        return $entry->is_open
                            ? 'Open · KES '.number_format((float) $entry->remaining_amount, 2)
                            : 'Settled';
                    })
                    ->badge()
                    ->color(function (SalesHeader $record): string {
                        $entry = CustomerLedgerEntry::query()
                            ->where('customer_id', $record->customer_id)
                            ->where('document_no', $record->no)
                            ->where('document_type', 'invoice')
                            ->first();

                        if (! $entry) {
                            return 'gray';
                        }

                        return $entry->is_open ? 'warning' : 'success';
                    }),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'posted' => 'Posted',
                    ]),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('open_pdf')
                    ->label('PDF')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (SalesHeader $record): string => route('admin.reports.invoice.pdf', ['invoice' => $record->no]))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('posting_date', 'desc');
    }
}
