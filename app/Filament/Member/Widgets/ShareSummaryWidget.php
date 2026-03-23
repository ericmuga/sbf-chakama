<?php

namespace App\Filament\Member\Widgets;

use App\Enums\ShareStatus;
use App\Models\ShareSubscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShareSummaryWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $member = auth()->user()?->member;

        if (! $member || ! $member->is_chakama) {
            return [];
        }

        $subscriptions = ShareSubscription::where('member_id', $member->id)
            ->whereNotIn('status', [ShareStatus::Cancelled->value, ShareStatus::Transferred->value])
            ->get();

        $totalShares = (int) $subscriptions
            ->where('status', ShareStatus::Active->value)
            ->sum('number_of_shares');

        $totalPaid = (float) $subscriptions->sum('amount_paid');

        $totalOutstanding = $subscriptions->sum(
            fn (ShareSubscription $sub) => max(0, (float) $sub->total_amount - (float) $sub->amount_paid)
        );

        return [
            Stat::make('My Shares', $totalShares)
                ->description('Active share units'),

            Stat::make('My Acres', $totalShares * 10)
                ->description('Total acres allocated'),

            Stat::make('Amount Paid', 'KES '.number_format($totalPaid, 2))
                ->color('success'),

            Stat::make('Outstanding', 'KES '.number_format((float) $totalOutstanding, 2))
                ->color($totalOutstanding > 0 ? 'danger' : 'success')
                ->description($totalOutstanding > 0 ? 'Balance due' : 'Fully paid'),
        ];
    }
}
