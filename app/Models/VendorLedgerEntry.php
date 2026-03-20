<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['entry_no', 'vendor_no', 'posting_date', 'document_type', 'document_no', 'amount', 'remaining_amount', 'open'])]
class VendorLedgerEntry extends Model
{
    use HasFactory;

    protected $table = 'ent_vendor_ledger';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
            'amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'open' => 'boolean',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_no', 'no');
    }
}
