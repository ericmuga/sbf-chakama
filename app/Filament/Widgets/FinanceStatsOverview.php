<?php

namespace App\Filament\Widgets;

use App\Models\Claim;
use App\Models\Finance\CashReceipt;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\VendorPayment;
use App\Models\Member;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class FinanceStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();

        $memberCount = Member::query()->members()->count();

        $monthlyReceipts = CashReceipt::query()
            ->where('status', 'posted')
            ->whereBetween('posting_date', [$monthStart, $now])
            ->sum('amount');

        $monthlyDisbursements = VendorPayment::query()
            ->where('status', 'posted')
            ->whereBetween('posting_date', [$monthStart, $now])
            ->sum('amount');

        $paidClaimsCount = Claim::query()
            ->whereNotNull('approved_at')
            ->whereBetween('approved_at', [$monthStart, $now])
            ->count();

        $pendingApprovals = Claim::query()
            ->where('status', 'pending')
            ->count();

        $outstandingInvoices = CustomerLedgerEntry::query()
            ->where('is_open', true)
            ->where('document_type', 'invoice')
            ->where('amount', '>', 0)
            ->sum('remaining_amount');

        return [
            Stat::make('Total Members', number_format($memberCount))
                ->icon('heroicon-o-user-group')
                ->color('primary'),

            Stat::make('Receipts This Month', 'KES '.number_format((float) $monthlyReceipts, 2))
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success'),

            Stat::make('Disbursements This Month', 'KES '.number_format((float) $monthlyDisbursements, 2))
                ->icon('heroicon-o-arrow-trending-down')
                ->color('warning'),

            Stat::make('Claims Paid This Month', number_format($paidClaimsCount))
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('Pending Approvals', number_format($pendingApprovals))
                ->icon('heroicon-o-clock')
                ->color($pendingApprovals > 0 ? 'danger' : 'gray'),

            Stat::make('Outstanding Invoices', 'KES '.number_format((float) $outstandingInvoices, 2))
                ->description('Unpaid member deposits')
                ->icon('heroicon-o-document-currency-dollar')
                ->color('info'),
        ];
    }
}
