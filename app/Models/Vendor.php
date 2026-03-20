<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['no', 'name', 'vendor_type', 'member_id', 'payment_terms'])]
class Vendor extends Model
{
    use HasFactory;

    protected $table = 'bus_vendors';

    public $timestamps = false;

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function purchaseHeaders(): HasMany
    {
        return $this->hasMany(PurchaseHeader::class, 'vendor_no', 'no');
    }

    public function postedPurchaseHeaders(): HasMany
    {
        return $this->hasMany(PostedPurchaseHeader::class, 'vendor_no', 'no');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(VendorLedgerEntry::class, 'vendor_no', 'no');
    }
}
