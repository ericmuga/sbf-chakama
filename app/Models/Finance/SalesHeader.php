<?php

namespace App\Models\Finance;

use App\Models\ShareBillingRun;
use App\Models\ShareSubscription;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['no', 'customer_id', 'document_type', 'applies_to_doc_no', 'posting_date', 'due_date', 'customer_posting_group_id', 'number_series_code', 'status', 'share_subscription_id', 'share_billing_run_id'])]
class SalesHeader extends Model
{
    use HasFactory;

    protected $table = 'sales_headers';

    protected static function booted(): void
    {
        static::creating(function (SalesHeader $header) {
            if (empty($header->no)) {
                $setup = SalesSetup::first();
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

    public function isPosted(): bool
    {
        return strcasecmp((string) $this->status, 'posted') === 0;
    }

    public function isCreditMemo(): bool
    {
        return strcasecmp((string) $this->document_type, 'credit_memo') === 0;
    }

    /**
     * The document total, derived from its sales lines.
     */
    protected function totalAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): float => (float) $this->salesLines->sum('line_amount'),
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value === null ? null : strtolower($value),
            set: fn (?string $value): ?string => $value === null ? null : strtolower($value),
        );
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerPostingGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerPostingGroup::class);
    }

    public function numberSeries(): BelongsTo
    {
        return $this->belongsTo(NumberSeries::class, 'number_series_code', 'code');
    }

    public function salesLines(): HasMany
    {
        return $this->hasMany(SalesLine::class);
    }

    public function shareSubscription(): BelongsTo
    {
        return $this->belongsTo(ShareSubscription::class);
    }

    public function shareBillingRun(): BelongsTo
    {
        return $this->belongsTo(ShareBillingRun::class);
    }
}
