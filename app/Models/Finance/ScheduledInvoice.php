<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['no', 'title', 'description', 'service_id', 'customer_posting_group_id', 'amount', 'scheduled_date', 'due_date', 'notify_members', 'send_email', 'status', 'processed_at', 'total_invoiced', 'member_count', 'error_log', 'created_by'])]
class ScheduledInvoice extends Model
{
    protected $table = 'scheduled_invoices';

    protected static function booted(): void
    {
        static::creating(function (ScheduledInvoice $invoice): void {
            if (empty($invoice->no)) {
                $invoice->no = 'SINV-'.now()->format('YmdHis');
            }
            $invoice->created_by = auth()->id();
        });
    }

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:4',
            'total_invoiced' => 'decimal:4',
            'notify_members' => 'boolean',
            'send_email' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function customerPostingGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerPostingGroup::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isProcessable(): bool
    {
        return $this->status === 'draft';
    }
}
