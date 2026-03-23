<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Enums\ProjectMemberRole;
use App\Models\User;
use App\Services\ProjectService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (User $record): string => $record->pivot?->role?->color() ?? 'gray')
                    ->state(fn (User $record): ?string => $record->pivot?->role?->label()),
                TextColumn::make('pivot.assigned_at')
                    ->label('Assigned On')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('assigned_by_name')
                    ->label('Assigned By')
                    ->state(fn (User $record): ?string => User::query()->find($record->pivot?->assigned_by)?->name),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('add_member')
                    ->label('Add Member')
                    ->icon('heroicon-o-user-plus')
                    ->schema([
                        Select::make('user_id')
                            ->label('User')
                            ->options(User::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Select::make('role')
                            ->options(ProjectMemberRole::class)
                            ->default(ProjectMemberRole::Contributor->value)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $member = User::query()->findOrFail($data['user_id']);

                        app(ProjectService::class)->addMember(
                            $this->getOwnerRecord(),
                            $member,
                            ProjectMemberRole::from($data['role']),
                            auth()->user(),
                        );

                        Notification::make()
                            ->success()
                            ->title('Project member saved.')
                            ->body('Newly assigned members will receive a notification.')
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('change_role')
                    ->label('Change Role')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Select::make('role')
                            ->options(ProjectMemberRole::class)
                            ->default(fn (User $record): string => $record->pivot->role->value)
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $this->getOwnerRecord()->members()->updateExistingPivot($record->id, [
                            'role' => $data['role'],
                            'assigned_at' => now(),
                            'assigned_by' => auth()->id(),
                        ]);

                        Notification::make()->success()->title('Project member role updated.')->send();
                    }),
                Action::make('remove_member')
                    ->label('Remove')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        app(ProjectService::class)->removeMember($this->getOwnerRecord(), $record);

                        Notification::make()->success()->title('Project member removed.')->send();
                    }),
            ]);
    }
}
