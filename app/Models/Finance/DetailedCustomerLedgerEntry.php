<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customer_ledger_entry_id', 'applied_entry_id', 'document_no', 'posting_date', 'amount', 'entry_type'])]
class DetailedCustomerLedgerEntry extends Model
{
    protected $table = 'detailed_customer_ledger_entries';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
            'amount' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    public function customerLedgerEntry(): BelongsTo
    {
        return $this->belongsTo(CustomerLedgerEntry::class);
    }

    public function appliedEntry(): BelongsTo
    {
        return $this->belongsTo(CustomerLedgerEntry::class, 'applied_entry_id');
    }
}
