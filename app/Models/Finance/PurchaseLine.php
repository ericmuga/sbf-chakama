<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['purchase_header_id', 'line_no', 'service_id', 'description', 'quantity', 'unit_price', 'line_amount'])]
class PurchaseLine extends Model
{
    use HasFactory;

    protected $table = 'purchase_lines';

    protected static function booted(): void
    {
        static::creating(function (PurchaseLine $line) {
            if (empty($line->line_no)) {
                $maxLine = static::where('purchase_header_id', $line->purchase_header_id)->max('line_no') ?? 0;
                $line->line_no = $maxLine + 10000;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'line_amount' => 'decimal:4',
        ];
    }

    public function purchaseHeader(): BelongsTo
    {
        return $this->belongsTo(PurchaseHeader::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
