<?php

namespace App\Filament\Widgets\Projects;

use App\Models\Project;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlySpendTrend extends ChartWidget
{
    public ?Project $record = null;

    protected ?string $heading = 'Monthly Spend Trend';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        if (! $this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        $project = $this->record;

        $months = collect(range(11, 0))->map(fn (int $i) => Carbon::now()->subMonths($i)->startOfMonth());

        $labels = $months->map(fn (Carbon $m) => $m->format('M Y'))->toArray();

        $monthlySpend = $months->map(fn (Carbon $m) => (float) DB::table('gl_entries')
            ->where('project_id', $project->id)
            ->whereBetween('posting_date', [$m->copy()->startOfMonth()->toDateString(), $m->copy()->endOfMonth()->toDateString()])
            ->selectRaw('COALESCE(SUM(debit_amount) - SUM(credit_amount), 0) as net')
            ->value('net')
        );

        $budgetCeiling = (float) $project->budget;

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Monthly Spend',
                    'data' => $monthlySpend->toArray(),
                    'borderColor' => 'rgb(251, 146, 60)',
                    'backgroundColor' => 'rgba(251, 146, 60, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Budget',
                    'data' => array_fill(0, count($labels), $budgetCeiling),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderDash' => [5, 5],
                    'fill' => false,
                    'pointRadius' => 0,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
