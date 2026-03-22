<?php

namespace App\Models;

use App\Enums\ApprovalAction;
use App\Enums\ClaimPaymentMethod;
use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\PurchaseHeader;
use App\Models\Finance\Vendor;
use App\Models\Finance\VendorPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Claim extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'no',
        'member_id',
        'claim_type',
        'subject',
        'description',
        'claimed_amount',
        'approved_amount',
        'status',
        'approval_template_id',
        'current_step',
        'payee_name',
        'payment_method',
        'bank_name',
        'bank_account_name',
        'bank_account_no',
        'bank_branch',
        'mpesa_phone',
        'purchase_header_id',
        'vendor_payment_id',
        'vendor_id',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'number_series_code',
    ];

    protected function casts(): array
    {
        return [
            'claim_type' => ClaimType::class,
            'status' => ClaimStatus::class,
            'payment_method' => ClaimPaymentMethod::class,
            'claimed_amount' => 'decimal:4',
            'approved_amount' => 'decimal:4',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ClaimLine::class)->orderBy('line_no');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ClaimApproval::class)->orderBy('step_order');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ClaimAttachment::class);
    }

    public function approvalTemplate(): BelongsTo
    {
        return $this->belongsTo(ClaimApprovalTemplate::class);
    }

    public function purchaseHeader(): BelongsTo
    {
        return $this->belongsTo(PurchaseHeader::class);
    }

    public function vendorPayment(): BelongsTo
    {
        return $this->belongsTo(VendorPayment::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function numberSeries(): BelongsTo
    {
        return $this->belongsTo(NumberSeries::class, 'number_series_code', 'code');
    }

    public function getCurrentApprovalAttribute(): ?ClaimApproval
    {
        return $this->approvals->firstWhere('step_order', $this->current_step);
    }

    public function getIsFullyApprovedAttribute(): bool
    {
        return $this->approvals
            ->where('is_required', true)
            ->every(fn (ClaimApproval $a) => $a->action === ApprovalAction::Approved);
    }

    public function getTotalLineAmountAttribute(): string
    {
        return $this->lines->sum('line_amount');
    }

    public function getPendingApproverAttribute(): ?User
    {
        return $this->approvals
            ->firstWhere('action', ApprovalAction::Pending)
            ?->approver;
    }

    public function scopeByStatus(Builder $query, ClaimStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function scopeForMember(Builder $query, int $memberId): Builder
    {
        return $query->where('member_id', $memberId);
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->whereIn('status', [ClaimStatus::Submitted->value, ClaimStatus::UnderReview->value]);
    }

    public function scopeByType(Builder $query, ClaimType $type): Builder
    {
        return $query->where('claim_type', $type->value);
    }
}
