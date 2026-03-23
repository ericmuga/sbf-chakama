<?php

namespace App\Models;

use App\Enums\DirectCostStatus;
use App\Enums\DirectCostType;
use App\Models\Finance\BankAccount;
use App\Models\Finance\GlAccount;
use App\Models\Finance\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectDirectCost extends Model
{
    use SoftDeletes;

    protected $table = 'project_direct_costs';

    protected $fillable = [
        'no',
        'project_id',
        'cost_type',
        'description',
        'amount',
        'gl_account_no',
        'bank_account_id',
        'vendor_id',
        'receipt_path',
        'receipt_number',
        'status',
        'posting_date',
        'posted_at',
        'posted_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'submitted_by',
        'number_series_code',
    ];

    protected function casts(): array
    {
        return [
            'status' => DirectCostStatus::class,
            'cost_type' => DirectCostType::class,
            'amount' => 'decimal:4',
            'posting_date' => 'date',
            'posted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class, 'gl_account_no', 'no');
    }
}
