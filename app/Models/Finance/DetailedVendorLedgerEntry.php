<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vendor_ledger_entry_id', 'applied_entry_id', 'document_no', 'posting_date', 'amount', 'entry_type'])]
class DetailedVendorLedgerEntry extends Model
{
    protected $table = 'detailed_vendor_ledger_entries';

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

    public function vendorLedgerEntry(): BelongsTo
    {
        return $this->belongsTo(VendorLedgerEntry::class);
    }

    public function appliedEntry(): BelongsTo
    {
        return $this->belongsTo(VendorLedgerEntry::class, 'applied_entry_id');
    }
}
