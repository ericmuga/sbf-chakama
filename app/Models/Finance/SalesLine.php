<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['sales_header_id', 'line_no', 'service_id', 'description', 'quantity', 'unit_price', 'line_amount', 'customer_posting_group_id', 'service_posting_group_id', 'general_posting_setup_id'])]
class SalesLine extends Model
{
    use HasFactory;

    protected $table = 'sales_lines';

    protected static function booted(): void
    {
        static::creating(function (SalesLine $line) {
            if (empty($line->line_no)) {
                $maxLine = static::where('sales_header_id', $line->sales_header_id)->max('line_no') ?? 0;
                $line->line_no = $maxLine + 10000;
            }

            if ($line->service_id && empty($line->service_posting_group_id)) {
                $service = Service::find($line->service_id);
                $line->service_posting_group_id = $service?->service_posting_group_id;
            }

            if (empty($line->customer_posting_group_id) && $line->sales_header_id) {
                $header = SalesHeader::find($line->sales_header_id);
                $line->customer_posting_group_id = $header?->customer_posting_group_id;
            }

            if (empty($line->general_posting_setup_id) && $line->customer_posting_group_id && $line->service_posting_group_id) {
                $gps = GeneralPostingSetup::where('customer_posting_group_id', $line->customer_posting_group_id)
                    ->where('service_posting_group_id', $line->service_posting_group_id)
                    ->first();
                $line->general_posting_setup_id = $gps?->id;
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

    public function salesHeader(): BelongsTo
    {
        return $this->belongsTo(SalesHeader::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function customerPostingGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerPostingGroup::class);
    }

    public function servicePostingGroup(): BelongsTo
    {
        return $this->belongsTo(ServicePostingGroup::class);
    }

    public function generalPostingSetup(): BelongsTo
    {
        return $this->belongsTo(GeneralPostingSetup::class);
    }
}
