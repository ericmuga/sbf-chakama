<?php

namespace App\Models;

use App\Enums\ShareBillingFrequency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShareBillingSchedule extends Model
{
    protected $fillable = [
        'name',
        'price_per_share',
        'acres_per_share',
        'billing_frequency',
        'is_default',
        'is_active',
        'fund_account_id',
        'service_id',
    ];

    protected function casts(): array
    {
        return [
            'price_per_share' => 'decimal:4',
            'billing_frequency' => ShareBillingFrequency::class,
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function fundAccount(): BelongsTo
    {
        return $this->belongsTo(FundAccount::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ShareSubscription::class, 'billing_schedule_id');
    }
}
