<?php

namespace App\Filament\Widgets\Members;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Member;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class MemberLedgerWidget extends TableWidget
{
    public ?Member $record = null;

    protected static ?string $heading = 'Ledger Entries';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->ledgerQuery())
            ->columns([
                TextColumn::make('posting_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'invoice' => 'warning',
                        'payment' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('document_no')
                    ->label('Document No')
                    ->searchable(),
                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y'),
                TextColumn::make('amount')
                    ->label('Debit')
                    ->state(fn (CustomerLedgerEntry $record): string => $record->amount > 0 ? number_format((float) $record->amount, 2) : '—')
                    ->alignRight()
                    ->color('warning'),
                TextColumn::make('credit')
                    ->label('Credit')
                    ->state(fn (CustomerLedgerEntry $record): string => $record->amount < 0 ? number_format(abs((float) $record->amount), 2) : '—')
                    ->alignRight()
                    ->color('success'),
                TextColumn::make('remaining_amount')
                    ->label('Remaining')
                    ->state(fn (CustomerLedgerEntry $record): string => number_format((float) $record->remaining_amount, 2))
                    ->alignRight(),
                TextColumn::make('is_open')
                    ->label('Open')
                    ->badge()
                    ->state(fn (CustomerLedgerEntry $record): string => $record->is_open ? 'Open' : 'Closed')
                    ->color(fn (string $state): string => $state === 'Open' ? 'danger' : 'success'),
            ])
            ->defaultSort('posting_date', 'asc')
            ->recordActions([])
            ->toolbarActions([]);
    }

    private function ledgerQuery(): Builder
    {
        if (! $this->record?->customer_no) {
            return CustomerLedgerEntry::query()->whereNull('id');
        }

        $customerId = Customer::where('no', $this->record->customer_no)->value('id');

        if (! $customerId) {
            return CustomerLedgerEntry::query()->whereNull('id');
        }

        return CustomerLedgerEntry::query()->where('customer_id', $customerId);
    }
}
