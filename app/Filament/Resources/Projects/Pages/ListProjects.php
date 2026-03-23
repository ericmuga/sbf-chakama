<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Widgets\Projects\AllProjectsStatsOverview;
use App\Filament\Widgets\Projects\ModuleSpendComparison;
use App\Filament\Widgets\Projects\ProjectStatusDistribution;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            AllProjectsStatsOverview::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            ProjectStatusDistribution::class,
            ModuleSpendComparison::class,
        ];
    }
}
