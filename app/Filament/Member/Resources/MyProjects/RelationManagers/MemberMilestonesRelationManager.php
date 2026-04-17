<?php

namespace App\Filament\Member\Resources\MyProjects\RelationManagers;

use App\Enums\ProjectMemberRole;
use App\Models\ProjectMilestone;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MemberMilestonesRelationManager extends RelationManager
{
    protected static string $relationship = 'milestones';

    protected static ?string $title = 'Milestones';

    public function isReadOnly(): bool
    {
        return false;
    }

    private function userCanEditMilestones(): bool
    {
        $user = auth()->user();
        $project = $this->getOwnerRecord();

        $role = $user->projects()
            ->where('projects.id', $project->id)
            ->first()?->pivot?->role;

        return $role instanceof ProjectMemberRole && $role !== ProjectMemberRole::Viewer;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('description')
                    ->wrap()
                    ->limit(80),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'completed' ? 'success' : 'warning'),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                Action::make('mark_complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ProjectMilestone $record): bool => $record->status !== 'completed' && $this->userCanEditMilestones())
                    ->action(function (ProjectMilestone $record): void {
                        $record->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ]);

                        Notification::make()->success()->title('Milestone marked complete.')->send();
                    }),
                Action::make('reopen')
                    ->label('Reopen')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->visible(fn (ProjectMilestone $record): bool => $record->status === 'completed' && $this->userCanEditMilestones())
                    ->action(function (ProjectMilestone $record): void {
                        $record->update([
                            'status' => 'pending',
                            'completed_at' => null,
                        ]);

                        Notification::make()->success()->title('Milestone reopened.')->send();
                    }),
            ]);
    }
}
