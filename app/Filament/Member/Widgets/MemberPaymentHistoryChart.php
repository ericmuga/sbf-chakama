<?php

namespace App\Filament\Member\Widgets;

use App\Models\Finance\CashReceipt;
use App\Models\Finance\Customer;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class MemberPaymentHistoryChart extends ChartWidget
{
    protected ?string $heading = 'My Payment History';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $member = auth()->user()?->member;
        $customer = $member?->customer_no
            ? Customer::where('no', $member->customer_no)->first()
            : null;

        $months = collect(range(5, 0))->map(fn (int $i) => Carbon::now()->subMonths($i)->startOfMonth());

        $labels = $months->map(fn (Carbon $m) => $m->format('M Y'))->toArray();

        $amounts = $months->map(function (Carbon $m) use ($customer): float {
            if (! $customer) {
                return 0.0;
            }

            return (float) CashReceipt::where('customer_id', $customer->id)
                ->where('status', 'posted')
                ->whereBetween('posting_date', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])
                ->sum('amount');
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Payments (KES)',
                    'data' => $amounts,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.7)',
                    'borderColor' => 'rgb(99, 102, 241)',
                    'borderWidth' => 2,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
