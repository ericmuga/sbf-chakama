<?php

namespace App\Filament\Member\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class MyNotifications extends Page
{
    protected string $view = 'filament.member.pages.my-notifications';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $title = 'Notifications';

    public static function getNavigationBadge(): ?string
    {
        $unread = auth()->user()?->unreadNotifications()?->count();

        return $unread > 0 ? (string) $unread : null;
    }

    public function getNotifications(): Collection
    {
        return auth()->user()?->notifications ?? collect();
    }

    public function markAsRead(string $id): void
    {
        auth()->user()?->notifications()->where('id', $id)->update(['read_at' => now()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mark_all_read')
                ->label('Mark All as Read')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('gray')
                ->action(fn () => auth()->user()?->unreadNotifications?->markAsRead()),
        ];
    }
}
