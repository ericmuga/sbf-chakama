<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['direct_income_id', 'line_no', 'service_id', 'description', 'amount'])]
class DirectIncomeLine extends Model
{
    protected $table = 'direct_income_lines';

    protected static function booted(): void
    {
        static::creating(function (DirectIncomeLine $line): void {
            if (empty($line->line_no)) {
                $maxLine = static::where('direct_income_id', $line->direct_income_id)->max('line_no') ?? 0;
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

    public function income(): BelongsTo
    {
        return $this->belongsTo(DirectIncome::class, 'direct_income_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
