<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['no', 'description', 'posting_date', 'bank_account_id', 'status', 'number_series_code'])]
class DirectIncome extends Model
{
    protected $table = 'direct_incomes';

    protected static function booted(): void
    {
        static::creating(function (DirectIncome $income): void {
            if (empty($income->no)) {
                $setup = SalesSetup::first();
                if ($setup?->invoice_nos) {
                    $income->no = NumberSeries::generate($setup->invoice_nos);
                    $income->number_series_code = $setup->invoice_nos;
                }

                if (empty($income->no)) {
                    $income->no = 'INC-'.now()->format('YmdHis');
                }
            }
        });
    }

    protected function casts(): array
    {
        return [
            'posting_date' => 'date',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DirectIncomeLine::class)->orderBy('line_no');
    }

    public function glEntries(): HasMany
    {
        return $this->hasMany(GlEntry::class, 'document_no', 'no');
    }
}
