<?php

namespace App\Filament\Member\Widgets\Member;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use Filament\Widgets\ChartWidget;

class ShareComparisonChart extends ChartWidget
{
    protected ?string $heading = 'My Share Billing: Paid vs Outstanding';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->member?->is_chakama;
    }

    protected function getData(): array
    {
        $member = auth()->user()?->member;

        if (! $member || ! $member->customer_no) {
            return ['datasets' => [], 'labels' => []];
        }

        $customer = Customer::where('no', $member->customer_no)->first();

        if (! $customer) {
            return ['datasets' => [], 'labels' => []];
        }

        $entries = CustomerLedgerEntry::where('customer_id', $customer->id)
            ->where('document_type', 'invoice')
            ->get();

        $totalBilled = $entries->sum(fn ($e) => (float) $e->amount);
        $totalPaid = $entries->sum(fn ($e) => (float) $e->amount - (float) $e->remaining_amount);
        $outstanding = max(0, $totalBilled - $totalPaid);

        return [
            'labels' => ['Paid', 'Outstanding'],
            'datasets' => [
                [
                    'data' => [$totalPaid, $outstanding],
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',  // green for paid
                        'rgba(239, 68, 68, 0.8)',   // red for outstanding
                    ],
                    'borderColor' => [
                        'rgb(22, 163, 74)',
                        'rgb(220, 38, 38)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
