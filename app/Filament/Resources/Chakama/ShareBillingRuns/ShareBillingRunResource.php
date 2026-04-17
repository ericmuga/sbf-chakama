<?php

namespace App\Filament\Resources\Chakama\ShareBillingRuns;

use App\Filament\Resources\Chakama\ShareBillingRuns\Pages\CreateShareBillingRun;
use App\Filament\Resources\Chakama\ShareBillingRuns\Pages\ListShareBillingRuns;
use App\Filament\Resources\Chakama\ShareBillingRuns\Pages\ViewShareBillingRun;
use App\Jobs\ProcessShareBillingRunJob;
use App\Models\ShareBillingRun;
use App\Models\ShareBillingSchedule;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ShareBillingRunResource extends Resource
{
    protected static ?string $model = ShareBillingRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static \UnitEnum|string|null $navigationGroup = 'Chakama — Shares';

    protected static ?string $navigationLabel = 'Billing Runs';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return (auth()->user()?->isAdmin() ?? false) && $record->status === 'draft';
    }

    public static function canDelete(Model $record): bool
    {
        return (auth()->user()?->isAdmin() ?? false) && $record->status === 'draft';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Billing Run Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->placeholder('e.g. January 2026 Share Billing'),
                        Select::make('billing_schedule_id')
                            ->label('Billing Schedule')
                            ->options(ShareBillingSchedule::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        DatePicker::make('billing_date')
                            ->label('Billing Date')
                            ->required()
                            ->default(today()),
                        DatePicker::make('due_date')
                            ->label('Due Date')
                            ->helperText('Leave blank to default to 30 days from billing date')
                            ->nullable(),
                        Toggle::make('notify_members')
                            ->label('Send in-app notification to members')
                            ->default(true),
                        Toggle::make('send_email')
                            ->label('Send email notification to members')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('billingSchedule.name')
                    ->label('Schedule')
                    ->sortable(),
                TextColumn::make('billing_date')
                    ->label('Billing Date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('member_count')
                    ->label('Members Billed')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('total_invoiced')
                    ->label('Total Invoiced')
                    ->money('KES')
                    ->sortable(),
                TextColumn::make('processed_at')
                    ->label('Processed At')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                IconColumn::make('notify_members')
                    ->label('In-app')
                    ->boolean(),
                IconColumn::make('send_email')
                    ->label('Email')
                    ->boolean(),
            ])
            ->recordActions([
                Action::make('view_run')
                    ->label('View')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (ShareBillingRun $record): string => static::getUrl('view', ['record' => $record])),
                Action::make('run_billing')
                    ->label('Run Billing')
                    ->icon(Heroicon::OutlinedBolt)
                    ->color('success')
                    ->visible(fn (ShareBillingRun $record): bool => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading('Run Share Billing')
                    ->modalDescription(fn (ShareBillingRun $record): string => "This will generate invoices for all active Chakama members with allocations for '{$record->billingSchedule?->name}'. This cannot be undone.")
                    ->action(function (ShareBillingRun $record): void {
                        ProcessShareBillingRunJob::dispatch($record->id);

                        Notification::make()
                            ->title('Billing run queued')
                            ->body('Invoices will be generated shortly. You will be notified when complete.')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShareBillingRuns::route('/'),
            'create' => CreateShareBillingRun::route('/create'),
            'view' => ViewShareBillingRun::route('/{record}'),
        ];
    }
}
