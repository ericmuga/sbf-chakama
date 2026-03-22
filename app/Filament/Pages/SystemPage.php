<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SystemPage extends Page
{
    protected string $view = 'filament.pages.system-page';

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static \UnitEnum|string|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'System';

    protected static ?string $title = 'System';

    protected static ?int $navigationSort = 100;

    public string $activeTab = 'logs';

    /** @var array<int, array<string, mixed>> */
    public array $logLines = [];

    /** @var array<int, array<string, mixed>> */
    public array $schedules = [];

    /** @var array<int, array<string, mixed>> */
    public array $notifications = [];

    public function mount(): void
    {
        $this->loadLogs();
        $this->loadSchedules();
        $this->loadNotifications();
    }

    public function loadLogs(): void
    {
        $logPath = storage_path('logs/laravel.log');

        if (! File::exists($logPath)) {
            $this->logLines = [];

            return;
        }

        $content = File::get($logPath);
        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*?)(?=\[\d{4}|\z)/s';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $this->logLines = collect(array_reverse($matches))
            ->take(100)
            ->map(fn (array $m) => [
                'datetime' => $m[1],
                'env' => $m[2],
                'level' => strtolower($m[3]),
                'message' => mb_substr(trim($m[4]), 0, 500),
            ])
            ->toArray();
    }

    public function loadSchedules(): void
    {
        Artisan::call('schedule:list', ['--json' => true]);
        $output = Artisan::output();
        $decoded = json_decode(trim($output), true);

        $this->schedules = is_array($decoded) ? $decoded : [];
    }

    public function loadNotifications(): void
    {
        $this->notifications = DatabaseNotification::query()
            ->latest()
            ->take(50)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => class_basename($n->type),
                'data' => json_encode($n->data, JSON_PRETTY_PRINT),
                'read_at' => $n->read_at?->diffForHumans(),
                'created_at' => $n->created_at->format('Y-m-d H:i'),
                'notifiable_id' => $n->notifiable_id,
            ])
            ->toArray();
    }

    public function clearLogs(): void
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::put($logPath, '');
        }
        $this->loadLogs();
        Notification::make()->title('Log file cleared')->success()->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
