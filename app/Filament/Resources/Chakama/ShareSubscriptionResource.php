<?php

namespace App\Filament\Resources\Chakama;

use App\Enums\ShareStatus;
use App\Filament\Resources\Chakama\Pages\CreateShareSubscription;
use App\Filament\Resources\Chakama\Pages\ListShareSubscriptions;
use App\Filament\Resources\Chakama\Pages\ViewShareSubscription;
use App\Models\Member;
use App\Models\ShareBillingSchedule;
use App\Models\ShareSubscription;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ShareSubscriptionResource extends Resource
{
    protected static ?string $model = ShareSubscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static \UnitEnum|string|null $navigationGroup = 'Chakama — Shares';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription Details')
                    ->columns(2)
                    ->schema([
                        Select::make('member_id')
                            ->label('Member')
                            ->options(
                                Member::query()
                                    ->where('is_chakama', true)
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required(),
                        Select::make('billing_schedule_id')
                            ->label('Billing Schedule')
                            ->options(ShareBillingSchedule::where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                        TextInput::make('number_of_shares')
                            ->label('Number of Shares')
                            ->integer()
                            ->minValue(1)
                            ->required()
                            ->default(1),
                        DatePicker::make('subscribed_at')
                            ->required()
                            ->default(today()),
                        Toggle::make('is_nominee')
                            ->label('Share for a Nominee (Third Party)')
                            ->live()
                            ->columnSpanFull(),
                    ]),
                Section::make('Nominee Details')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => (bool) $get('is_nominee'))
                    ->schema([
                        TextInput::make('nominee.full_name')
                            ->label('Full Name')
                            ->required(fn (Get $get): bool => (bool) $get('is_nominee')),
                        TextInput::make('nominee.national_id')
                            ->label('National ID / Passport')
                            ->required(fn (Get $get): bool => (bool) $get('is_nominee')),
                        TextInput::make('nominee.phone')
                            ->label('Phone')
                            ->tel(),
                        TextInput::make('nominee.email')
                            ->label('Email')
                            ->email(),
                        TextInput::make('nominee.relationship')
                            ->label('Relationship to Member'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('member.name')
                    ->label('Member')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('number_of_shares')
                    ->label('Shares')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_acres')
                    ->label('Acres')
                    ->state(fn (ShareSubscription $record): int => $record->number_of_shares * 10),
                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('KES')
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('KES')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ShareStatus $state): string => $state->color()),
                IconColumn::make('is_first_share')
                    ->label('First Share')
                    ->boolean(),
                TextColumn::make('subscribed_at')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ShareStatus::class),
                TernaryFilter::make('is_nominee'),
                TernaryFilter::make('is_first_share'),
            ])
            ->recordActions([
                ViewAction::make(),
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
            'index' => ListShareSubscriptions::route('/'),
            'create' => CreateShareSubscription::route('/create'),
            'view' => ViewShareSubscription::route('/{record}'),
        ];
    }
}
