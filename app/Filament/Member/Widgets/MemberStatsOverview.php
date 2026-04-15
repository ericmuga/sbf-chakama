<?php

namespace App\Filament\Member\Widgets;

use App\Enums\ClaimStatus;
use App\Models\Finance\CashReceipt;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MemberStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $member = auth()->user()->member;

        if (! $member) {
            return [];
        }

        $customer = $member->customer_no
            ? Customer::where('no', $member->customer_no)->first()
            : null;

        $balance = $customer
            ? CustomerLedgerEntry::where('customer_id', $customer->id)->sum('remaining_amount')
            : 0;

        $activeClaims = $member->claims()
            ->whereNotIn('status', [
                ClaimStatus::Paid->value,
                ClaimStatus::Rejected->value,
                ClaimStatus::Cancelled->value,
            ])
            ->count();

        $totalClaimedThisYear = $member->claims()
            ->whereYear('created_at', now()->year)
            ->sum('claimed_amount');

        $lastReceipt = $customer
            ? CashReceipt::where('customer_id', $customer->id)
                ->latest('posting_date')
                ->first()
            : null;

        // 6-month payment trend for sparkline
        $paymentTrend = $customer
            ? collect(range(5, 0))->map(fn (int $i) => (float) CashReceipt::where('customer_id', $customer->id)
                ->where('status', 'posted')
                ->whereBetween('posting_date', [
                    now()->subMonths($i)->startOfMonth(),
                    now()->subMonths($i)->endOfMonth(),
                ])
                ->sum('amount')
            )->toArray()
            : [];

        $balanceFloat = (float) $balance;

        return [
            Stat::make('Outstanding Balance', 'KES '.number_format($balanceFloat, 2))
                ->description($balanceFloat > 0 ? 'Amount due — please pay promptly' : 'No outstanding balance')
                ->descriptionIcon($balanceFloat > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle')
                ->color($balanceFloat > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-scale'),

            Stat::make('Active Claims', $activeClaims)
                ->description($activeClaims > 0 ? 'Awaiting review or approval' : 'No pending claims')
                ->descriptionIcon($activeClaims > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($activeClaims > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-document-check'),

            Stat::make('Claimed This Year', 'KES '.number_format((float) $totalClaimedThisYear, 2))
                ->description(now()->format('Y').' total submitted')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->icon('heroicon-o-document-currency-dollar'),

            Stat::make(
                'Last Payment',
                $lastReceipt
                    ? 'KES '.number_format((float) $lastReceipt->amount, 2)
                    : '—'
            )
                ->description($lastReceipt ? 'on '.$lastReceipt->posting_date?->format('d M Y') : 'No payments recorded')
                ->descriptionIcon('heroicon-m-calendar')
                ->chart($paymentTrend)
                ->color('success')
                ->icon('heroicon-o-credit-card'),
        ];
    }
}
