<?php

namespace App\Filament\Member\Resources\MyProjects;

use App\Filament\Member\Resources\MyProjects\Pages\ListMyProjects;
use App\Filament\Member\Resources\MyProjects\Pages\ViewMyProject;
use App\Models\Project;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MyProjectsResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $navigationLabel = 'My Projects';

    protected static \UnitEnum|string|null $navigationGroup = 'My Work';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'my-projects';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('members', fn (Builder $q) => $q->where('users.id', auth()->id()));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Project No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('priority')
                    ->badge()
                    ->sortable(),
                TextColumn::make('my_role')
                    ->label('My Role')
                    ->state(function (Project $record): string {
                        $role = auth()->user()
                            ->projects()
                            ->where('projects.id', $record->id)
                            ->first()?->pivot?->role;

                        return $role?->label() ?? '—';
                    })
                    ->badge(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('due_date')
            ->recordUrl(fn (Project $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMyProjects::route('/'),
            'view' => ViewMyProject::route('/{record}'),
        ];
    }
}
