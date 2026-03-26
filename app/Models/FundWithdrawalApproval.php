<?php

namespace App\Models;

use App\Enums\ApprovalAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundWithdrawalApproval extends Model
{
    protected $table = 'fund_withdrawal_approvals';

    protected $fillable = [
        'fund_withdrawal_id',
        'step_order',
        'approver_user_id',
        'action',
        'comments',
        'actioned_at',
        'due_by',
    ];

    protected function casts(): array
    {
        return [
            'action' => ApprovalAction::class,
            'actioned_at' => 'datetime',
            'due_by' => 'datetime',
        ];
    }

    public function fundWithdrawal(): BelongsTo
    {
        return $this->belongsTo(FundWithdrawal::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
