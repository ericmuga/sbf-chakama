<?php

namespace App\Filament\Member\Resources\Shares;

use App\Enums\ShareStatus;
use App\Filament\Member\Pages\MakePayment;
use App\Filament\Member\Resources\Shares\Pages\ListMyShares;
use App\Models\ShareSubscription;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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
            ->with(['billingSchedule', 'nominee', 'payments.paymentMethod'])
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
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->slideOver()
                    ->modalHeading(fn (ShareSubscription $record): string => "Share Subscription — {$record->no}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->schema(fn (ShareSubscription $record): array => [
                        Section::make('Subscription Details')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('no')
                                    ->label('Subscription No'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (ShareStatus $state): string => $state->color()),
                                TextEntry::make('billingSchedule.name')
                                    ->label('Billing Schedule'),
                                TextEntry::make('subscribed_at')
                                    ->label('Subscribed On')
                                    ->date(),
                                TextEntry::make('number_of_shares')
                                    ->label('Shares'),
                                TextEntry::make('total_acres')
                                    ->label('Acres'),
                                TextEntry::make('total_amount')
                                    ->label('Total Amount')
                                    ->money('KES'),
                                TextEntry::make('amount_paid')
                                    ->label('Amount Paid')
                                    ->money('KES'),
                                TextEntry::make('amount_outstanding')
                                    ->label('Outstanding')
                                    ->money('KES'),
                                IconEntry::make('is_first_share')
                                    ->label('First Share')
                                    ->boolean(),
                                IconEntry::make('is_nominee')
                                    ->label('Nominee Share')
                                    ->boolean(),
                            ]),

                        Section::make('Nominee / Assignment Details')
                            ->columns(3)
                            ->visible(fn () => (bool) $record->is_nominee && $record->nominee !== null)
                            ->schema([
                                TextEntry::make('nominee.full_name')
                                    ->label('Assigned To'),
                                TextEntry::make('nominee.national_id')
                                    ->label('National ID / Passport'),
                                TextEntry::make('nominee.relationship')
                                    ->label('Relationship'),
                                TextEntry::make('nominee.phone')
                                    ->label('Phone')
                                    ->placeholder('—'),
                                TextEntry::make('nominee.email')
                                    ->label('Email')
                                    ->placeholder('—'),
                            ]),

                        Section::make('Payment History')
                            ->schema([
                                RepeatableEntry::make('payments')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('no')
                                            ->label('Receipt No'),
                                        TextEntry::make('posting_date')
                                            ->label('Date')
                                            ->date(),
                                        TextEntry::make('amount')
                                            ->label('Amount')
                                            ->money('KES'),
                                        TextEntry::make('paymentMethod.description')
                                            ->label('Method')
                                            ->placeholder('—'),
                                        TextEntry::make('mpesa_receipt_no')
                                            ->label('M-Pesa Ref')
                                            ->placeholder('—'),
                                        TextEntry::make('status')
                                            ->badge()
                                            ->color(fn (string $state): string => match (strtolower($state)) {
                                                'posted' => 'success',
                                                default => 'warning',
                                            }),
                                    ])
                                    ->columns(6),
                            ]),
                    ]),
                Action::make('pay_now')
                    ->label('Pay Now')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('success')
                    ->visible(fn (ShareSubscription $record): bool => (float) $record->total_amount > (float) $record->amount_paid)
                    ->url(fn (ShareSubscription $record): string => MakePayment::getUrl([
                        'amount' => number_format((float) $record->total_amount - (float) $record->amount_paid, 2, '.', ''),
                    ])),
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
