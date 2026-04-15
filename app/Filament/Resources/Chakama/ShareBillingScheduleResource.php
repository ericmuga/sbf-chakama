<?php

namespace App\Filament\Resources\Chakama;

use App\Enums\ShareBillingFrequency;
use App\Filament\Resources\Chakama\Pages\CreateShareBillingSchedule;
use App\Filament\Resources\Chakama\Pages\EditShareBillingSchedule;
use App\Filament\Resources\Chakama\Pages\ListShareBillingSchedules;
use App\Filament\Resources\Chakama\Pages\ViewShareBillingSchedule;
use App\Models\FundAccount;
use App\Models\ShareBillingSchedule;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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
            'index' => ListShareBillingSchedules::route('/'),
            'create' => CreateShareBillingSchedule::route('/create'),
            'view' => ViewShareBillingSchedule::route('/{record}'),
            'edit' => EditShareBillingSchedule::route('/{record}/edit'),
        ];
    }
}
