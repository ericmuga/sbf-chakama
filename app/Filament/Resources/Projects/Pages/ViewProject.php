<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('change_status')
                ->label('Change Status')
                ->icon(Heroicon::OutlinedArrowPath)
                ->visible(function (Project $record): bool {
                    return count(ProjectStatus::allowedTransitions($record->status)) > 0;
                })
                ->schema(function (Project $record): array {
                    $transitions = ProjectStatus::allowedTransitions($record->status);
                    $options = collect($transitions)
                        ->mapWithKeys(fn (ProjectStatus $s) => [$s->value => $s->label()])
                        ->all();

                    return [
                        Select::make('new_status')
                            ->options($options)
                            ->required(),
                        Textarea::make('reason')
                            ->label('Reason (optional)'),
                    ];
                })
                ->action(function (Project $record, array $data): void {
                    app(ProjectService::class)->changeStatus(
                        $record,
                        ProjectStatus::from($data['new_status']),
                        auth()->user(),
                        $data['reason'] ?? null,
                    );

                    Notification::make()->success()->title('Status updated.')->send();

                    $this->refreshFormData(['status']);
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Overview')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('no'),
                        TextEntry::make('module')
                            ->badge(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('priority')
                            ->badge(),
                        TextEntry::make('budget')
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('spent')
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('utilisation_percent')
                            ->label('Utilisation %')
                            ->state(fn (Project $record): float => $record->utilisationPercent()),
                        TextEntry::make('start_date')
                            ->date(),
                        TextEntry::make('due_date')
                            ->date(),
                        TextEntry::make('completed_at')
                            ->dateTime(),
                        TextEntry::make('creator.name')
                            ->label('Created By'),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                    ]),
                Section::make('Progress')
                    ->schema([
                        TextEntry::make('utilisation_percent')
                            ->label('Utilisation %')
                            ->state(fn (Project $record): float => $record->utilisationPercent())
                            ->suffix('%'),
                    ]),
            ]);
    }
}
