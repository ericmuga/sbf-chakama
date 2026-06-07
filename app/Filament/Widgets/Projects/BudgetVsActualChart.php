<?php

namespace App\Filament\Widgets\Projects;

use App\Models\Project;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BudgetVsActualChart extends ChartWidget
{
    public ?Project $record = null;

    protected ?string $heading = 'Budget vs Actual by Account';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        if (! $this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        $project = $this->record;

        $budgetLines = $project->budgetLines()->with('glAccount')->get();

        if ($budgetLines->isEmpty()) {
            return ['datasets' => [], 'labels' => []];
        }

        // Single query for all account actuals instead of N+1
        $accountNos = $budgetLines->pluck('gl_account_no')->filter()->unique()->values();
        $actuals = DB::table('gl_entries')
            ->where('project_id', $project->id)
            ->whereIn('account_no', $accountNos)
            ->groupBy('account_no')
            ->selectRaw('account_no, COALESCE(SUM(debit_amount) - SUM(credit_amount), 0) as net')
            ->pluck('net', 'account_no');

        $labels = $budgetLines->map(fn ($line) => $line->description ?: $line->gl_account_no)->toArray();
        $budgeted = $budgetLines->map(fn ($line) => (float) $line->budgeted_amount)->toArray();
        $actual = $budgetLines->map(fn ($line) => (float) ($actuals[$line->gl_account_no] ?? 0))->toArray();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Budgeted',
                    'data' => $budgeted,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                ],
                [
                    'label' => 'Actual',
                    'data' => $actual,
                    'backgroundColor' => 'rgba(251, 146, 60, 0.7)',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
