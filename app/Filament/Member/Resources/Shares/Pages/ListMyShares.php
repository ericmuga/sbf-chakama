<?php

namespace App\Filament\Member\Resources\Shares\Pages;

use App\Filament\Member\Resources\Shares\MyShareResource;
use App\Models\ShareBillingSchedule;
use App\Services\ShareService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class ListMyShares extends ListRecords
{
    protected static string $resource = MyShareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('subscribeToShare')
                ->label('Subscribe to Share')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->schema([
                    Section::make('Subscription Details')
                        ->columns(2)
                        ->schema([
                            Select::make('billing_schedule_id')
                                ->label('Billing Schedule')
                                ->options(
                                    ShareBillingSchedule::where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(fn (ShareBillingSchedule $s) => [
                                            $s->id => "{$s->name} — KES ".number_format($s->price_per_share, 0).'/share',
                                        ])
                                )
                                ->required(),
                            TextInput::make('number_of_shares')
                                ->label('Number of Shares')
                                ->integer()
                                ->minValue(1)
                                ->default(1)
                                ->required(),
                            DatePicker::make('subscribed_at')
                                ->label('Date')
                                ->default(today())
                                ->required(),
                            Toggle::make('is_nominee')
                                ->label('Subscribe on behalf of a Nominee (Third Party)')
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
                ])
                ->action(function (array $data, ShareService $service): void {
                    $member = auth()->user()->member;
                    $service->subscribe($member, $data);
                })
                ->successNotificationTitle('Share subscription created successfully.')
                ->modalHeading('Subscribe to Share')
                ->modalSubmitActionLabel('Subscribe'),
        ];
    }
}
