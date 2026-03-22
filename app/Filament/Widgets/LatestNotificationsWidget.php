<?php

namespace App\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

class LatestNotificationsWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Notifications';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 10;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => DatabaseNotification::query()
                    ->where('notifiable_type', auth()->user()::class)
                    ->where('notifiable_id', auth()->id())
                    ->latest()
            )
            ->columns([
                IconColumn::make('read_at')
                    ->label('')
                    ->icon(fn ($state): string => $state ? 'heroicon-o-envelope-open' : 'heroicon-o-envelope')
                    ->color(fn ($state): string => $state ? 'gray' : 'primary')
                    ->width('40px'),
                TextColumn::make('data.message')
                    ->label('Message')
                    ->wrap()
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('data->message', 'like', "%{$search}%")),
                TextColumn::make('data.claim_no')
                    ->label('Claim')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('mark_read')
                    ->label('Mark read')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('gray')
                    ->hidden(fn (DatabaseNotification $record): bool => $record->read_at !== null)
                    ->action(fn (DatabaseNotification $record) => $record->markAsRead()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_all_read')
                        ->label('Mark selected as read')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->action(fn ($records) => $records->each->markAsRead())
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->headerActions([
                Action::make('mark_all_read')
                    ->label('Mark all as read')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('gray')
                    ->action(fn () => auth()->user()->unreadNotifications->markAsRead()),
            ])
            ->paginated([10, 25])
            ->striped();
    }
}
