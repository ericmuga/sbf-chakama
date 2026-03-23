<?php

namespace App\Filament\Widgets\Projects;

use App\Enums\ProjectStatus;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ProjectStatusDistribution extends ChartWidget
{
    protected static ?int $sort = 11;

    protected ?string $heading = 'Projects by Status';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $counts = DB::table('projects')
            ->whereNull('deleted_at')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $statuses = ProjectStatus::cases();

        $labels = array_map(fn (ProjectStatus $s) => $s->label(), $statuses);

        $data = array_map(fn (ProjectStatus $s) => (int) ($counts[$s->value] ?? 0), $statuses);

        $colors = array_map(fn (ProjectStatus $s) => match ($s->color()) {
            'gray' => 'rgba(156, 163, 175, 0.7)',
            'info' => 'rgba(6, 182, 212, 0.7)',
            'primary' => 'rgba(59, 130, 246, 0.7)',
            'warning' => 'rgba(251, 191, 36, 0.7)',
            'success' => 'rgba(34, 197, 94, 0.7)',
            'danger' => 'rgba(239, 68, 68, 0.7)',
            default => 'rgba(156, 163, 175, 0.7)',
        }, $statuses);

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Projects',
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
