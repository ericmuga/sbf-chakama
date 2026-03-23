<?php

namespace App\Filament\Widgets\Projects;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AllProjectsStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $total = Project::query()->count();
        $active = Project::query()->whereIn('status', [
            ProjectStatus::Planning->value,
            ProjectStatus::InProgress->value,
        ])->count();

        $totalBudget = (float) Project::query()->sum('budget');
        $totalSpent = (float) Project::query()->sum('spent');

        $overdue = Project::query()
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereIn('status', [
                ProjectStatus::Planning->value,
                ProjectStatus::InProgress->value,
                ProjectStatus::OnHold->value,
            ])
            ->count();

        return [
            Stat::make('Total Projects', number_format($total))
                ->icon('heroicon-o-briefcase')
                ->color('primary'),

            Stat::make('Active Projects', number_format($active))
                ->icon('heroicon-o-play')
                ->color('success'),

            Stat::make('Total Budget', 'KES '.number_format($totalBudget, 2))
                ->icon('heroicon-o-banknotes')
                ->color('info'),

            Stat::make('Total Spent', 'KES '.number_format($totalSpent, 2))
                ->icon('heroicon-o-arrow-trending-up')
                ->color('warning'),

            Stat::make('Overdue Projects', number_format($overdue))
                ->icon('heroicon-o-exclamation-triangle')
                ->color($overdue > 0 ? 'danger' : 'gray'),
        ];
    }
}
