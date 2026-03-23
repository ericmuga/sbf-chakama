<?php

namespace App\Models;

use App\Models\Finance\GlAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FundAccount extends Model
{
    protected $fillable = [
        'no',
        'name',
        'description',
        'gl_account_no',
        'balance',
        'is_active',
        'number_series_code',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class, 'gl_account_no', 'no');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FundTransaction::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(FundWithdrawal::class);
    }

    public function billingSchedules(): HasMany
    {
        return $this->hasMany(ShareBillingSchedule::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
