<?php

namespace App\Models;

use App\Enums\FundTransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class FundTransaction extends Model
{
    protected $table = 'fund_transactions';

    const UPDATED_AT = null;

    protected $fillable = [
        'fund_account_id',
        'transaction_type',
        'description',
        'amount',
        'running_balance',
        'reference_type',
        'reference_id',
        'document_no',
        'posting_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => FundTransactionType::class,
            'amount' => 'decimal:4',
            'running_balance' => 'decimal:4',
            'posting_date' => 'date',
        ];
    }

    public function fundAccount(): BelongsTo
    {
        return $this->belongsTo(FundAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeByType(Builder $query, FundTransactionType $type): Builder
    {
        return $query->where('transaction_type', $type->value);
    }

    public function scopeInflow(Builder $query): Builder
    {
        return $query->where('amount', '>', 0);
    }

    public function scopeOutflow(Builder $query): Builder
    {
        return $query->where('amount', '<', 0);
    }

    public function scopeDateRange(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->whereBetween('posting_date', [$from, $to]);
    }
}
