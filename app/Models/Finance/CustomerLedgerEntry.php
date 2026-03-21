<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['entry_no', 'customer_id', 'document_type', 'document_no', 'posting_date', 'due_date', 'amount', 'remaining_amount', 'is_open'])]
class CustomerLedgerEntry extends Model
{
    use HasFactory;

    protected $table = 'customer_ledger_entries';

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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailedCustomerLedgerEntry::class);
    }
}
