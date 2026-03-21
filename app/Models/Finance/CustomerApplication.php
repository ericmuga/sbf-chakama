<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payment_entry_id', 'invoice_entry_id', 'amount_applied'])]
class CustomerApplication extends Model
{
    protected $table = 'customer_applications';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'amount_applied' => 'decimal:4',
        ];
    }

    public function paymentEntry(): BelongsTo
    {
        return $this->belongsTo(CustomerLedgerEntry::class, 'payment_entry_id');
    }

    public function invoiceEntry(): BelongsTo
    {
        return $this->belongsTo(CustomerLedgerEntry::class, 'invoice_entry_id');
    }
}
