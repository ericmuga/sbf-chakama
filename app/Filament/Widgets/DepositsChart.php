<?php

namespace App\Filament\Widgets;

use App\Models\Finance\CashReceipt;
use App\Models\Finance\VendorPayment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class DepositsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Receipts vs Disbursements (Last 6 Months)';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn (int $i) => Carbon::now()->subMonths($i)->startOfMonth());

        $labels = $months->map(fn (Carbon $m) => $m->format('M Y'))->toArray();

        $receipts = $months->map(fn (Carbon $m) => (float) CashReceipt::query()
            ->where('status', 'posted')
            ->whereBetween('posting_date', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])
            ->sum('amount')
        )->toArray();

        $disbursements = $months->map(fn (Carbon $m) => (float) VendorPayment::query()
            ->where('status', 'posted')
            ->whereBetween('posting_date', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])
            ->sum('amount')
        )->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Receipts',
                    'data' => $receipts,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Disbursements',
                    'data' => $disbursements,
                    'borderColor' => 'rgb(251, 146, 60)',
                    'backgroundColor' => 'rgba(251, 146, 60, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
