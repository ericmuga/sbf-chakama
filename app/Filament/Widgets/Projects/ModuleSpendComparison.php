<?php

namespace App\Filament\Widgets\Projects;

use App\Enums\ProjectModule;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ModuleSpendComparison extends ChartWidget
{
    protected static ?int $sort = 12;

    protected ?string $heading = 'Budget & Spend by Module';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $modules = ProjectModule::cases();

        $labels = array_map(fn (ProjectModule $m) => $m->label(), $modules);

        $budgets = [];
        $spends = [];

        foreach ($modules as $module) {
            $row = DB::table('projects')
                ->whereNull('deleted_at')
                ->where('module', $module->value)
                ->selectRaw('COALESCE(SUM(budget), 0) as total_budget, COALESCE(SUM(spent), 0) as total_spent')
                ->first();

            $budgets[] = (float) ($row?->total_budget ?? 0);
            $spends[] = (float) ($row?->total_spent ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Budget',
                    'data' => $budgets,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                ],
                [
                    'label' => 'Spent',
                    'data' => $spends,
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
