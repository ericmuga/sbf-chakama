<?php

namespace App\Filament\Widgets\Projects;

use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectStatsWidget extends StatsOverviewWidget
{
    public ?Project $record = null;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $project = $this->record;
        $budget = (float) $project->budget;
        $spent = (float) $project->spent;
        $remaining = $budget - $spent;
        $utilisation = $project->utilisationPercent();

        $utilisationColor = match (true) {
            $utilisation >= 100 => 'danger',
            $utilisation >= 80 => 'warning',
            default => 'success',
        };

        return [
            Stat::make('Budget', 'KES '.number_format($budget, 2))
                ->icon('heroicon-o-banknotes')
                ->color('primary'),

            Stat::make('Spent', 'KES '.number_format($spent, 2))
                ->icon('heroicon-o-arrow-trending-up')
                ->color('warning'),

            Stat::make('Remaining', 'KES '.number_format($remaining, 2))
                ->icon('heroicon-o-arrow-trending-down')
                ->color($remaining >= 0 ? 'success' : 'danger'),

            Stat::make('Utilisation', number_format($utilisation, 1).'%')
                ->icon('heroicon-o-chart-bar')
                ->color($utilisationColor),
        ];
    }
}
