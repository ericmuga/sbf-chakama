<?php

namespace App\Filament\Resources\Claims\Tables;

use App\Enums\ApprovalAction;
use App\Enums\ClaimStatus;
use App\Events\ClaimApprovalActioned;
use App\Events\ClaimFullyApproved;
use App\Events\ClaimRejected;
use App\Models\Claim;
use App\Services\ClaimService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ClaimsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('Claim No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('member.name')
                    ->label('Member')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('claim_type')
                    ->label('Type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('subject')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('claimed_amount')
                    ->label('Claimed (KES)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('approved_amount')
                    ->label('Approved (KES)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('current_step')
                    ->label('Step')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y')
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->label('Approved')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ClaimStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Claim $record) => in_array($record->status, [ClaimStatus::Submitted, ClaimStatus::UnderReview]))
                    ->schema([
                        TextInput::make('approved_amount')
                            ->label('Approved Amount (leave blank to use claimed amount)')
                            ->numeric()
                            ->minValue(0),
                        Textarea::make('comments')
                            ->label('Comments')
                            ->maxLength(1000),
                    ])
                    ->action(function (Claim $record, array $data): void {
                        $approval = $record->currentApproval;

                        if (! $approval || $approval->action !== ApprovalAction::Pending) {
                            Notification::make()
                                ->warning()
                                ->title('No pending approval step found.')
                                ->send();

                            return;
                        }

                        DB::transaction(function () use ($approval, $record, $data): void {
                            $approval->update([
                                'action' => ApprovalAction::Approved,
                                'comments' => $data['comments'] ?? null,
                                'actioned_at' => now(),
                            ]);

                            $claim = $record->fresh(['approvals']);
                            $allApproved = $claim->approvals->every(fn ($a) => $a->action === ApprovalAction::Approved);

                            if ($allApproved) {
                                $claim->update([
                                    'status' => ClaimStatus::Approved,
                                    'approved_at' => now(),
                                    'approved_amount' => $data['approved_amount'] ?? $claim->claimed_amount,
                                ]);

                                ClaimFullyApproved::dispatch($claim);
                            } else {
                                $nextApproval = $claim->approvals
                                    ->where('action', ApprovalAction::Pending)
                                    ->sortBy('step_order')
                                    ->first();

                                $claim->update([
                                    'status' => ClaimStatus::UnderReview,
                                    'current_step' => $nextApproval?->step_order ?? $claim->current_step,
                                ]);

                                ClaimApprovalActioned::dispatch($approval, $claim);
                            }
                        });

                        Notification::make()
                            ->success()
                            ->title('Claim approved.')
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Claim $record) => in_array($record->status, [ClaimStatus::Submitted, ClaimStatus::UnderReview]))
                    ->schema([
                        Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (Claim $record, array $data): void {
                        $approval = $record->currentApproval;

                        if (! $approval || $approval->action !== ApprovalAction::Pending) {
                            Notification::make()
                                ->warning()
                                ->title('No pending approval step found.')
                                ->send();

                            return;
                        }

                        DB::transaction(function () use ($approval, $record, $data): void {
                            $approval->update([
                                'action' => ApprovalAction::Rejected,
                                'comments' => $data['reason'],
                                'actioned_at' => now(),
                            ]);

                            $record->update([
                                'status' => ClaimStatus::Rejected,
                                'rejected_at' => now(),
                                'rejection_reason' => $data['reason'],
                            ]);

                            ClaimRejected::dispatch($record->fresh());
                        });

                        Notification::make()
                            ->danger()
                            ->title('Claim rejected.')
                            ->send();
                    }),
                Action::make('return_claim')
                    ->label('Return')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (Claim $record) => in_array($record->status, [ClaimStatus::Submitted, ClaimStatus::UnderReview]))
                    ->schema([
                        Textarea::make('comments')
                            ->label('Return Reason')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (Claim $record, array $data): void {
                        $approval = $record->currentApproval;

                        if (! $approval || $approval->action !== ApprovalAction::Pending) {
                            Notification::make()
                                ->warning()
                                ->title('No pending approval step found.')
                                ->send();

                            return;
                        }

                        DB::transaction(function () use ($approval, $record, $data): void {
                            $approval->update([
                                'action' => ApprovalAction::Returned,
                                'comments' => $data['comments'],
                                'actioned_at' => now(),
                            ]);

                            $record->approvals()->where('action', ApprovalAction::Pending->value)->delete();

                            $record->update([
                                'status' => ClaimStatus::Draft,
                                'submitted_at' => null,
                                'approval_template_id' => null,
                                'current_step' => 0,
                            ]);
                        });

                        Notification::make()
                            ->warning()
                            ->title('Claim returned to draft.')
                            ->send();
                    }),
                Action::make('convert_to_po')
                    ->label('Convert to PO')
                    ->icon('heroicon-o-document-plus')
                    ->color('primary')
                    ->visible(fn (Claim $record) => $record->status === ClaimStatus::Approved)
                    ->requiresConfirmation()
                    ->action(function (Claim $record): void {
                        app(ClaimService::class)->convertToPurchase($record);

                        Notification::make()
                            ->success()
                            ->title('Purchase order created.')
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
