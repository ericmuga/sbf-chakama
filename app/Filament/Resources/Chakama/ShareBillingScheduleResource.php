<?php

namespace App\Filament\Resources\Chakama;

use App\Enums\ShareBillingFrequency;
use App\Filament\Resources\Chakama\Pages\CreateShareBillingSchedule;
use App\Filament\Resources\Chakama\Pages\EditShareBillingSchedule;
use App\Filament\Resources\Chakama\Pages\ListShareBillingSchedules;
use App\Filament\Resources\Chakama\Pages\ViewShareBillingSchedule;
use App\Jobs\ProcessShareBillingRunJob;
use App\Models\Finance\Service;
use App\Models\FundAccount;
use App\Models\MemberGroup;
use App\Models\ShareBillingRun;
use App\Models\ShareBillingSchedule;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShareBillingScheduleResource extends Resource
{
    protected static ?string $model = ShareBillingSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static \UnitEnum|string|null $navigationGroup = 'Chakama — Settings';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Schedule Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('price_per_share')
                            ->label('Price Per Share')
                            ->numeric()
                            ->prefix('KES')
                            ->required()
                            ->minValue(0),
                        TextInput::make('acres_per_share')
                            ->label('Acres Per Share')
                            ->integer()
                            ->required()
                            ->minValue(1),
                        Select::make('billing_frequency')
                            ->options(ShareBillingFrequency::class)
                            ->required(),
                        Select::make('service_id')
                            ->label('Service (for invoicing)')
                            ->options(Service::query()->where('is_sellable', true)->orderBy('description')->pluck('description', 'id'))
                            ->searchable()
                            ->required()
                            ->helperText('Required — drives the revenue G/L account on every invoice generated for this schedule.'),
                        Select::make('fund_account_id')
                            ->label('Fund Account')
                            ->options(FundAccount::pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Toggle::make('is_default')
                            ->label('Default Schedule'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }

    private static function subscriptionsAside(): Action
    {
        return Action::make('viewSubscriptions')
            ->slideOver()
            ->modalHeading(fn (ShareBillingSchedule $record): string => "Subscriptions — {$record->name}")
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->schema(fn (ShareBillingSchedule $record): array => [
                View::make('filament.chakama.subscriptions-table')
                    ->viewData([
                        'subscriptions' => $record->subscriptions()->with('member')->orderByDesc('subscribed_at')->get(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->withCount('subscriptions')
                ->withSum('subscriptions', 'number_of_shares')
                ->withSum('subscriptions', 'total_amount')
                ->withSum('subscriptions', 'amount_paid')
            )
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price_per_share')
                    ->label('Price Per Share')
                    ->money('KES')
                    ->sortable(),
                TextColumn::make('acres_per_share')
                    ->label('Acres')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('billing_frequency')
                    ->badge(),
                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('subscriptions_count')
                    ->label('Subscribers')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->action(static::subscriptionsAside()),
                TextColumn::make('subscriptions_sum_number_of_shares')
                    ->label('Total Shares')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->action(static::subscriptionsAside()),
                TextColumn::make('subscriptions_sum_total_amount')
                    ->label('Total Value')
                    ->money('KES')
                    ->sortable()
                    ->action(static::subscriptionsAside()),
                TextColumn::make('subscriptions_sum_amount_paid')
                    ->label('Total Collected')
                    ->money('KES')
                    ->sortable()
                    ->action(static::subscriptionsAside()),
                TextColumn::make('outstanding')
                    ->label('Outstanding')
                    ->money('KES')
                    ->state(fn (ShareBillingSchedule $record): float => (float) $record->subscriptions_sum_total_amount - (float) $record->subscriptions_sum_amount_paid)
                    ->action(static::subscriptionsAside()),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                static::billMembersAction(),
            ])
            ->defaultSort('id', 'desc');
    }

    private static function billMembersAction(): Action
    {
        return Action::make('billMembers')
            ->label('Bill Members')
            ->icon(Heroicon::OutlinedBolt)
            ->color('success')
            ->visible(fn (ShareBillingSchedule $record): bool => (bool) $record->is_active)
            ->modalHeading(fn (ShareBillingSchedule $record): string => "Bill Members — {$record->name}")
            ->modalDescription('Schedule (or immediately run) a billing run for all members allocated to this schedule.')
            ->modalSubmitActionLabel('Save Billing Run')
            ->schema([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->default(fn (ShareBillingSchedule $record): string => "{$record->name} — ".now()->format('M Y')),
                        Select::make('member_group_id')
                            ->label('Member List (optional)')
                            ->options(MemberGroup::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->helperText('Pick a list to bill those members directly — anyone in the list without an existing allocation will be auto-given a 1-share allocation on this schedule. Leave blank to bill only members already allocated.')
                            ->columnSpanFull(),
                        DatePicker::make('billing_date')
                            ->label('Billing Date')
                            ->required()
                            ->default(today())
                            ->live()
                            ->helperText('Future-dated runs are picked up by the daily cron at 06:30.'),
                        DatePicker::make('due_date')
                            ->label('Due Date')
                            ->nullable()
                            ->helperText('Leave blank for 30 days after billing date.'),
                        Toggle::make('notify_members')
                            ->label('In-app notification to members')
                            ->default(true),
                        Toggle::make('send_email')
                            ->label('Email notification to members')
                            ->default(true),
                        Toggle::make('run_now')
                            ->label('Run immediately after saving')
                            ->helperText('Only effective when billing date is today or earlier.')
                            ->default(fn (Get $get): bool => $get('billing_date')
                                ? (string) $get('billing_date') <= today()->toDateString()
                                : true)
                            ->columnSpanFull(),
                    ]),
            ])
            ->action(function (ShareBillingSchedule $record, array $data): void {
                $run = ShareBillingRun::create([
                    'title' => $data['title'],
                    'billing_schedule_id' => $record->id,
                    'member_group_id' => $data['member_group_id'] ?? null,
                    'billing_date' => $data['billing_date'],
                    'due_date' => $data['due_date'] ?? null,
                    'status' => 'draft',
                    'notify_members' => (bool) ($data['notify_members'] ?? true),
                    'send_email' => (bool) ($data['send_email'] ?? true),
                    'created_by' => auth()->id(),
                ]);

                $shouldRunNow = (bool) ($data['run_now'] ?? false)
                    && $run->billing_date->lessThanOrEqualTo(today());

                if ($shouldRunNow) {
                    ProcessShareBillingRunJob::dispatch($run->id);

                    Notification::make()
                        ->title('Billing run queued')
                        ->body("Invoices for '{$record->name}' will be generated shortly.")
                        ->success()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Billing run scheduled')
                    ->body("Will run automatically on {$run->billing_date->format('d M Y')}.")
                    ->success()
                    ->send();
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShareBillingSchedules::route('/'),
            'create' => CreateShareBillingSchedule::route('/create'),
            'view' => ViewShareBillingSchedule::route('/{record}'),
            'edit' => EditShareBillingSchedule::route('/{record}/edit'),
        ];
    }
}
