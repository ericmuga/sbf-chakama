<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['direct_expense_id', 'line_no', 'service_id', 'description', 'amount'])]
class DirectExpenseLine extends Model
{
    protected $table = 'direct_expense_lines';

    protected static function booted(): void
    {
        static::creating(function (DirectExpenseLine $line): void {
            if (empty($line->line_no)) {
                $maxLine = static::where('direct_expense_id', $line->direct_expense_id)->max('line_no') ?? 0;
                $line->line_no = $maxLine + 10000;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
        ];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(DirectExpense::class, 'direct_expense_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
