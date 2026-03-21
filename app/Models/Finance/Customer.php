<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['no', 'name', 'customer_posting_group_id', 'payment_terms_code'])]
class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';

    protected function casts(): array
    {
        return [];
    }

    public function customerPostingGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerPostingGroup::class);
    }

    public function paymentTerms(): BelongsTo
    {
        return $this->belongsTo(PaymentTerms::class, 'payment_terms_code', 'code');
    }

    public function salesHeaders(): HasMany
    {
        return $this->hasMany(SalesHeader::class);
    }

    public function customerLedgerEntries(): HasMany
    {
        return $this->hasMany(CustomerLedgerEntry::class);
    }
}
