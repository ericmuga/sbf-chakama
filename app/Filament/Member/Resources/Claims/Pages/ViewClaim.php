<?php

namespace App\Filament\Member\Resources\Claims\Pages;

use App\Enums\ClaimStatus;
use App\Filament\Member\Resources\Claims\ClaimResource;
use App\Models\Claim;
use App\Services\ClaimService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewClaim extends ViewRecord
{
    protected static string $resource = ClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (Claim $record) => $record->status === ClaimStatus::Draft),
            Action::make('submit')
                ->label('Submit for Approval')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (Claim $record) => $record->status === ClaimStatus::Draft)
                ->requiresConfirmation()
                ->modalDescription('Once submitted, this claim will enter the approval process. Are you sure?')
                ->action(function (Claim $record): void {
                    app(ClaimService::class)->submitClaim($record, auth()->user());

                    Notification::make()
                        ->success()
                        ->title('Claim submitted for approval.')
                        ->send();
                }),
            Action::make('cancel')
                ->label('Cancel Claim')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (Claim $record) => in_array($record->status, [ClaimStatus::Draft, ClaimStatus::Submitted]))
                ->schema([
                    Textarea::make('reason')
                        ->label('Cancellation Reason')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function (Claim $record, array $data): void {
                    app(ClaimService::class)->cancelClaim($record, auth()->user(), $data['reason']);

                    Notification::make()
                        ->warning()
                        ->title('Claim cancelled.')
                        ->send();
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Claim Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('no')
                            ->label('Claim No'),
                        TextEntry::make('claim_type')
                            ->badge(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('subject')
                            ->columnSpan(3),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                        TextEntry::make('claimed_amount')
                            ->label('Claimed Amount (KES)')
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('approved_amount')
                            ->label('Approved Amount (KES)')
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('submitted_at')
                            ->dateTime(),
                        TextEntry::make('rejection_reason')
                            ->columnSpanFull()
                            ->visible(fn (Claim $record) => filled($record->rejection_reason)),
                    ]),
                Section::make('Claim Items')
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->schema([
                                TextEntry::make('line_no')
                                    ->label('Line'),
                                TextEntry::make('description'),
                                TextEntry::make('quantity'),
                                TextEntry::make('unit_amount')
                                    ->label('Unit (KES)')
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('line_amount')
                                    ->label('Total (KES)')
                                    ->numeric(decimalPlaces: 2),
                            ])
                            ->columns(5),
                    ]),
                Section::make('Approval Progress')
                    ->schema([
                        RepeatableEntry::make('approvals')
                            ->schema([
                                TextEntry::make('step_order')
                                    ->label('Step'),
                                TextEntry::make('approver.name')
                                    ->label('Approver'),
                                TextEntry::make('action')
                                    ->badge(),
                                TextEntry::make('comments'),
                                TextEntry::make('actioned_at')
                                    ->label('Actioned At')
                                    ->dateTime(),
                                TextEntry::make('due_by')
                                    ->label('Due By')
                                    ->dateTime(),
                            ])
                            ->columns(3),
                    ]),
                Section::make('Payment Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('payee_name'),
                        TextEntry::make('payment_method')
                            ->badge(),
                        TextEntry::make('mpesa_phone'),
                        TextEntry::make('bank_name'),
                        TextEntry::make('bank_account_name'),
                        TextEntry::make('bank_account_no'),
                        TextEntry::make('bank_branch'),
                    ]),
                Section::make('Finance Status')
                    ->columns(2)
                    ->visible(fn (Claim $record) => in_array($record->status, [
                        ClaimStatus::PurchaseCreated,
                        ClaimStatus::Paid,
                    ]))
                    ->schema([
                        TextEntry::make('purchaseHeader.id')
                            ->label('Purchase Order'),
                        TextEntry::make('vendorPayment.id')
                            ->label('Payment Reference'),
                    ]),
            ]);
    }
}
