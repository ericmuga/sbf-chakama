<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['entry_no', 'vendor_id', 'document_type', 'document_no', 'posting_date', 'due_date', 'amount', 'remaining_amount', 'is_open'])]
class VendorLedgerEntry extends Model
{
    use HasFactory;

    protected $table = 'vendor_ledger_entries';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:4',
            'remaining_amount' => 'decimal:4',
            'is_open' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailedVendorLedgerEntry::class);
    }
}
