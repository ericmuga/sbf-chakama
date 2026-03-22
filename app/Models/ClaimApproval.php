<?php

namespace App\Models;

use App\Enums\ApprovalAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimApproval extends Model
{
    protected $fillable = [
        'claim_id',
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

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('action', ApprovalAction::Pending->value);
    }
}
