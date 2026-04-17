<?php

namespace App\Models;

use App\Models\Finance\SalesHeader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShareBillingRun extends Model
{
    protected $fillable = [
        'title',
        'billing_schedule_id',
        'billing_date',
        'due_date',
        'status',
        'notify_members',
        'send_email',
        'processed_at',
        'total_invoiced',
        'member_count',
        'error_log',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'billing_date' => 'date',
            'due_date' => 'date',
            'processed_at' => 'datetime',
            'notify_members' => 'boolean',
            'send_email' => 'boolean',
            'total_invoiced' => 'decimal:4',
        ];
    }

    public function billingSchedule(): BelongsTo
    {
        return $this->belongsTo(ShareBillingSchedule::class, 'billing_schedule_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SalesHeader::class, 'share_billing_run_id');
    }

    public function isProcessable(): bool
    {
        return $this->status === 'draft';
    }
}
