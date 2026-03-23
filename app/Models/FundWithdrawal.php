<?php

namespace App\Models;

use App\Enums\ApprovalAction;
use App\Enums\FundWithdrawalStatus;
use App\Models\Finance\PurchaseHeader;
use App\Models\Finance\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FundWithdrawal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'no',
        'fund_account_id',
        'project_id',
        'description',
        'amount',
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
        'vendor_id',
        'purchase_header_id',
        'vendor_payment_id',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'submitted_by',
        'number_series_code',
    ];

    protected function casts(): array
    {
        return [
            'status' => FundWithdrawalStatus::class,
            'amount' => 'decimal:4',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function fundAccount(): BelongsTo
    {
        return $this->belongsTo(FundAccount::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(FundWithdrawalApproval::class)->orderBy('step_order');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(FundWithdrawalAttachment::class);
    }

    public function approvalTemplate(): BelongsTo
    {
        return $this->belongsTo(ClaimApprovalTemplate::class, 'approval_template_id');
    }

    public function purchaseHeader(): BelongsTo
    {
        return $this->belongsTo(PurchaseHeader::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    protected function isFullyApproved(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->approvals->isNotEmpty()
                && $this->approvals->every(fn (FundWithdrawalApproval $a) => $a->action === ApprovalAction::Approved),
        );
    }

    public function scopeByStatus(Builder $query, FundWithdrawalStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function scopeForFund(Builder $query, int $fundId): Builder
    {
        return $query->where('fund_account_id', $fundId);
    }
}
