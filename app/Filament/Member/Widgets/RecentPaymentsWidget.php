<?php

namespace App\Filament\Member\Widgets;

use App\Models\Finance\CashReceipt;
use App\Models\Finance\Customer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentPaymentsWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Payments';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $member = auth()->user()->member;
        $customer = $member?->customer_no
            ? Customer::where('no', $member->customer_no)->first()
            : null;

        return $table
            ->query(fn (): Builder => CashReceipt::query()
                ->when($customer, fn ($q) => $q->where('customer_id', $customer->id))
                ->unless($customer, fn ($q) => $q->whereRaw('1 = 0'))
                ->latest('posting_date')
                ->limit(5)
            )
            ->columns([
                TextColumn::make('no')
                    ->label('Receipt No'),
                TextColumn::make('posting_date')
                    ->label('Date')
                    ->date('d M Y'),
                TextColumn::make('amount')
                    ->label('Amount (KES)')
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
