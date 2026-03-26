<?php

namespace App\Models;

use App\Enums\ShareStatus;
use App\Models\Finance\CashReceipt;
use App\Models\Finance\SalesHeader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShareSubscription extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'share_subscriptions';

    protected $fillable = [
        'no',
        'member_id',
        'billing_schedule_id',
        'number_of_shares',
        'price_per_share',
        'total_amount',
        'amount_paid',
        'status',
        'is_first_share',
        'is_nominee',
        'nominee_id',
        'subscribed_at',
        'next_billing_date',
        'number_series_code',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShareStatus::class,
            'price_per_share' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'amount_paid' => 'decimal:4',
            'subscribed_at' => 'date',
            'next_billing_date' => 'date',
            'is_first_share' => 'boolean',
            'is_nominee' => 'boolean',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function billingSchedule(): BelongsTo
    {
        return $this->belongsTo(ShareBillingSchedule::class, 'billing_schedule_id');
    }

    public function nominee(): BelongsTo
    {
        return $this->belongsTo(ShareNominee::class, 'nominee_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SalesHeader::class, 'share_subscription_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CashReceipt::class, 'share_subscription_id');
    }

    protected function amountOutstanding(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) $this->total_amount - (float) $this->amount_paid,
        );
    }

    protected function totalAcres(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->number_of_shares * 10,
        );
    }

    protected function isFullyPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) $this->amount_paid >= (float) $this->total_amount,
        );
    }

    protected function holderName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_nominee && $this->nominee
                ? $this->nominee->full_name
                : ($this->member?->name ?? 'Unknown'),
        );
    }

    public function scopeForMember(Builder $query, int $memberId): Builder
    {
        return $query->where('member_id', $memberId);
    }

    public function scopeByStatus(Builder $query, ShareStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function scopePendingPayment(Builder $query): Builder
    {
        return $query->where('status', ShareStatus::PendingPayment->value);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ShareStatus::Active->value);
    }
}
