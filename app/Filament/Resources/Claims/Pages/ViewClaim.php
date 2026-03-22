<?php

namespace App\Filament\Resources\Claims\Pages;

use App\Enums\ApprovalAction;
use App\Filament\Resources\Claims\ClaimResource;
use App\Models\Claim;
use App\Models\ClaimApproval;
use App\Services\ClaimApprovalService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewClaim extends ViewRecord
{
    protected static string $resource = ClaimResource::class;

    private function currentPendingApproval(Claim $record): ?ClaimApproval
    {
        return $record->approvals
            ->where('step_order', $record->current_step)
            ->where('action', ApprovalAction::Pending)
            ->where('approver_user_id', auth()->id())
            ->first();
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (Claim $record) => $this->currentPendingApproval($record) !== null)
                ->schema([
                    TextInput::make('approved_amount')
                        ->label('Approved Amount (KES)')
                        ->numeric()
                        ->placeholder('Leave blank to use claimed amount'),
                    Textarea::make('comments')
                        ->label('Comments')
                        ->maxLength(500),
                ])
                ->action(function (Claim $record, array $data): void {
                    $approval = $this->currentPendingApproval($record);
                    app(ClaimApprovalService::class)->approve(
                        $approval,
                        auth()->user(),
                        $data['comments'] ?? null,
                        filled($data['approved_amount']) ? $data['approved_amount'] : null,
                    );
                    Notification::make()->success()->title('Claim approved.')->send();
                }),
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (Claim $record) => $this->currentPendingApproval($record) !== null)
                ->schema([
                    Textarea::make('reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function (Claim $record, array $data): void {
                    $approval = $this->currentPendingApproval($record);
                    app(ClaimApprovalService::class)->reject($approval, auth()->user(), $data['reason']);
                    Notification::make()->warning()->title('Claim rejected.')->send();
                }),
            Action::make('return_to_member')
                ->label('Return to Member')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn (Claim $record) => $this->currentPendingApproval($record) !== null)
                ->schema([
                    Textarea::make('comments')
                        ->label('Reason for Return')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function (Claim $record, array $data): void {
                    $approval = $this->currentPendingApproval($record);
                    app(ClaimApprovalService::class)->return($approval, auth()->user(), $data['comments']);
                    Notification::make()->info()->title('Claim returned to member.')->send();
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
                            ->columnSpan(2),
                        TextEntry::make('approvalTemplate.name')
                            ->label('Approval Template'),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                        TextEntry::make('claimed_amount')
                            ->label('Claimed Amount')
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('approved_amount')
                            ->label('Approved Amount')
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('current_step')
                            ->label('Current Step'),
                        TextEntry::make('submitted_at')
                            ->label('Submitted At')
                            ->dateTime(),
                        TextEntry::make('approved_at')
                            ->label('Approved At')
                            ->dateTime(),
                        TextEntry::make('rejected_at')
                            ->label('Rejected At')
                            ->dateTime(),
                        TextEntry::make('rejection_reason')
                            ->columnSpanFull(),
                    ]),
                Section::make('Member & Payee Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('member.name')
                            ->label('Member'),
                        TextEntry::make('payee_name')
                            ->label('Payee Name'),
                        TextEntry::make('payment_method')
                            ->label('Payment Method')
                            ->badge(),
                        TextEntry::make('bank_name')
                            ->label('Bank'),
                        TextEntry::make('bank_account_name')
                            ->label('Account Name'),
                        TextEntry::make('bank_account_no')
                            ->label('Account No'),
                        TextEntry::make('bank_branch')
                            ->label('Branch'),
                        TextEntry::make('mpesa_phone')
                            ->label('M-PESA Phone'),
                    ]),
                Section::make('Claim Lines')
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->schema([
                                TextEntry::make('line_no')
                                    ->label('Line'),
                                TextEntry::make('description'),
                                TextEntry::make('quantity')
                                    ->numeric(),
                                TextEntry::make('unit_amount')
                                    ->label('Unit Amount')
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('line_amount')
                                    ->label('Total')
                                    ->numeric(decimalPlaces: 2),
                            ])
                            ->columns(5),
                    ]),
                Section::make('Approval Chain')
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
                Section::make('Finance Trail')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('vendor.name')
                            ->label('Vendor'),
                        TextEntry::make('purchaseHeader.id')
                            ->label('Purchase Order ID'),
                        TextEntry::make('vendorPayment.id')
                            ->label('Vendor Payment ID'),
                    ]),
            ]);
    }
}
