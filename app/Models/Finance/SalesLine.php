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
