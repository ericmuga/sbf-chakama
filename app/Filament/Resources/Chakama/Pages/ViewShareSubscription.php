<?php

namespace App\Filament\Resources\Chakama\Pages;

use App\Enums\ShareStatus;
use App\Filament\Resources\Chakama\ShareSubscriptionResource;
use App\Models\Member;
use App\Models\ShareSubscription;
use App\Services\ShareService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewShareSubscription extends ViewRecord
{
    protected static string $resource = ShareSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        /** @var ShareSubscription $record */
        $record = $this->getRecord();
        $service = app(ShareService::class);

        return [
            Action::make('activate')
                ->label('Activate')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn () => $record->status === ShareStatus::PendingPayment)
                ->requiresConfirmation()
                ->action(fn () => $service->activateSubscription($record)),

            Action::make('suspend')
                ->label('Suspend')
                ->color('warning')
                ->icon('heroicon-o-pause-circle')
                ->visible(fn () => $record->status === ShareStatus::Active)
                ->schema([
                    Textarea::make('reason')
                        ->label('Reason for Suspension')
                        ->required()
                        ->rows(3),
                ])
                ->action(fn (array $data) => $service->suspendSubscription($record, $data['reason'])),

            Action::make('transfer')
                ->label('Transfer to Member')
                ->color('info')
                ->icon('heroicon-o-arrow-right-circle')
                ->visible(fn () => in_array($record->status, [ShareStatus::Active, ShareStatus::PendingPayment]))
                ->schema([
                    Select::make('new_member_id')
                        ->label('New Member')
                        ->options(
                            Member::query()
                                ->where('is_chakama', true)
                                ->where('id', '!=', $record->member_id)
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->required(),
                ])
                ->action(fn (array $data) => $service->transferSubscription(
                    $record,
                    Member::findOrFail($data['new_member_id'])
                )),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('no')
                            ->label('Subscription No'),
                        TextEntry::make('member.name')
                            ->label('Member'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (ShareStatus $state): string => $state->color()),
                        TextEntry::make('billingSchedule.name')
                            ->label('Billing Schedule'),
                        TextEntry::make('subscribed_at')
                            ->label('Subscribed On')
                            ->date(),
                        TextEntry::make('next_billing_date')
                            ->label('Next Billing Date')
                            ->date()
                            ->placeholder('—'),
                        TextEntry::make('number_of_shares')
                            ->label('Shares'),
                        TextEntry::make('total_acres')
                            ->label('Acres'),
                        TextEntry::make('price_per_share')
                            ->label('Price per Share')
                            ->money('KES'),
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

                Section::make('Nominee Details')
                    ->columns(3)
                    ->visible(fn (ShareSubscription $record): bool => (bool) $record->is_nominee && $record->nominee !== null)
                    ->schema([
                        TextEntry::make('nominee.full_name')
                            ->label('Full Name'),
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
                            ->label('')
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
                                    ->label('Payment Method')
                                    ->placeholder('—'),
                                TextEntry::make('mpesa_receipt_no')
                                    ->label('M-Pesa Receipt')
                                    ->placeholder('—'),
                                TextEntry::make('mpesa_phone')
                                    ->label('M-Pesa Phone')
                                    ->placeholder('—'),
                                TextEntry::make('bankAccount.name')
                                    ->label('Bank Account')
                                    ->placeholder('—'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match (strtolower($state)) {
                                        'posted' => 'success',
                                        default => 'warning',
                                    }),
                            ])
                            ->columns(4),
                    ]),

                Section::make('Invoices')
                    ->schema([
                        RepeatableEntry::make('invoices')
                            ->label('')
                            ->schema([
                                TextEntry::make('no')
                                    ->label('Invoice No'),
                                TextEntry::make('posting_date')
                                    ->label('Posting Date')
                                    ->date(),
                                TextEntry::make('due_date')
                                    ->label('Due Date')
                                    ->date()
                                    ->placeholder('—'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (?string $state): string => match (strtolower($state ?? '')) {
                                        'posted' => 'success',
                                        'open' => 'warning',
                                        default => 'gray',
                                    }),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
}
