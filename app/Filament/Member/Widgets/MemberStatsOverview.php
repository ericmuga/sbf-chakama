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

        return [
            Stat::make('Account Balance', 'KES '.number_format((float) $balance, 2)),
            Stat::make('Active Claims', $activeClaims),
            Stat::make('Total Claimed This Year', 'KES '.number_format((float) $totalClaimedThisYear, 2)),
            Stat::make(
                'Last Payment',
                $lastReceipt
                    ? 'KES '.number_format((float) $lastReceipt->amount, 2).' on '.$lastReceipt->posting_date?->format('d M Y')
                    : 'No payments recorded'
            ),
        ];
    }
}
