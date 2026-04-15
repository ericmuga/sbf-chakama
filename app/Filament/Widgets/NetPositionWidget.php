<?php

namespace App\Filament\Widgets;

use App\Models\Finance\BankLedgerEntry;
use App\Models\Finance\GlEntry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class NetPositionWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $yearStart = Carbon::now()->startOfYear();
        $now = Carbon::now();

        // Income = credits on 4xxx accounts YTD
        $totalIncome = (float) GlEntry::query()
            ->where('account_no', 'like', '4%')
            ->whereBetween('posting_date', [$yearStart, $now])
            ->sum('credit_amount');

        // Expenses = debits on 5xxx accounts YTD
        $totalExpenses = (float) GlEntry::query()
            ->where('account_no', 'like', '5%')
            ->whereBetween('posting_date', [$yearStart, $now])
            ->sum('debit_amount');

        $surplus = $totalIncome - $totalExpenses;

        // Bank cash = net of all bank ledger entries
        $bankBalance = (float) BankLedgerEntry::sum('amount');

        $surplusColor = $surplus >= 0 ? 'success' : 'danger';
        $surplusDescription = $surplus >= 0
            ? 'Surplus year-to-date'
            : 'Deficit year-to-date';

        // 3-month income trend for sparkline
        $trend = collect(range(2, 0))->map(fn (int $i) => (float) GlEntry::query()
            ->where('account_no', 'like', '4%')
            ->whereBetween('posting_date', [
                Carbon::now()->subMonths($i)->startOfMonth(),
                Carbon::now()->subMonths($i)->endOfMonth(),
            ])
            ->sum('credit_amount')
        )->toArray();

        return [
            Stat::make('Total Income YTD', 'KES '.number_format($totalIncome, 2))
                ->description(Carbon::now()->format('Y').' year-to-date')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($trend)
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Total Expenses YTD', 'KES '.number_format($totalExpenses, 2))
                ->description(Carbon::now()->format('Y').' year-to-date')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning')
                ->icon('heroicon-o-arrow-trending-down'),

            Stat::make('Net Surplus / Deficit', 'KES '.number_format(abs($surplus), 2))
                ->description($surplusDescription)
                ->descriptionIcon($surplus >= 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($surplusColor)
                ->icon('heroicon-o-scale'),

            Stat::make('Total Bank Balance', 'KES '.number_format($bankBalance, 2))
                ->description('All accounts combined')
                ->descriptionIcon('heroicon-m-building-library')
                ->color($bankBalance >= 0 ? 'info' : 'danger')
                ->icon('heroicon-o-building-library'),
        ];
    }
}
