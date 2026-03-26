<?php

namespace App\Services;

use App\Enums\ApprovalAction;
use App\Enums\FundWithdrawalStatus;
use App\Models\ClaimApprovalTemplate;
use App\Models\Finance\NumberSeries;
use App\Models\FundAccount;
use App\Models\FundWithdrawal;
use App\Models\FundWithdrawalApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FundWithdrawalService
{
    public function createWithdrawal(FundAccount $fund, array $data, User $submitter): FundWithdrawal
    {
        $no = NumberSeries::generate('FWITH');

        return FundWithdrawal::create(array_merge($data, [
            'no' => $no,
            'fund_account_id' => $fund->id,
            'status' => FundWithdrawalStatus::Draft,
            'submitted_by' => $submitter->id,
            'number_series_code' => 'FWITH',
        ]));
    }

    public function submitWithdrawal(FundWithdrawal $withdrawal): void
    {
        DB::transaction(function () use ($withdrawal) {
            $template = $withdrawal->approval_template_id
                ? ClaimApprovalTemplate::find($withdrawal->approval_template_id)
                : ClaimApprovalTemplate::query()->active()->orderByDesc('is_default')->first();

            if ($template) {
                foreach ($template->steps as $step) {
                    FundWithdrawalApproval::create([
                        'fund_withdrawal_id' => $withdrawal->id,
                        'step_order' => $step->step_order,
                        'approver_user_id' => $step->approver_user_id,
                        'action' => ApprovalAction::Pending,
                        'due_by' => now()->addDays(3),
                    ]);
                }
            }

            $withdrawal->update([
                'status' => FundWithdrawalStatus::Submitted,
                'submitted_at' => now(),
                'current_step' => $template?->steps->first()?->step_order ?? 1,
                'approval_template_id' => $template?->id,
            ]);
        });
    }

    public function approveStep(FundWithdrawalApproval $approval, User $approver, ?string $comments = null): void
    {
        $approval->action = ApprovalAction::Approved;
        $approval->actioned_at = now();
        $approval->comments = $comments;
        $approval->save();

        $withdrawal = $approval->fundWithdrawal->load('approvals');
        $nextStep = $approval->step_order + 1;

        $withdrawal->current_step = $nextStep;

        if ($withdrawal->is_fully_approved) {
            $withdrawal->status = FundWithdrawalStatus::Approved;
            $withdrawal->approved_at = now();
        }

        $withdrawal->save();
    }

    public function rejectStep(FundWithdrawalApproval $approval, User $approver, string $reason): void
    {
        $approval->action = ApprovalAction::Rejected;
        $approval->actioned_at = now();
        $approval->comments = $reason;
        $approval->save();

        $withdrawal = $approval->fundWithdrawal;
        $withdrawal->status = FundWithdrawalStatus::Rejected;
        $withdrawal->rejected_at = now();
        $withdrawal->rejection_reason = $reason;
        $withdrawal->save();
    }

    public function cancelWithdrawal(FundWithdrawal $withdrawal, User $user, string $reason): void
    {
        $withdrawal->status = FundWithdrawalStatus::Cancelled;
        $withdrawal->rejection_reason = $reason;
        $withdrawal->save();
    }
}
