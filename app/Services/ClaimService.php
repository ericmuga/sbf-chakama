<?php

namespace App\Services;

use App\Enums\ApprovalAction;
use App\Enums\ClaimStatus;
use App\Events\ClaimPaymentCreated;
use App\Events\ClaimSubmitted;
use App\Models\Claim;
use App\Models\ClaimApproval;
use App\Models\ClaimApprovalTemplate;
use App\Models\ClaimAttachment;
use App\Models\ClaimLine;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\PurchaseHeader;
use App\Models\Finance\PurchaseLine;
use App\Models\Finance\PurchaseSetup;
use App\Models\Finance\SalesSetup;
use App\Models\Finance\Vendor;
use App\Models\Finance\VendorPostingGroup;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ClaimService
{
    public function createClaim(Member $member, array $data): Claim
    {
        return DB::transaction(function () use ($member, $data) {
            $setup = SalesSetup::first();
            $no = $setup?->claim_nos ? NumberSeries::generate($setup->claim_nos) : 'CLM-'.now()->format('YmdHis');

            return Claim::create(array_merge($data, [
                'no' => $no,
                'member_id' => $member->id,
                'status' => ClaimStatus::Draft,
                'number_series_code' => $setup?->claim_nos ?? 'CLAIM',
                'payee_name' => $data['payee_name'] ?? ($member->name ?? $member->user?->name ?? $member->no),
                'bank_name' => $data['bank_name'] ?? $member->bank_name,
                'bank_account_name' => $data['bank_account_name'] ?? $member->bank_account_name,
                'bank_account_no' => $data['bank_account_no'] ?? $member->bank_account_no,
                'bank_branch' => $data['bank_branch'] ?? $member->bank_branch,
                'mpesa_phone' => $data['mpesa_phone'] ?? $member->mpesa_phone,
                'vendor_id' => $member->financeVendor?->id,
            ]));
        });
    }

    public function addLine(Claim $claim, array $lineData): ClaimLine
    {
        if ($claim->status !== ClaimStatus::Draft) {
            throw new InvalidArgumentException('Lines can only be added to draft claims.');
        }

        $lineAmount = (float) $lineData['quantity'] * (float) $lineData['unit_amount'];
        $maxLineNo = $claim->lines()->max('line_no') ?? 0;

        return ClaimLine::create([
            'claim_id' => $claim->id,
            'line_no' => $maxLineNo + 10,
            'description' => $lineData['description'],
            'quantity' => $lineData['quantity'] ?? 1,
            'unit_amount' => $lineData['unit_amount'],
            'line_amount' => $lineData['line_amount'] ?? $lineAmount,
            'service_id' => $lineData['service_id'] ?? null,
        ]);
    }

    public function removeLine(ClaimLine $line): void
    {
        if ($line->claim->status !== ClaimStatus::Draft) {
            throw new InvalidArgumentException('Lines can only be removed from draft claims.');
        }

        $line->delete();
    }

    public function addAttachment(Claim $claim, array $data, User $uploader): ClaimAttachment
    {
        $file = $data['file'];
        $path = $file->store("claim-attachments/{$claim->no}", 'local');

        return ClaimAttachment::create([
            'claim_id' => $claim->id,
            'uploaded_by' => $uploader->id,
            'document_type' => $data['document_type'],
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }

    public function submitClaim(Claim $claim, User $submitter): void
    {
        if ($claim->status !== ClaimStatus::Draft) {
            throw new InvalidArgumentException('Only draft claims can be submitted.');
        }

        if ($claim->lines()->count() === 0) {
            throw new InvalidArgumentException('Claim must have at least one line item.');
        }

        DB::transaction(function () use ($claim) {
            // Update claimed_amount from lines if not manually set
            $totalLines = $claim->lines()->sum('line_amount');
            if ((float) $claim->claimed_amount === 0.0) {
                $claim->claimed_amount = $totalLines;
            }

            // Find applicable approval template
            $template = ClaimApprovalTemplate::query()
                ->active()
                ->forType($claim->claim_type)
                ->orderByDesc('is_default')
                ->first();

            // Generate approval rows from template steps
            if ($template) {
                foreach ($template->steps as $step) {
                    ClaimApproval::create([
                        'claim_id' => $claim->id,
                        'step_order' => $step->step_order,
                        'approver_user_id' => $step->approver_user_id,
                        'action' => ApprovalAction::Pending,
                        'due_by' => now()->addDays(3),
                    ]);
                }
            }

            $claim->update([
                'status' => ClaimStatus::Submitted,
                'submitted_at' => now(),
                'current_step' => $template?->steps->first()?->step_order ?? 1,
                'approval_template_id' => $template?->id,
            ]);
        });

        ClaimSubmitted::dispatch($claim);
    }

    public function convertToPurchase(Claim $claim): PurchaseHeader
    {
        if ($claim->status !== ClaimStatus::Approved) {
            throw new InvalidArgumentException('Only approved claims can be converted to a purchase order.');
        }

        return DB::transaction(function () use ($claim) {
            // Ensure member has a vendor record
            $vendorId = $claim->vendor_id;
            if (! $vendorId) {
                $member = $claim->member;
                $vendor = $this->ensureVendorForMember($member);
                $vendorId = $vendor->id;
                $claim->vendor_id = $vendorId;
            }

            $vendor = Vendor::findOrFail($vendorId);

            // Create the purchase header
            $purchaseHeader = PurchaseHeader::create([
                'vendor_id' => $vendor->id,
                'vendor_posting_group_id' => $vendor->vendor_posting_group_id,
                'posting_date' => today(),
                'status' => 'Open',
                'claim_id' => $claim->id,
            ]);

            // Create purchase lines from claim lines
            foreach ($claim->lines as $claimLine) {
                PurchaseLine::create([
                    'purchase_header_id' => $purchaseHeader->id,
                    'service_id' => $claimLine->service_id,
                    'description' => $claimLine->description,
                    'quantity' => $claimLine->quantity,
                    'unit_price' => $claimLine->unit_amount,
                    'line_amount' => $claimLine->line_amount,
                ]);
            }

            $claim->update([
                'purchase_header_id' => $purchaseHeader->id,
                'status' => ClaimStatus::PurchaseCreated,
            ]);

            ClaimPaymentCreated::dispatch($claim, $purchaseHeader);

            return $purchaseHeader;
        });
    }

    public function cancelClaim(Claim $claim, User $user, string $reason): void
    {
        if (! in_array($claim->status, [ClaimStatus::Draft, ClaimStatus::Submitted])) {
            throw new InvalidArgumentException('Only draft or submitted claims can be cancelled.');
        }

        DB::transaction(function () use ($claim, $reason) {
            if ($claim->status === ClaimStatus::Submitted) {
                $claim->approvals()->where('action', ApprovalAction::Pending->value)->delete();
            }

            $claim->update([
                'status' => ClaimStatus::Cancelled,
                'rejection_reason' => $reason,
            ]);
        });
    }

    private function ensureVendorForMember(Member $member): Vendor
    {
        if ($member->vendor_no) {
            $vendor = Vendor::where('no', $member->vendor_no)->first();
            if ($vendor) {
                return $vendor;
            }
        }

        $purchaseSetup = PurchaseSetup::first();
        $vpg = VendorPostingGroup::where('code', 'MEMBER')->first();
        $displayName = $member->name ?? $member->user?->name ?? $member->no;

        $vendorNo = $purchaseSetup?->vendor_nos
            ? NumberSeries::generate($purchaseSetup->vendor_nos)
            : 'VEN-'.$member->id;

        $vendor = Vendor::create([
            'no' => $vendorNo,
            'name' => $displayName,
            'vendor_posting_group_id' => $vpg?->id,
        ]);

        $member->updateQuietly(['vendor_no' => $vendorNo]);

        return $vendor;
    }
}
