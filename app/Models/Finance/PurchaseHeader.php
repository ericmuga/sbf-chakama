<?php

namespace App\Models\Finance;

use App\Models\Claim;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['no', 'vendor_id', 'posting_date', 'due_date', 'vendor_posting_group_id', 'number_series_code', 'status', 'claim_id'])]
class PurchaseHeader extends Model
{
    use HasFactory;

    protected $table = 'purchase_headers';

    protected static function booted(): void
    {
        static::creating(function (PurchaseHeader $header) {
            if (empty($header->no)) {
                $setup = PurchaseSetup::first();
                if ($setup?->invoice_nos) {
                    $header->no = NumberSeries::generate($setup->invoice_nos);
                    $header->number_series_code = $setup->invoice_nos;
                }
            }
        });
    }

    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function vendorPostingGroup(): BelongsTo
    {
        return $this->belongsTo(VendorPostingGroup::class);
    }

    public function numberSeries(): BelongsTo
    {
        return $this->belongsTo(NumberSeries::class, 'number_series_code', 'code');
    }

    public function purchaseLines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class);
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function scopeForClaim(Builder $query, int $claimId): Builder
    {
        return $query->where('claim_id', $claimId);
    }
}
