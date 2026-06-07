<?php

namespace App\Filament\Member\Resources\MyInvoices\Tables;

use App\Filament\Member\Pages\MakePayment;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MyInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_no')
                    ->label('Invoice No')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(40)
                    ->placeholder('—'),

                TextColumn::make('posting_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn ($record) => $record->due_date?->isPast() ? 'danger' : null),

                TextColumn::make('amount')
                    ->label('Invoice Amount (KES)')
                    ->numeric(decimalPlaces: 2)
                    ->alignRight(),

                TextColumn::make('remaining_amount')
                    ->label('Outstanding (KES)')
                    ->numeric(decimalPlaces: 2)
                    ->alignRight()
                    ->weight('bold')
                    ->color('warning'),
            ])
            ->defaultSort('due_date', 'asc')
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateHeading('No pending bills')
            ->emptyStateDescription('You have no outstanding invoices at this time.')
            ->headerActions([
                Action::make('pay_all')
                    ->label(function (): string {
                        $member = auth()->user()?->member;
                        $customer = $member?->customer_no
                            ? Customer::where('no', $member->customer_no)->first()
                            : null;

                        if (! $customer) {
                            return 'Pay All Outstanding';
                        }

                        $total = CustomerLedgerEntry::where('customer_id', $customer->id)
                            ->where('document_type', 'invoice')
                            ->where('is_open', true)
                            ->sum('remaining_amount');

                        return 'Pay All — KES '.number_format((float) $total, 2);
                    })
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->visible(function (): bool {
                        $member = auth()->user()?->member;
                        $customer = $member?->customer_no
                            ? Customer::where('no', $member->customer_no)->first()
                            : null;

                        if (! $customer) {
                            return false;
                        }

                        return CustomerLedgerEntry::where('customer_id', $customer->id)
                            ->where('document_type', 'invoice')
                            ->where('is_open', true)
                            ->where('remaining_amount', '>', 0)
                            ->exists();
                    })
                    ->url(function (): string {
                        $member = auth()->user()?->member;
                        $customer = $member?->customer_no
                            ? Customer::where('no', $member->customer_no)->first()
                            : null;

                        $total = $customer
                            ? CustomerLedgerEntry::where('customer_id', $customer->id)
                                ->where('document_type', 'invoice')
                                ->where('is_open', true)
                                ->sum('remaining_amount')
                            : 0;

                        return MakePayment::getUrl(['amount' => number_format((float) $total, 2, '.', '')]);
                    }),
            ])
            ->recordActions([
                Action::make('pay_invoice')
                    ->label('Pay Now')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('success')
                    ->visible(fn (CustomerLedgerEntry $record): bool => (float) $record->remaining_amount > 0)
                    ->url(fn (CustomerLedgerEntry $record): string => MakePayment::getUrl([
                        'amount' => number_format((float) $record->remaining_amount, 2, '.', ''),
                    ])),
            ])
            ->toolbarActions([]);
    }
}
