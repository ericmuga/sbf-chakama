<?php

namespace Database\Seeders;

use App\Enums\ApprovalAction;
use App\Enums\ClaimPaymentMethod;
use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use App\Models\Claim;
use App\Models\ClaimApproval;
use App\Models\ClaimApprovalTemplate;
use App\Models\ClaimApprovalTemplateStep;
use App\Models\ClaimLine;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\SalesSetup;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClaimsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure claim number series exists
        NumberSeries::firstOrCreate(['code' => 'CLAIM'], [
            'description' => 'SBF Claim Numbers',
            'prefix' => 'CLM-',
            'last_no' => 0,
            'length' => 6,
            'is_manual_allowed' => false,
            'prevent_repeats' => true,
            'is_active' => true,
        ]);

        // Link the series to SalesSetup so ClaimService can find it
        $salesSetup = SalesSetup::first();
        if ($salesSetup && ! $salesSetup->claim_nos) {
            $salesSetup->update(['claim_nos' => 'CLAIM']);
        }

        // ─── Approvers ───────────────────────────────────────────────────────

        $treasurer = User::where('email', 'treasurer@sbfchakama.co.ke')->first()
            ?? User::where('name', 'like', '%Treasurer%')->first();

        $chairman = User::where('email', 'chairman@sbfchakama.co.ke')->first()
            ?? User::where('name', 'like', '%Chairman%')->first();

        // ─── Approval Templates ───────────────────────────────────────────────

        // General 2-step template (applies to all claim types)
        $generalTemplate = ClaimApprovalTemplate::firstOrCreate(
            ['name' => 'General 2-Step Approval'],
            [
                'claim_type' => null,
                'is_default' => true,
                'is_active' => true,
            ]
        );

        if ($treasurer && $generalTemplate->steps()->where('step_order', 1)->doesntExist()) {
            ClaimApprovalTemplateStep::create([
                'template_id' => $generalTemplate->id,
                'step_order' => 1,
                'label' => 'Treasurer Review',
                'approver_user_id' => $treasurer->id,
                'is_required' => true,
            ]);
        }

        if ($chairman && $generalTemplate->steps()->where('step_order', 2)->doesntExist()) {
            ClaimApprovalTemplateStep::create([
                'template_id' => $generalTemplate->id,
                'step_order' => 2,
                'label' => 'Chairman Approval',
                'approver_user_id' => $chairman->id,
                'is_required' => true,
            ]);
        }

        // Medical-specific single-step template
        $medicalTemplate = ClaimApprovalTemplate::firstOrCreate(
            ['name' => 'Medical Claims — Fast Track'],
            [
                'claim_type' => ClaimType::Medical,
                'is_default' => false,
                'is_active' => true,
            ]
        );

        if ($treasurer && $medicalTemplate->steps()->where('step_order', 1)->doesntExist()) {
            ClaimApprovalTemplateStep::create([
                'template_id' => $medicalTemplate->id,
                'step_order' => 1,
                'label' => 'Treasurer Approval',
                'approver_user_id' => $treasurer->id,
                'is_required' => true,
            ]);
        }

        // ─── Sample Claims ────────────────────────────────────────────────────

        $member = Member::where('is_sbf', true)->first();

        if (! $member) {
            $this->command->warn('No SBF member found. Run SampleMemberSeeder first.');

            return;
        }

        $payeeData = [
            'payee_name' => $member->name ?? 'Amina Wanjiru',
            'payment_method' => ClaimPaymentMethod::BankTransfer,
            'bank_name' => $member->bank_name ?? 'KCB',
            'bank_account_name' => $member->bank_account_name ?? 'Amina Wanjiru',
            'bank_account_no' => $member->bank_account_no ?? '1234567890',
            'bank_branch' => $member->bank_branch ?? 'Chakama Branch',
        ];

        // ── 1. Draft claim ────────────────────────────────────────────────────

        $draftClaim = Claim::firstOrCreate(
            ['member_id' => $member->id, 'status' => ClaimStatus::Draft->value, 'subject' => 'Hospital Bill — Draft'],
            array_merge($payeeData, [
                'no' => NumberSeries::generate('CLAIM'),
                'number_series_code' => 'CLAIM',
                'claim_type' => ClaimType::Medical,
                'subject' => 'Hospital Bill — Draft',
                'description' => 'Admitted to Malindi District Hospital for malaria treatment.',
                'claimed_amount' => 15000,
            ])
        );

        if ($draftClaim->wasRecentlyCreated) {
            ClaimLine::create([
                'claim_id' => $draftClaim->id,
                'line_no' => 10,
                'description' => 'Hospital admission fee',
                'quantity' => 1,
                'unit_amount' => 8000,
                'line_amount' => 8000,
            ]);
            ClaimLine::create([
                'claim_id' => $draftClaim->id,
                'line_no' => 20,
                'description' => 'Medication & pharmacy',
                'quantity' => 1,
                'unit_amount' => 7000,
                'line_amount' => 7000,
            ]);
        }

        // ── 2. Submitted claim (awaiting treasurer approval) ──────────────────

        $submittedClaim = Claim::firstOrCreate(
            ['member_id' => $member->id, 'status' => ClaimStatus::Submitted->value, 'subject' => 'Funeral Expenses — Submitted'],
            array_merge($payeeData, [
                'no' => NumberSeries::generate('CLAIM'),
                'number_series_code' => 'CLAIM',
                'claim_type' => ClaimType::Funeral,
                'subject' => 'Funeral Expenses — Submitted',
                'description' => 'Burial expenses for a deceased parent.',
                'claimed_amount' => 30000,
                'approved_amount' => 0,
                'status' => ClaimStatus::Submitted,
                'approval_template_id' => $generalTemplate->id,
                'current_step' => 1,
                'submitted_at' => now()->subDays(1),
            ])
        );

        if ($submittedClaim->wasRecentlyCreated) {
            ClaimLine::create([
                'claim_id' => $submittedClaim->id,
                'line_no' => 10,
                'description' => 'Coffin and mortuary charges',
                'quantity' => 1,
                'unit_amount' => 18000,
                'line_amount' => 18000,
            ]);
            ClaimLine::create([
                'claim_id' => $submittedClaim->id,
                'line_no' => 20,
                'description' => 'Transport and logistics',
                'quantity' => 1,
                'unit_amount' => 12000,
                'line_amount' => 12000,
            ]);

            if ($treasurer) {
                ClaimApproval::create([
                    'claim_id' => $submittedClaim->id,
                    'step_order' => 1,
                    'approver_user_id' => $treasurer->id,
                    'action' => ApprovalAction::Pending,
                    'due_by' => now()->addDays(3),
                ]);
            }

            if ($chairman) {
                ClaimApproval::create([
                    'claim_id' => $submittedClaim->id,
                    'step_order' => 2,
                    'approver_user_id' => $chairman->id,
                    'action' => ApprovalAction::Pending,
                    'due_by' => now()->addDays(5),
                ]);
            }
        }

        // ── 3. Under review (treasurer approved, awaiting chairman) ──────────

        $underReviewClaim = Claim::firstOrCreate(
            ['member_id' => $member->id, 'status' => ClaimStatus::UnderReview->value, 'subject' => 'Emergency Relief — Under Review'],
            array_merge($payeeData, [
                'no' => NumberSeries::generate('CLAIM'),
                'number_series_code' => 'CLAIM',
                'claim_type' => ClaimType::Emergency,
                'subject' => 'Emergency Relief — Under Review',
                'description' => 'Flood damage to household property.',
                'claimed_amount' => 25000,
                'approved_amount' => 0,
                'status' => ClaimStatus::UnderReview,
                'approval_template_id' => $generalTemplate->id,
                'current_step' => 2,
                'submitted_at' => now()->subDays(3),
            ])
        );

        if ($underReviewClaim->wasRecentlyCreated) {
            ClaimLine::create([
                'claim_id' => $underReviewClaim->id,
                'line_no' => 10,
                'description' => 'Household goods replacement',
                'quantity' => 1,
                'unit_amount' => 25000,
                'line_amount' => 25000,
            ]);

            if ($treasurer) {
                ClaimApproval::create([
                    'claim_id' => $underReviewClaim->id,
                    'step_order' => 1,
                    'approver_user_id' => $treasurer->id,
                    'action' => ApprovalAction::Approved,
                    'comments' => 'Verified receipts. Recommend for approval.',
                    'actioned_at' => now()->subDay(),
                    'due_by' => now()->addDays(2),
                ]);
            }

            if ($chairman) {
                ClaimApproval::create([
                    'claim_id' => $underReviewClaim->id,
                    'step_order' => 2,
                    'approver_user_id' => $chairman->id,
                    'action' => ApprovalAction::Pending,
                    'due_by' => now()->addDays(2),
                ]);
            }
        }

        // ── 4. Approved claim (ready for PO conversion) ───────────────────────

        $approvedClaim = Claim::firstOrCreate(
            ['member_id' => $member->id, 'status' => ClaimStatus::Approved->value, 'subject' => 'Medical Claim — Approved'],
            array_merge($payeeData, [
                'no' => NumberSeries::generate('CLAIM'),
                'number_series_code' => 'CLAIM',
                'claim_type' => ClaimType::Medical,
                'subject' => 'Medical Claim — Approved',
                'description' => 'Knee surgery at Nairobi Hospital.',
                'claimed_amount' => 50000,
                'approved_amount' => 45000,
                'status' => ClaimStatus::Approved,
                'approval_template_id' => $generalTemplate->id,
                'current_step' => 2,
                'submitted_at' => now()->subDays(7),
                'approved_at' => now()->subDays(2),
            ])
        );

        if ($approvedClaim->wasRecentlyCreated) {
            ClaimLine::create([
                'claim_id' => $approvedClaim->id,
                'line_no' => 10,
                'description' => 'Surgical procedure',
                'quantity' => 1,
                'unit_amount' => 40000,
                'line_amount' => 40000,
            ]);
            ClaimLine::create([
                'claim_id' => $approvedClaim->id,
                'line_no' => 20,
                'description' => 'Post-op medication',
                'quantity' => 1,
                'unit_amount' => 5000,
                'line_amount' => 5000,
            ]);

            if ($treasurer) {
                ClaimApproval::create([
                    'claim_id' => $approvedClaim->id,
                    'step_order' => 1,
                    'approver_user_id' => $treasurer->id,
                    'action' => ApprovalAction::Approved,
                    'comments' => 'All documents in order.',
                    'actioned_at' => now()->subDays(5),
                    'due_by' => now()->subDays(4),
                ]);
            }

            if ($chairman) {
                ClaimApproval::create([
                    'claim_id' => $approvedClaim->id,
                    'step_order' => 2,
                    'approver_user_id' => $chairman->id,
                    'action' => ApprovalAction::Approved,
                    'comments' => 'Approved. Amount adjusted to KES 45,000.',
                    'actioned_at' => now()->subDays(2),
                    'due_by' => now()->subDays(1),
                ]);
            }
        }
    }
}
