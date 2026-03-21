<?php

namespace App\Models;

use App\Models\Finance\NumberSeries;
use App\Models\Finance\PurchaseSetup;
use App\Models\Finance\Vendor as FinanceVendor;
use App\Models\Finance\VendorPostingGroup;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['no', 'name', 'vendor_type', 'member_id', 'payment_terms'])]
class Vendor extends Model
{
    use HasFactory;

    protected $table = 'bus_vendors';

    public $timestamps = false;

    protected static function booted(): void
    {
        static::created(function (Vendor $vendor) {
            $setup = PurchaseSetup::first();
            $vpg = VendorPostingGroup::where('code', 'MEMBER')->first();

            if ($vpg) {
                $no = $vendor->no;
                if (empty($no) && $setup?->vendor_nos) {
                    $no = NumberSeries::generate($setup->vendor_nos);
                    $vendor->updateQuietly(['no' => $no]);
                }

                if ($no) {
                    FinanceVendor::firstOrCreate(
                        ['no' => $no],
                        ['name' => $vendor->name ?? $no, 'vendor_posting_group_id' => $vpg->id]
                    );
                }
            }
        });
    }

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

    public function financeVendor(): HasOne
    {
        return $this->hasOne(FinanceVendor::class, 'no', 'no');
    }
}
