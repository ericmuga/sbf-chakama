<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'bank_account_no', 'bank_posting_group_id', 'currency_code'])]
class BankAccount extends Model
{
    use HasFactory;

    protected $table = 'bank_accounts';

    protected function casts(): array
    {
        return [];
    }

    public function bankPostingGroup(): BelongsTo
    {
        return $this->belongsTo(BankPostingGroup::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(BankLedgerEntry::class);
    }
}
