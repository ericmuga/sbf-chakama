<?php

namespace App\Filament\Widgets;

use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Member;
use Filament\Widgets\ChartWidget;

class MemberComplianceWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Member Payment Compliance';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $members = Member::query()->members()->where('member_status', 'active')->get();

        $compliant = 0;
        $outstanding = 0;

        foreach ($members as $member) {
            $hasOutstanding = CustomerLedgerEntry::query()
                ->join('customers', 'customer_ledger_entries.customer_id', '=', 'customers.id')
                ->where('customers.no', $member->customer_no)
                ->where('customer_ledger_entries.document_type', 'invoice')
                ->where('customer_ledger_entries.is_open', true)
                ->where('customer_ledger_entries.remaining_amount', '>', 0)
                ->exists();

            $hasOutstanding ? $outstanding++ : $compliant++;
        }

        return [
            'datasets' => [
                [
                    'data' => [$compliant, $outstanding],
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                    ],
                    'borderColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => [
                "Fully Paid ({$compliant})",
                "Outstanding ({$outstanding})",
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
