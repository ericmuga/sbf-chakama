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
                        'rgba(94, 114, 160, 0.75)',   // muted blue for paid
                        'rgba(139, 111, 168, 0.75)',  // muted purple for outstanding
                    ],
                    'borderColor' => [
                        'rgb(71, 89, 133)',
                        'rgb(109, 82, 136)',
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
