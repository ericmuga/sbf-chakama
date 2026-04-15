<?php

namespace App\Filament\Widgets;

use App\Models\Finance\GlAccount;
use App\Models\Finance\GlEntry;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ExpenseBreakdownChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Expense Breakdown (Current Year)';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $yearStart = Carbon::now()->startOfYear();

        $accounts = GlAccount::where('no', 'like', '5%')->get()->keyBy('no');

        $entries = GlEntry::query()
            ->where('account_no', 'like', '5%')
            ->where('debit_amount', '>', 0)
            ->where('posting_date', '>=', $yearStart)
            ->selectRaw('account_no, SUM(debit_amount) as total')
            ->groupBy('account_no')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $labels = $entries->map(fn ($e) => $accounts->get($e->account_no)?->name ?? $e->account_no)->toArray();
        $data = $entries->map(fn ($e) => (float) $e->total)->toArray();

        $palette = [
            'rgba(251, 146, 60, 0.8)',
            'rgba(239, 68, 68, 0.8)',
            'rgba(168, 85, 247, 0.8)',
            'rgba(59, 130, 246, 0.8)',
            'rgba(20, 184, 166, 0.8)',
            'rgba(234, 179, 8, 0.8)',
        ];

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => array_slice($palette, 0, count($data)),
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
