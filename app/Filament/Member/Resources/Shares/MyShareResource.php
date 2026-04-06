<?php

namespace App\Filament\Member\Resources\Shares;

use App\Enums\ShareStatus;
use App\Filament\Member\Resources\Shares\Pages\ListMyShares;
use App\Models\ShareSubscription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MyShareResource extends Resource
{
    protected static ?string $model = ShareSubscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'My Shares';

    protected static \UnitEnum|string|null $navigationGroup = 'Chakama';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return auth()->user()?->member?->is_chakama ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $member = auth()->user()?->member;

        return parent::getEloquentQuery()
            ->when($member, fn ($q) => $q->where('member_id', $member->id))
            ->unless($member, fn ($q) => $q->whereRaw('1 = 0'));
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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('number_of_shares')
                    ->label('Shares')
                    ->numeric(),
                TextColumn::make('total_acres')
                    ->label('Acres')
                    ->state(fn (ShareSubscription $record): int => $record->number_of_shares * 10),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('KES'),
                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('KES'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ShareStatus $state): string => $state->color()),
                IconColumn::make('is_first_share')
                    ->label('First Share')
                    ->boolean(),
                TextColumn::make('subscribed_at')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ShareStatus::class),
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
            'index' => ListMyShares::route('/'),
        ];
    }
}
