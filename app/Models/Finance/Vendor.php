<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['no', 'name', 'vendor_posting_group_id', 'payment_terms_code'])]
class Vendor extends Model
{
    use HasFactory;

    protected $table = 'vendors';

    protected function casts(): array
    {
        return [];
    }

    public function vendorPostingGroup(): BelongsTo
    {
        return $this->belongsTo(VendorPostingGroup::class);
    }

    public function paymentTerms(): BelongsTo
    {
        return $this->belongsTo(PaymentTerms::class, 'payment_terms_code', 'code');
    }

    public function purchaseHeaders(): HasMany
    {
        return $this->hasMany(PurchaseHeader::class);
    }

    public function vendorLedgerEntries(): HasMany
    {
        return $this->hasMany(VendorLedgerEntry::class);
    }
}
