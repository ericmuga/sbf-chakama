<?php

namespace App\Models\Finance;

use App\Models\ShareSubscription;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['no', 'customer_id', 'document_type', 'posting_date', 'due_date', 'customer_posting_group_id', 'number_series_code', 'status', 'share_subscription_id'])]
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
}
