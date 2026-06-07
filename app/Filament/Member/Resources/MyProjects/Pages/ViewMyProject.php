<?php

namespace App\Filament\Member\Resources\MyProjects\Pages;

use App\Filament\Member\Resources\MyProjects\MyProjectsResource;
use App\Filament\Member\Resources\MyProjects\RelationManagers\MemberMilestonesRelationManager;
use App\Models\Project;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewMyProject extends ViewRecord
{
    protected static string $resource = MyProjectsResource::class;

    public function getRelationManagers(): array
    {
        return [
            MemberMilestonesRelationManager::class,
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Project Overview')
                ->columns(3)
                ->schema([
                    TextEntry::make('no')
                        ->label('Project No'),
                    TextEntry::make('name'),
                    TextEntry::make('status')
                        ->badge(),
                    TextEntry::make('priority')
                        ->badge(),
                    TextEntry::make('module')
                        ->badge(),
                    TextEntry::make('my_role')
                        ->label('My Role')
                        ->state(function (Project $record): string {
                            $role = auth()->user()
                                ->projects()
                                ->where('projects.id', $record->id)
                                ->first()?->pivot?->role;

                            return $role?->label() ?? '—';
                        })
                        ->badge(),
                    TextEntry::make('budget')
                        ->numeric(decimalPlaces: 2)
                        ->prefix('KES '),
                    TextEntry::make('start_date')
                        ->date(),
                    TextEntry::make('due_date')
                        ->date(),
                    TextEntry::make('creator.name')
                        ->label('Project Lead'),
                    TextEntry::make('description')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
