<?php

namespace App\Services;

use App\Enums\DirectCostStatus;
use App\Events\DirectCostApproved;
use App\Events\DirectCostPosted;
use App\Events\DirectCostRejected;
use App\Events\DirectCostSubmitted;
use App\Models\Finance\GlAccount;
use App\Models\Finance\GlEntry;
use App\Models\Finance\NumberSeries;
use App\Models\Project;
use App\Models\ProjectDirectCost;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProjectCostService
{
    public function __construct(private readonly ProjectService $projectService) {}

    public function submitDirectCost(Project $project, array $data, User $submitter): ProjectDirectCost
    {
        return DB::transaction(function () use ($project, $data, $submitter) {
            $no = NumberSeries::generate('DCOST');

            $receiptPath = null;
            if (isset($data['receipt_file']) && $data['receipt_file']) {
                $file = $data['receipt_file'];
                $receiptPath = $file->store("project-receipts/{$project->no}", 'local');
                unset($data['receipt_file']);
            }

            $cost = ProjectDirectCost::create(array_merge($data, [
                'no' => $no,
                'project_id' => $project->id,
                'status' => DirectCostStatus::Pending,
                'submitted_by' => $submitter->id,
                'number_series_code' => 'DCOST',
                'receipt_path' => $receiptPath ?? $data['receipt_path'] ?? null,
            ]));

            DirectCostSubmitted::dispatch($cost);

            return $cost;
        });
    }

    public function approveDirectCost(ProjectDirectCost $cost, User $approver): void
    {
        $cost->update([
            'status' => DirectCostStatus::Approved,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        DirectCostApproved::dispatch($cost);
    }

    public function postDirectCost(ProjectDirectCost $cost, User $poster): void
    {
        if ($cost->status !== DirectCostStatus::Approved) {
            throw new RuntimeException('Only approved direct costs can be posted.');
        }

        DB::transaction(function () use ($cost, $poster) {
            // Debit the expense GL account
            GlEntry::create([
                'posting_date' => $cost->posting_date,
                'document_no' => $cost->no,
                'account_no' => $cost->gl_account_no,
                'debit_amount' => $cost->amount,
                'credit_amount' => 0,
                'source_type' => 'ProjectDirectCost',
                'source_id' => $cost->id,
                'project_id' => $cost->project_id,
                'created_by' => $poster->id,
            ]);

            // Credit the bank or cash account
            $creditGlNo = $this->resolveCreditGlAccountNo($cost);

            GlEntry::create([
                'posting_date' => $cost->posting_date,
                'document_no' => $cost->no,
                'account_no' => $creditGlNo,
                'debit_amount' => 0,
                'credit_amount' => $cost->amount,
                'source_type' => 'ProjectDirectCost',
                'source_id' => $cost->id,
                'project_id' => $cost->project_id,
                'created_by' => $poster->id,
            ]);

            $cost->update([
                'status' => DirectCostStatus::Posted,
                'posted_at' => now(),
                'posted_by' => $poster->id,
            ]);

            $this->projectService->recalculateSpent($cost->project);
        });

        DirectCostPosted::dispatch($cost);
    }

    public function rejectDirectCost(ProjectDirectCost $cost, User $approver, string $reason): void
    {
        $cost->update([
            'status' => DirectCostStatus::Rejected,
            'rejection_reason' => $reason,
        ]);

        DirectCostRejected::dispatch($cost);
    }

    public function voidDirectCost(ProjectDirectCost $cost, User $user): void
    {
        if ($cost->status !== DirectCostStatus::Posted) {
            throw new RuntimeException('Only posted direct costs can be voided.');
        }

        DB::transaction(function () use ($cost, $user) {
            // Reversing GL entries (swap debit/credit)
            GlEntry::create([
                'posting_date' => now()->toDateString(),
                'document_no' => $cost->no,
                'account_no' => $cost->gl_account_no,
                'debit_amount' => 0,
                'credit_amount' => $cost->amount,
                'source_type' => 'ProjectDirectCostVoid',
                'source_id' => $cost->id,
                'project_id' => $cost->project_id,
                'created_by' => $user->id,
            ]);

            $creditGlNo = $this->resolveCreditGlAccountNo($cost);

            GlEntry::create([
                'posting_date' => now()->toDateString(),
                'document_no' => $cost->no,
                'account_no' => $creditGlNo,
                'debit_amount' => $cost->amount,
                'credit_amount' => 0,
                'source_type' => 'ProjectDirectCostVoid',
                'source_id' => $cost->id,
                'project_id' => $cost->project_id,
                'created_by' => $user->id,
            ]);

            $cost->update(['status' => DirectCostStatus::Voided]);

            $this->projectService->recalculateSpent($cost->project);
        });
    }

    private function resolveCreditGlAccountNo(ProjectDirectCost $cost): string
    {
        if ($cost->bank_account_id) {
            $bankGlNo = $cost->bankAccount?->bankPostingGroup?->bank_account_gl_no;
            if ($bankGlNo) {
                return $bankGlNo;
            }
        }

        $cashAccount = GlAccount::where('account_type', 'Posting')
            ->where('name', 'like', '%cash%')
            ->first();

        if (! $cashAccount) {
            throw new RuntimeException('No cash GL account found for crediting the direct cost.');
        }

        return $cashAccount->no;
    }
}
