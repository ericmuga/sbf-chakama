<?php

namespace App\Filament\Resources\Chakama\Pages;

use App\Enums\FundWithdrawalStatus;
use App\Filament\Resources\Chakama\FundWithdrawalResource;
use App\Models\FundWithdrawal;
use App\Services\FundWithdrawalService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewFundWithdrawal extends ViewRecord
{
    protected static string $resource = FundWithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        /** @var FundWithdrawal $record */
        $record = $this->getRecord();
        $service = app(FundWithdrawalService::class);
        $user = auth()->user();

        return [
            EditAction::make()
                ->visible(fn () => $record->status === FundWithdrawalStatus::Draft),

            Action::make('submit')
                ->label('Submit for Approval')
                ->color('primary')
                ->icon('heroicon-o-paper-airplane')
                ->visible(fn () => $record->status === FundWithdrawalStatus::Draft)
                ->requiresConfirmation()
                ->action(fn () => $service->submitWithdrawal($record)),

            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(function () use ($record, $user): bool {
                    if (! in_array($record->status, [FundWithdrawalStatus::Submitted->value, FundWithdrawalStatus::UnderReview->value])) {
                        return false;
                    }
                    $currentApproval = $record->approvals->firstWhere('step_order', $record->current_step);

                    return $currentApproval && $currentApproval->approver_user_id === $user?->id;
                })
                ->schema([
                    Textarea::make('comments')
                        ->label('Comments (optional)')
                        ->rows(3),
                ])
                ->action(function (array $data) use ($record, $service, $user): void {
                    $approval = $record->approvals->firstWhere('step_order', $record->current_step);
                    if ($approval) {
                        $service->approveStep($approval, $user, $data['comments'] ?? null);
                    }
                }),

            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(function () use ($record, $user): bool {
                    if (! in_array($record->status->value ?? $record->status, [FundWithdrawalStatus::Submitted->value, FundWithdrawalStatus::UnderReview->value])) {
                        return false;
                    }
                    $currentApproval = $record->approvals->firstWhere('step_order', $record->current_step);

                    return $currentApproval && $currentApproval->approver_user_id === $user?->id;
                })
                ->schema([
                    Textarea::make('reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) use ($record, $service, $user): void {
                    $approval = $record->approvals->firstWhere('step_order', $record->current_step);
                    if ($approval) {
                        $service->rejectStep($approval, $user, $data['reason']);
                    }
                }),
        ];
    }
}
